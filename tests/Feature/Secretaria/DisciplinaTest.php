<?php

namespace Tests\Feature\Secretaria;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Turma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DisciplinaTest extends TestCase
{
    use RefreshDatabase;

    private function curso(string $titulo = 'Teologia'): Curso
    {
        return Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);
    }

    private function disciplina(Curso $curso, array $override = []): Disciplina
    {
        return Disciplina::query()->create(array_merge([
            'curso_id' => $curso->id,
            'codigo' => 'TEO01',
            'nome' => 'Introdução à Teologia',
            'carga_horaria' => 40,
            'ordem' => 1,
            'ativa' => true,
        ], $override));
    }

    private function turma(Disciplina $disciplina): Turma
    {
        return Turma::query()->create([
            'curso_id' => $disciplina->curso_id,
            'disciplina_id' => $disciplina->id,
            'nome' => 'Turma teste',
            'ano_letivo' => 2026,
            'valor_total_curso' => 1200,
            'qtd_parcelas' => 12,
            'status' => Turma::STATUS_PLANEJADA,
            'ativa' => true,
        ]);
    }

    public function test_cria_disciplina_valida(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);

        $this->assertDatabaseHas('disciplinas', [
            'id' => $disciplina->id,
            'curso_id' => $curso->id,
            'codigo' => 'TEO01',
        ]);
    }

    public function test_codigo_unico_dentro_do_curso(): void
    {
        $curso = $this->curso();
        $this->disciplina($curso);

        $this->expectException(ValidationException::class);

        $this->disciplina($curso, ['nome' => 'Outra disciplina']);
    }

    public function test_mesmo_codigo_pode_existir_em_cursos_diferentes(): void
    {
        $cursoA = $this->curso('Teologia');
        $cursoB = $this->curso('Diaconato');

        $this->disciplina($cursoA);
        $disciplinaB = $this->disciplina($cursoB, ['nome' => 'Diaconato básico']);

        $this->assertDatabaseHas('disciplinas', [
            'id' => $disciplinaB->id,
            'codigo' => 'TEO01',
            'curso_id' => $cursoB->id,
        ]);
    }

    public function test_carga_horaria_deve_ser_positiva(): void
    {
        $curso = $this->curso();

        $this->expectException(ValidationException::class);

        $this->disciplina($curso, ['carga_horaria' => 0]);
    }

    public function test_ordem_deve_ser_positiva(): void
    {
        $curso = $this->curso();

        $this->expectException(ValidationException::class);

        $this->disciplina($curso, ['ordem' => 0]);
    }

    public function test_nao_exclui_disciplina_com_turmas(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $this->turma($disciplina);

        $this->expectException(ValidationException::class);

        $disciplina->delete();
    }

    public function test_exclui_disciplina_sem_turmas(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);

        $disciplina->delete();

        $this->assertDatabaseMissing('disciplinas', [
            'id' => $disciplina->id,
        ]);
    }
}
