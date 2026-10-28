<?php

namespace Tests\Feature\Aluno;

use App\Filament\Aluno\Pages\MeuCursoDetalhe;
use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GradeCurricularTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_separa_aulas_por_disciplina_e_preserva_legadas_sem_duplicar(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $curso = Curso::create(['titulo' => 'Teologia', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo I', 'ordem' => 1]);

        $disciplinaA = $this->disciplina($curso, $modulo, 'História Judaica', 'HJ-001', 1);
        $disciplinaB = $this->disciplina($curso, $modulo, 'Hermeneutica', 'HER-001', 2);

        $aulaB = Aula::create([
            'disciplina_id' => $disciplinaB->id,
            'titulo' => 'Interpretação de textos',
            'ordem' => 1,
            'liberada' => false,
        ]);
        $aulaA = Aula::create([
            'disciplina_id' => $disciplinaA->id,
            'titulo' => 'Introdução à história judaica',
            'ordem' => 1,
            'liberada' => true,
        ]);

        // Simula o acervo anterior à inclusão de disciplina_id.
        $legadaId = DB::table('aulas')->insertGetId([
            'modulo_id' => $modulo->id,
            'disciplina_id' => null,
            'titulo' => 'Aula do acervo antigo',
            'ordem' => 2,
            'liberada' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matricula = Matricula::create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'numero_matricula' => 'MAT-GRADE-1',
            'data_matricula' => now()->toDateString(),
            'status' => 'ativo',
            'dia_vencimento' => 10,
        ]);

        DB::table('aula_progresso')->insert([
            'aluno_id' => $aluno->id,
            'aula_id' => $aulaA->id,
            'concluida' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $antes = DB::table('aulas')->count();
        $grade = MeuCursoDetalhe::gradeDaMatricula($matricula);

        $this->assertSame($antes, DB::table('aulas')->count());
        $this->assertSame(3, $grade['total_aulas']);
        $this->assertSame(1, $grade['aulas_concluidas']);
        $this->assertCount(1, $grade['modulos']);

        $materias = $grade['modulos'][0]['disciplinas'];
        $this->assertSame(['História Judaica', 'Hermeneutica', 'Aulas aguardando organização'], array_column($materias, 'nome'));
        $this->assertSame([$aulaA->id], array_column($materias[0]['aulas'], 'id'));
        $this->assertSame([$aulaB->id], array_column($materias[1]['aulas'], 'id'));
        $this->assertSame([$legadaId], array_column($materias[2]['aulas'], 'id'));
        $this->assertTrue($materias[0]['aulas'][0]['concluida']);
        $this->assertFalse($materias[1]['aulas'][0]['liberada']);
        $this->assertDatabaseHas('aulas', ['id' => $legadaId, 'disciplina_id' => null]);
    }

    public function test_grade_nao_inclui_aulas_de_outro_curso(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $curso = Curso::create(['titulo' => 'Curso A', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'M1', 'ordem' => 1]);
        $disciplina = $this->disciplina($curso, $modulo, 'A', 'A-001', 1);
        $aulaA = Aula::create(['disciplina_id' => $disciplina->id, 'titulo' => 'Aula A', 'ordem' => 1]);

        $outroCurso = Curso::create(['titulo' => 'Curso B', 'ativo' => true]);
        $outroModulo = Modulo::create(['curso_id' => $outroCurso->id, 'titulo' => 'M2', 'ordem' => 1]);
        $outraDisciplina = $this->disciplina($outroCurso, $outroModulo, 'B', 'B-001', 1);
        Aula::create(['disciplina_id' => $outraDisciplina->id, 'titulo' => 'Aula B', 'ordem' => 1]);

        $matricula = Matricula::create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'numero_matricula' => 'MAT-GRADE-2',
            'data_matricula' => now()->toDateString(),
            'status' => 'ativo',
            'dia_vencimento' => 10,
        ]);

        $grade = MeuCursoDetalhe::gradeDaMatricula($matricula);

        $this->assertSame(1, $grade['total_aulas']);
        $this->assertSame([$aulaA->id], array_column($grade['modulos'][0]['disciplinas'][0]['aulas'], 'id'));
    }

    private function disciplina(Curso $curso, Modulo $modulo, string $nome, string $codigo, int $ordem): Disciplina
    {
        return Disciplina::create([
            'curso_id' => $curso->id,
            'modulo_id' => $modulo->id,
            'nome' => $nome,
            'codigo' => $codigo,
            'carga_horaria' => 40,
            'ordem' => $ordem,
            'ativa' => true,
        ]);
    }
}
