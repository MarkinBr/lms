<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationBatchApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria um curso ativo com uma modalidade ativa.
     *
     * @return array{0: Curso, 1: CourseModality}
     */
    private function criarCursoComModalidade(string $titulo, string $nomeModalidade): array
    {
        $curso = Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);

        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => $nomeModalidade,
            'ativo' => true,
        ]);

        return [$curso, $modalidade];
    }

    /**
     * Dados pessoais válidos para o payload em lote.
     */
    private function dadosPessoais(): array
    {
        return [
            'nome_completo' => 'Maria da Silva',
            'email' => 'maria@example.test',
            'telefone' => '11999999999',
            'cpf' => '12345678901',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'escolaridade' => 'medio_completo',
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'numero' => '1',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
        ];
    }

    public function test_cria_duas_inscricoes_para_cursos_diferentes_na_mesma_requisicao(): void
    {
        [$cursoA, $modalidadeA] = $this->criarCursoComModalidade('Curso Médio em Teologia', 'EAD');
        [$cursoB, $modalidadeB] = $this->criarCursoComModalidade('Habilitação ao Diaconato', 'Presencial');

        $response = $this->postJson('/api/v1/applications/batch', array_merge($this->dadosPessoais(), [
            'inscricoes' => [
                ['curso_id' => $cursoA->id, 'course_modality_id' => $modalidadeA->id],
                ['curso_id' => $cursoB->id, 'course_modality_id' => $modalidadeB->id],
            ],
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.inscricoes')
            ->assertJsonPath('data.inscricoes.0.curso.id', $cursoA->id)
            ->assertJsonPath('data.inscricoes.0.modalidade.id', $modalidadeA->id)
            ->assertJsonPath('data.inscricoes.0.status', 'em_analise')
            ->assertJsonPath('data.inscricoes.1.curso.id', $cursoB->id)
            ->assertJsonPath('data.inscricoes.1.modalidade.id', $modalidadeB->id)
            ->assertJsonPath('data.inscricoes.1.status', 'em_analise');

        $protocoloA = $response->json('data.inscricoes.0.protocol');
        $protocoloB = $response->json('data.inscricoes.1.protocol');

        $this->assertNotEmpty($protocoloA);
        $this->assertNotEmpty($protocoloB);
        $this->assertNotEquals($protocoloA, $protocoloB);

        $this->assertDatabaseCount('applications', 2);
        $this->assertDatabaseHas('applications', [
            'cpf' => '12345678901',
            'curso_id' => $cursoA->id,
            'status' => 'em_analise',
        ]);
        $this->assertDatabaseHas('applications', [
            'cpf' => '12345678901',
            'curso_id' => $cursoB->id,
            'status' => 'em_analise',
        ]);
    }

    public function test_impede_curso_repetido_na_mesma_requisicao(): void
    {
        [$curso, $modalidade] = $this->criarCursoComModalidade('Curso Médio em Teologia', 'EAD');

        $response = $this->postJson('/api/v1/applications/batch', array_merge($this->dadosPessoais(), [
            'inscricoes' => [
                ['curso_id' => $curso->id, 'course_modality_id' => $modalidade->id],
                ['curso_id' => $curso->id, 'course_modality_id' => $modalidade->id],
            ],
        ]));

        $response->assertStatus(422);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_rollback_total_quando_modalidade_nao_pertence_ao_curso(): void
    {
        [$cursoA, $modalidadeA] = $this->criarCursoComModalidade('Curso Médio em Teologia', 'EAD');
        [$cursoB] = $this->criarCursoComModalidade('Habilitação ao Diaconato', 'Presencial');

        // O primeiro item é válido; o segundo usa a modalidade do curso A no curso B.
        // Nenhum registro pode ser gravado.
        $response = $this->postJson('/api/v1/applications/batch', array_merge($this->dadosPessoais(), [
            'inscricoes' => [
                ['curso_id' => $cursoA->id, 'course_modality_id' => $modalidadeA->id],
                ['curso_id' => $cursoB->id, 'course_modality_id' => $modalidadeA->id],
            ],
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath(
                'error.details.inscricoes.1.course_modality_id.0',
                'A modalidade selecionada não pertence ao curso informado.',
            );

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_impede_inscricao_duplicada_para_o_mesmo_curso_com_o_mesmo_cpf(): void
    {
        [$cursoA, $modalidadeA] = $this->criarCursoComModalidade('Curso Médio em Teologia', 'EAD');
        [$cursoB, $modalidadeB] = $this->criarCursoComModalidade('Habilitação ao Diaconato', 'Presencial');

        // Primeira inscrição no curso A (endpoint único)
        $this->postJson('/api/v1/applications', array_merge($this->dadosPessoais(), [
            'curso_id' => $cursoA->id,
            'course_modality_id' => $modalidadeA->id,
        ]))->assertCreated();

        // Tentativa de repetir o curso A no lote deve falhar por completo
        $response = $this->postJson('/api/v1/applications/batch', array_merge($this->dadosPessoais(), [
            'inscricoes' => [
                ['curso_id' => $cursoA->id, 'course_modality_id' => $modalidadeA->id],
                ['curso_id' => $cursoB->id, 'course_modality_id' => $modalidadeB->id],
            ],
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'error.details.inscricoes.0.curso_id.0',
                'Você já possui uma inscrição para este curso.',
            );

        // Continua existindo apenas a inscrição original
        $this->assertDatabaseCount('applications', 1);
    }

    public function test_permite_mesmo_cpf_em_cursos_diferentes_via_endpoint_unico(): void
    {
        [$cursoA, $modalidadeA] = $this->criarCursoComModalidade('Curso Médio em Teologia', 'EAD');
        [$cursoB, $modalidadeB] = $this->criarCursoComModalidade('Habilitação ao Diaconato', 'Presencial');

        $this->postJson('/api/v1/applications', array_merge($this->dadosPessoais(), [
            'curso_id' => $cursoA->id,
            'course_modality_id' => $modalidadeA->id,
        ]))->assertCreated();

        $this->postJson('/api/v1/applications', array_merge($this->dadosPessoais(), [
            'curso_id' => $cursoB->id,
            'course_modality_id' => $modalidadeB->id,
        ]))->assertCreated();

        $this->assertDatabaseCount('applications', 2);
    }
}
