<?php

namespace Tests\Feature\Secretaria;

use App\Jobs\EntregarAcessoAluno;
use App\Mail\AtendimentoAcessoMail;
use App\Models\User;
use App\Services\Acessos\AtendimentoAluno;
use App\Services\Acessos\EntregasAcesso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EntregasAcessoTest extends TestCase
{
    use RefreshDatabase;

    public function test_payload_fica_cifrado_e_job_descarta_envio_apos_revogacao(): void
    {
        Mail::fake();
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $id = app(EntregasAcesso::class)->preparar($ator, $aluno, 'email', 'mensagem', 'Texto reservado de teste', now()->addMinutes(30));
        $this->assertStringNotContainsString('Texto reservado', DB::table('acesso_entregas')->where('id', $id)->value('conteudo'));
        DB::table('acesso_entregas')->where('id', $id)->update(['estado' => 'enfileirado']);
        AtendimentoAluno::revogar($aluno);
        (new EntregarAcessoAluno($id))->handle(app(EntregasAcesso::class));
        Mail::assertNothingSent();
        $this->assertDatabaseHas('acesso_entregas', ['id' => $id, 'estado' => 'cancelado', 'conteudo' => null]);
    }

    public function test_job_envia_uma_vez_sem_segredo_no_payload_da_fila(): void
    {
        Mail::fake();
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $id = app(EntregasAcesso::class)->preparar($ator, $aluno, 'email', 'mensagem', 'Instruções fictícias', now()->addMinutes(30));
        DB::table('acesso_entregas')->where('id', $id)->update(['estado' => 'enfileirado']);
        $job = new EntregarAcessoAluno($id);
        $this->assertStringNotContainsString('Instruções', serialize($job));
        $job->handle(app(EntregasAcesso::class));
        $job->handle(app(EntregasAcesso::class));
        Mail::assertSent(AtendimentoAcessoMail::class, 1);
        $this->assertDatabaseHas('acesso_entregas', ['id' => $id, 'estado' => 'enviado', 'conteudo' => null]);
    }
}
