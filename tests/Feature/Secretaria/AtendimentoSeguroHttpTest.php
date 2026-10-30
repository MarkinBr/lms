<?php

namespace Tests\Feature\Secretaria;

use App\Jobs\EntregarAcessoAluno;
use App\Mail\AtendimentoAcessoMail;
use App\Models\User;
use App\Services\Acessos\AtendimentoAluno;
use App\Services\Acessos\EntregasAcesso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AtendimentoSeguroHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_ficha_formulario_e_previa_de_edicao_renderizam_sem_dados_fabricados(): void
    {
        $ator = $this->ator();
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->actingAs($ator)->get(route('secretaria.acessos.show', $aluno))->assertOk()->assertSee($aluno->name);
        $this->get(route('secretaria.acessos.create'))->assertOk();
        $this->get(route('secretaria.acessos.edit', $aluno))->assertOk();
        $dados = ['name' => 'Nome revisado', 'estado' => 'RJ', 'motivo' => 'Correção solicitada pelo aluno', '_estado' => AtendimentoAluno::estado($aluno), 'destino_conferido' => 1, 'senha_confirmacao' => 'Senha-do-atendimento'];
        $pre = $this->put(route('secretaria.acessos.update', $aluno), $dados)->assertOk()->assertSee('Nome revisado');
        $this->assertNotSame('Nome revisado', $aluno->fresh()->name);
        $this->put(route('secretaria.acessos.update', $aluno), $dados + ['confirmar' => 1, 'operacao' => $pre->viewData('operacao')])->assertRedirect();
        $this->assertSame('Nome revisado', $aluno->fresh()->name);
        $this->assertSame('RJ', $aluno->fresh()->estado);
    }

    public function test_cadastro_confirmado_nao_se_repete_mesmo_com_copia_antiga_da_sessao(): void
    {
        $ator = $this->ator();
        $dados = ['name' => 'Aluno sem e-mail', 'whatsapp' => '21999999999', 'canal' => 'whatsapp', 'motivo' => 'Atendimento de novo acesso', 'destino_conferido' => '1', 'senha_confirmacao' => 'Senha-do-atendimento'];
        $pre = $this->actingAs($ator)->post(route('secretaria.acessos.store'), $dados)->assertOk();
        $operacao = $pre->viewData('operacao');
        $sessao = session('acessos.cadastros.'.$operacao);
        $confirmacao = $dados + ['confirmar' => '1', 'operacao' => $operacao];
        $this->post(route('secretaria.acessos.store'), $confirmacao)->assertRedirect();
        $this->withSession(['acessos' => ['cadastros' => [$operacao => $sessao]]])->post(route('secretaria.acessos.store'), $confirmacao)->assertRedirect();
        $this->assertSame(1, User::where('name', 'Aluno sem e-mail')->count());
        $this->assertDatabaseCount('acesso_credenciais', 1);
    }

    public function test_whatsapp_exige_confirmacao_e_revela_so_uma_vez_sem_marcar_como_enviado(): void
    {
        $ator = $this->ator();
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true, 'whatsapp' => '5521999999999']);
        $id = app(EntregasAcesso::class)->preparar($ator, $aluno, 'whatsapp', 'mensagem', 'Olá, mensagem fictícia!', now()->addMinutes(30));
        $this->actingAs($ator)->get(route('secretaria.acessos.entrega', [$aluno, $id]))->assertOk()->assertSee('mensagem fictícia')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get(route('secretaria.acessos.entrega', [$aluno, $id]))->assertGone();
        $this->post(route('secretaria.acessos.entrega.enviar', [$aluno, $id]), ['confirmar' => 1])->assertSessionHasErrors('destino_conferido');
        $resposta = $this->post(route('secretaria.acessos.entrega.enviar', [$aluno, $id]), ['confirmar' => 1, 'destino_conferido' => 1]);
        $resposta->assertRedirect('https://wa.me/5521999999999?text='.rawurlencode('Olá, mensagem fictícia!'));
        $this->assertDatabaseHas('acesso_entregas', ['id' => $id, 'estado' => 'abertura_solicitada', 'conteudo' => null]);
        $this->post(route('secretaria.acessos.entrega.enviar', [$aluno, $id]), ['confirmar' => 1, 'destino_conferido' => 1])->assertGone();
    }

    public function test_link_email_nao_confirma_em_get_e_post_e_de_uso_unico(): void
    {
        $token = str_repeat('a', 64);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true, 'email_verified_at' => null, 'email_confirmacao_pendente' => 'novo@example.test', 'email_confirmacao_token' => hash('sha256', $token), 'email_confirmacao_expira_em' => now()->addHour()]);
        $url = URL::temporarySignedRoute('secretaria.acessos.email.confirmar', now()->addHour(), ['aluno' => $aluno->id]);
        $this->get($url)->assertOk();
        $this->assertNull($aluno->fresh()->email_verified_at);
        $this->post($url, ['token' => $token])->assertRedirect();
        $this->assertSame('novo@example.test', $aluno->fresh()->email);
        $this->assertNotNull($aluno->fresh()->email_verified_at);
        $this->post($url, ['token' => $token])->assertForbidden();
    }

    public function test_fila_recusa_contato_alterado_ou_ator_revogado_e_limpeza_remove_expirados(): void
    {
        Mail::fake();
        $ator = $this->ator();
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $servico = app(EntregasAcesso::class);
        $id = $servico->preparar($ator, $aluno, 'email', 'mensagem', 'Conteúdo reservado', now()->addMinutes(30));
        DB::table('acesso_entregas')->where('id', $id)->update(['estado' => 'enfileirado']);
        $aluno->forceFill(['email' => 'outro@example.test'])->save();
        $servico->enviarEmail($id);
        $this->assertDatabaseHas('acesso_entregas', ['id' => $id, 'estado' => 'cancelado', 'conteudo' => null]);
        $id = $servico->preparar($ator, $aluno, 'email', 'mensagem', 'Outro conteúdo', now()->addMinute());
        $this->travel(2)->minutes();
        $this->artisan('acessos:limpar-entregas')->assertSuccessful();
        $this->assertDatabaseHas('acesso_entregas', ['id' => $id, 'estado' => 'expirado', 'conteudo' => null]);
        Mail::assertNothingSent();
    }

    public function test_fila_nao_entrega_confirmacao_de_email_substituida(): void
    {
        Mail::fake();
        $ator = $this->ator();
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true, 'email_confirmacao_pendente' => 'novo@example.test', 'email_confirmacao_token' => hash('sha256', 'antigo')]);
        $servico = app(EntregasAcesso::class);
        $id = $servico->preparar($ator, $aluno, 'email', 'confirmar_email', 'Link antigo', now()->addHour(), ['email_token_hash' => hash('sha256', 'antigo')]);
        DB::table('acesso_entregas')->where('id', $id)->update(['estado' => 'enfileirado']);
        $aluno->forceFill(['email_confirmacao_token' => hash('sha256', 'novo')])->save();
        $servico->enviarEmail($id);
        Mail::assertNothingSent();
        $this->assertDatabaseHas('acesso_entregas', ['id' => $id, 'estado' => 'cancelado']);
    }

    public function test_email_escapa_html_e_link_individual_fica_clicavel(): void
    {
        $html = (new AtendimentoAcessoMail('<script>alert(1)</script> https://example.test/acesso#segredo=abc.'))->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('href="https://example.test/acesso#segredo=abc"', $html);
    }

    public function test_ficha_mostra_fila_e_falha_sem_expor_mensagem_reservada(): void
    {
        $ator = $this->ator();
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $id = app(EntregasAcesso::class)->preparar($ator, $aluno, 'email', 'mensagem', 'Conteúdo individual reservado', now()->addHour());
        DB::table('acesso_entregas')->where('id', $id)->update(['estado' => 'enfileirado']);
        $this->actingAs($ator)->get(route('secretaria.acessos.show', $aluno))->assertOk()->assertSee('Aguardando processamento')->assertDontSee('Conteúdo individual reservado');
        (new EntregarAcessoAluno($id))->failed(new \RuntimeException('Diagnóstico reservado do transporte'));
        $this->get(route('secretaria.acessos.show', $aluno))->assertOk()->assertSee('Falha no envio')->assertDontSee('Diagnóstico reservado');
    }

    private function ator(): User
    {
        return User::factory()->create(['nivel' => 'secretario', 'ativo' => true, 'password' => Hash::make('Senha-do-atendimento')]);
    }
}
