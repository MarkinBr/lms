<?php

namespace Tests\Feature\Secretaria;

use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Requerimento;
use App\Models\SecretariaAssinatura;
use App\Models\SecretariaDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CentralDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private function equipe(string $nivel = 'secretario'): User
    {
        return User::factory()->create(['nivel' => $nivel, 'ativo' => true]);
    }

    private function matricula(): Matricula
    {
        $curso = Curso::query()->create(['titulo' => 'Seminário Médio em Teologia', 'ativo' => true]);
        $aluno = $this->equipe('aluno');

        return Matricula::query()->create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'numero_matricula' => 'MAT-2026-0117',
            'data_matricula' => today()->toDateString(),
            'status' => 'ativa',
            'dia_vencimento' => 10,
        ]);
    }

    public function test_quarto_modulo_ativo_e_rotas_protegidas(): void
    {
        $this->get(route('secretaria.documentos.painel'))->assertRedirect('/secretaria/login');
        $this->actingAs($this->equipe('aluno'))
            ->get(route('secretaria.documentos.painel'))->assertForbidden();

        $this->actingAs($this->equipe());
        $this->get(route('secretaria.central'))
            ->assertOk()->assertSee(route('secretaria.documentos.painel'));

        foreach (['painel', 'solicitacoes', 'atendimento', 'documentacao',
            'declaracoes', 'assinaturas', 'aprovacoes', 'historico'] as $rota) {
            $this->get(route('secretaria.documentos.'.$rota))->assertOk();
        }
    }

    public function test_preparacao_de_documento_preserva_matricula_e_oferece_quatro_previas_a4(): void
    {
        $matricula = $this->matricula();
        $this->actingAs($this->equipe());

        foreach (array_keys(SecretariaDocumento::MODELOS) as $modelo) {
            $resposta = $this->post(route('secretaria.documentos.declaracoes.preparar'), [
                'matricula_id' => $matricula->id,
                'tipo' => 'declaracao_matricula',
                'modelo' => $modelo,
                'finalidade' => 'Comprovação de estudos junto à instituição',
            ]);
            $doc = SecretariaDocumento::query()->latest('id')->firstOrFail();
            $resposta->assertRedirect(route('secretaria.documentos.declaracao', $doc));
            $this->get(route('secretaria.documentos.declaracao', $doc))
                ->assertOk()->assertSee('Prévia institucional');
            $this->get(route('secretaria.documentos.declaracao.pdf', $doc))
                ->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertSame($modelo, $doc->modelo);
        }

        $this->assertDatabaseCount('secretaria_documentos', 4);
        $this->assertDatabaseCount('declaracoes', 0);
        $this->assertSame('MAT-2026-0117', $matricula->fresh()->numero_matricula);
    }

    public function test_assinatura_em_imagem_e_privada_por_responsavel(): void
    {
        Storage::fake('local');
        $autor = $this->equipe();
        $outro = $this->equipe();
        $this->actingAs($autor);

        $this->post(route('secretaria.documentos.assinaturas.salvar'), [
            'cargo' => 'Secretaria Acadêmica',
            'imagem' => UploadedFile::fake()->image('assinatura.png', 500, 170),
            'confirmar_titularidade' => 1,
        ])->assertRedirect(route('secretaria.documentos.assinaturas'));

        $assinatura = SecretariaAssinatura::query()->firstOrFail();
        $this->assertSame($autor->id, $assinatura->responsavel_id);
        $this->assertTrue($assinatura->ativa);
        Storage::disk('local')->assertExists($assinatura->getRawOriginal('arquivo_privado'));
        $this->get(route('secretaria.documentos.assinaturas.imagem', $assinatura))->assertOk();
        $this->actingAs($outro)
            ->get(route('secretaria.documentos.assinaturas.imagem', $assinatura))->assertForbidden();

        $this->actingAs($autor);
        $this->post(route('secretaria.documentos.assinaturas.desativar', $assinatura))->assertRedirect();
        $this->assertFalse($assinatura->fresh()->ativa);
    }

    public function test_revisao_de_documento_exige_segunda_pessoa_e_congela_os_dados(): void
    {
        $matricula = $this->matricula();
        $autor = $this->equipe();
        $revisor = $this->equipe();
        $this->actingAs($autor);
        $this->post(route('secretaria.documentos.declaracoes.preparar'), [
            'matricula_id' => $matricula->id,
            'tipo' => 'comprovante_matricula',
            'modelo' => 'solene',
            'finalidade' => 'Comprovação de vínculo acadêmico',
        ])->assertRedirect();
        $documento = SecretariaDocumento::firstOrFail();

        $this->post(route('secretaria.documentos.declaracao.enviar', $documento), [
            'confirmar_envio' => 1,
        ])->assertRedirect();
        $this->assertSame('em_aprovacao', $documento->fresh()->status);
        $this->assertSame('MAT-2026-0117', $documento->fresh()->dados_congelados['numero_matricula']);

        $matricula->update(['numero_matricula' => 'MAT-ALTERADA']);
        $this->get(route('secretaria.documentos.declaracao', $documento))
            ->assertOk()->assertSee('MAT-2026-0117')->assertDontSee('MAT-ALTERADA');

        $this->post(route('secretaria.documentos.declaracao.revisar', $documento), [
            'decisao' => 'aprovar', 'conferiu_previa' => 1,
        ])->assertSessionHasErrors('decisao');

        $this->actingAs($revisor)->post(route('secretaria.documentos.declaracao.revisar', $documento), [
            'decisao' => 'aprovar', 'conferiu_previa' => 1,
        ])->assertRedirect(route('secretaria.documentos.aprovacoes'));

        $this->assertSame('aprovado_interno', $documento->fresh()->status);
        $this->assertDatabaseHas('secretaria_documento_movimentacoes', [
            'entidade' => 'declaracao', 'entidade_id' => (string) $documento->id,
            'acao' => 'revisao', 'estado_novo' => 'aprovado_interno',
            'ator_id' => $revisor->id,
        ]);
        $this->assertDatabaseCount('declaracoes', 0);
    }

    public function test_assinatura_de_outro_usuario_nao_pode_ser_aplicada(): void
    {
        $matricula = $this->matricula();
        $outro = $this->equipe();
        $autor = $this->equipe();

        $assinatura = SecretariaAssinatura::query()->create([
            'responsavel_id' => $outro->id,
            'cargo' => 'Direção',
            'arquivo_privado' => 'secretaria/assinaturas/exemplo.png',
            'mime' => 'image/png', 'origem' => 'upload', 'ativa' => true,
        ]);
        $this->actingAs($autor)->post(route('secretaria.documentos.declaracoes.preparar'), [
            'matricula_id' => $matricula->id, 'modelo' => 'classico',
            'tipo' => 'declaracao_matricula', 'finalidade' => 'Comprovação acadêmica',
            'assinatura_id' => $assinatura->id,
        ])->assertSessionHasErrors('assinatura_id');
        $this->assertDatabaseCount('secretaria_documentos', 0);
    }

    public function test_protocolo_resolvido_preserva_resposta_responsavel_e_trilha(): void
    {
        $aluno = $this->equipe('aluno');
        $secretario = $this->equipe();
        $req = Requerimento::query()->create([
            'aluno_id' => $aluno->id,
            'tipo' => 'declaracao_matricula',
            'assunto' => 'Declaração para bolsa',
            'descricao' => 'Necessito de uma declaração de matrícula.',
            'status' => 'pendente',
        ]);
        $this->actingAs($secretario);
        $this->get(route('secretaria.documentos.solicitacao', $req))->assertOk()
            ->assertSee('Declaração para bolsa');
        $this->post(route('secretaria.documentos.solicitacao.responder', $req), [
            'status' => 'resolvido', 'resposta' => 'Solicitação encaminhada ao atendimento.',
            'confirmar_analise' => 1,
        ])->assertRedirect();
        $this->assertSame('resolvido', $req->fresh()->status);
        $this->assertSame($secretario->id, $req->fresh()->respondido_por);
        $this->assertDatabaseHas('secretaria_documento_movimentacoes', [
            'entidade' => 'requerimento', 'entidade_id' => (string) $req->id,
            'estado_novo' => 'resolvido', 'ator_id' => $secretario->id,
        ]);
    }
}
