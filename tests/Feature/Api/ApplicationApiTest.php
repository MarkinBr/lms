<?php

namespace Tests\Feature\Api;

use App\Mail\InscricaoConfirmacaoMail;
use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Services\NotificarInscricaoRecebidaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApplicationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_inscricao_publica_com_ids_numericos_do_schema(): void
    {
        Mail::fake();

        $curso = Curso::query()->create([
            'titulo' => 'Curso Médio em Teologia',
            'ativo' => true,
        ]);

        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'EAD',
            'ativo' => true,
        ]);

        $response = $this->postJson('/api/v1/applications', [
            'nome_completo' => 'Maria da Silva',
            'email' => 'maria@example.test',
            'telefone' => '11999999999',
            'cpf' => '12345678901',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'nacionalidade' => 'Brasileira',
            'escolaridade' => 'medio_completo',
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'numero' => '1',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Inscrição realizada com sucesso! Guarde o protocolo abaixo.')
            ->assertJsonPath('data.application.curso.id', $curso->id)
            ->assertJsonPath('data.application.course_modality.id', $modalidade->id);

        $protocolo = $response->json('data.protocol');

        $this->assertDatabaseHas('applications', [
            'email' => 'maria@example.test',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => 'em_analise',
        ]);

        Mail::assertSent(InscricaoConfirmacaoMail::class, function (InscricaoConfirmacaoMail $mail) use ($protocolo): bool {
            return $mail->hasTo('maria@example.test')
                && count($mail->inscricoes) === 1
                && $mail->inscricoes[0]['protocol'] === $protocolo;
        });
        Mail::assertSentCount(1);
    }

    public function test_falha_da_notificacao_nao_altera_201_nem_persistencia_da_inscricao(): void
    {
        Mail::fake();

        $nivelTransacaoInicial = DB::connection()->transactionLevel();

        $notificador = new class($nivelTransacaoInicial) extends NotificarInscricaoRecebidaService
        {
            public int $chamadas = 0;

            public int $quantidadeRecebida = 0;

            public bool $persistidaAntesDoEnvio = false;

            public function __construct(private readonly int $nivelTransacaoInicial) {}

            public function enviar(Collection $inscricoes): bool
            {
                $this->chamadas++;
                $this->quantidadeRecebida = $inscricoes->count();
                $this->persistidaAntesDoEnvio = $inscricoes->count() === 1
                    && $inscricoes->every(
                        fn (Application $inscricao): bool => $inscricao->exists
                            && Application::query()->whereKey($inscricao->getKey())->exists(),
                    )
                    && DB::connection()->transactionLevel() === $this->nivelTransacaoInicial;

                return false;
            }
        };
        $this->app->instance(NotificarInscricaoRecebidaService::class, $notificador);

        $curso = Curso::query()->create([
            'titulo' => 'Curso Médio em Teologia',
            'ativo' => true,
        ]);
        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'Híbrido',
            'ativo' => true,
        ]);

        $response = $this->postJson('/api/v1/applications', [
            'nome_completo' => 'Maria da Silva',
            'email' => 'maria.falha@example.test',
            'telefone' => '11999999999',
            'cpf' => '52998224725',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'escolaridade' => 'medio_completo',
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'numero' => '1',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Inscrição realizada com sucesso! Guarde o protocolo abaixo.');

        $this->assertNotEmpty($response->json('data.protocol'));
        $this->assertSame(1, $notificador->chamadas);
        $this->assertSame(1, $notificador->quantidadeRecebida);
        $this->assertTrue($notificador->persistidaAntesDoEnvio);
        $this->assertDatabaseHas('applications', [
            'email' => 'maria.falha@example.test',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => 'em_analise',
        ]);
        Mail::assertNothingSent();
    }

    public function test_nao_cria_inscricao_quando_modalidade_nao_pertence_ao_curso(): void
    {
        Mail::fake();

        $cursoA = Curso::query()->create([
            'titulo' => 'Curso Médio em Teologia',
            'ativo' => true,
        ]);

        $cursoB = Curso::query()->create([
            'titulo' => 'Bacharelado em Teologia',
            'ativo' => true,
        ]);

        $modalidadeDoCursoB = CourseModality::query()->create([
            'curso_id' => $cursoB->id,
            'nome' => 'Presencial',
            'ativo' => true,
        ]);

        $response = $this->postJson('/api/v1/applications', [
            'nome_completo' => 'João Pereira',
            'email' => 'joao.pereira@example.test',
            'telefone' => '11988888888',
            'cpf' => '98765432100',
            'data_nascimento' => '1988-03-20',
            'sexo' => 'M',
            'escolaridade' => 'medio_completo',
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'numero' => '2',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'curso_id' => $cursoA->id,
            'course_modality_id' => $modalidadeDoCursoB->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath(
                'error.details.course_modality_id.0',
                'A modalidade selecionada não pertence ao curso informado.',
            );

        $this->assertDatabaseMissing('applications', [
            'email' => 'joao.pereira@example.test',
        ]);
        $this->assertDatabaseCount('applications', 0);
        Mail::assertNothingSent();
    }
}
