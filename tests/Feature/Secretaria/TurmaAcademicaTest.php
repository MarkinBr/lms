<?php

namespace Tests\Feature\Secretaria;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TurmaAcademicaTest extends TestCase
{
    use RefreshDatabase;

    private function curso(string $titulo = 'Teologia'): Curso
    {
        return Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);
    }

    private function usuario(string $nivel, bool $ativo = true): User
    {
        return User::query()->create([
            'name' => 'Usuário '.ucfirst($nivel),
            'email' => $nivel.uniqid().'@cetsadp.test',
            'password' => 'segredo',
            'nivel' => $nivel,
            'ativo' => $ativo,
        ]);
    }

    private function periodo(string $nome = '2026.1'): PeriodoLetivo
    {
        return PeriodoLetivo::query()->create([
            'nome' => $nome,
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-06-30',
            'ativo' => false,
        ]);
    }

    private function disciplina(Curso $curso, string $codigo = 'TEO01'): Disciplina
    {
        return Disciplina::query()->create([
            'curso_id' => $curso->id,
            'codigo' => $codigo,
            'nome' => 'Disciplina '.$codigo,
            'carga_horaria' => 40,
            'ordem' => 1,
            'ativa' => true,
        ]);
    }

    private function turma(Curso $curso, array $override = []): Turma
    {
        return Turma::query()->create(array_merge([
            'curso_id' => $curso->id,
            'nome' => 'Turma teste',
            'ano_letivo' => 2026,
            'valor_total_curso' => 1200,
            'qtd_parcelas' => 12,
            'status' => Turma::STATUS_PLANEJADA,
            'ativa' => true,
        ], $override));
    }

    public function test_cria_turma_com_vinculos_academicos_validos(): void
    {
        $curso = $this->curso();
        $periodo = $this->periodo();
        $disciplina = $this->disciplina($curso);
        $professor = $this->usuario('professor');

        $turma = $this->turma($curso, [
            'periodo_letivo_id' => $periodo->id,
            'disciplina_id' => $disciplina->id,
            'professor_id' => $professor->id,
            'codigo' => 'TURMA-001',
            'capacidade' => 30,
            'modalidade' => Turma::MODALIDADE_PRESENCIAL,
        ]);

        $this->assertDatabaseHas('turmas', [
            'id' => $turma->id,
            'periodo_letivo_id' => $periodo->id,
            'disciplina_id' => $disciplina->id,
            'professor_id' => $professor->id,
        ]);
    }

    public function test_disciplina_de_outro_curso_falha(): void
    {
        $cursoA = $this->curso('Teologia');
        $cursoB = $this->curso('Diaconato');
        $disciplinaB = $this->disciplina($cursoB);

        $this->expectException(ValidationException::class);

        $this->turma($cursoA, [
            'disciplina_id' => $disciplinaB->id,
        ]);
    }

    public function test_professor_com_nivel_invalido_falha(): void
    {
        $curso = $this->curso();
        $naoProfessor = $this->usuario('aluno');

        $this->expectException(ValidationException::class);

        $this->turma($curso, [
            'professor_id' => $naoProfessor->id,
        ]);
    }

    public function test_professor_inativo_falha(): void
    {
        $curso = $this->curso();
        $professorInativo = $this->usuario('professor', false);

        $this->expectException(ValidationException::class);

        $this->turma($curso, [
            'professor_id' => $professorInativo->id,
        ]);
    }

    public function test_codigo_unico_por_periodo_letivo(): void
    {
        $curso = $this->curso();
        $periodo = $this->periodo();

        $this->turma($curso, [
            'periodo_letivo_id' => $periodo->id,
            'codigo' => 'COD-1',
        ]);

        $this->expectException(ValidationException::class);

        $this->turma($curso, [
            'periodo_letivo_id' => $periodo->id,
            'codigo' => 'COD-1',
            'nome' => 'Turma duplicada',
        ]);
    }

    public function test_mesmo_codigo_pode_existir_em_periodos_diferentes(): void
    {
        $curso = $this->curso();
        $periodoA = $this->periodo('2026.1');
        $periodoB = $this->periodo('2026.2');

        $this->turma($curso, [
            'periodo_letivo_id' => $periodoA->id,
            'codigo' => 'COD-1',
        ]);

        $turmaB = $this->turma($curso, [
            'periodo_letivo_id' => $periodoB->id,
            'codigo' => 'COD-1',
            'nome' => 'Turma segundo período',
        ]);

        $this->assertDatabaseHas('turmas', [
            'id' => $turmaB->id,
            'codigo' => 'COD-1',
            'periodo_letivo_id' => $periodoB->id,
        ]);
    }

    public function test_turma_legada_pode_existir_sem_novos_vinculos(): void
    {
        $curso = $this->curso();

        $turma = $this->turma($curso, [
            'periodo_letivo_id' => null,
            'disciplina_id' => null,
            'professor_id' => null,
            'codigo' => null,
            'status' => null,
        ]);

        $this->assertDatabaseHas('turmas', [
            'id' => $turma->id,
            'curso_id' => $curso->id,
        ]);
    }
}
