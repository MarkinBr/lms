<?php

namespace Tests\Feature\Secretaria;

use App\Models\Curso;
use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\VincularMatriculaTurmaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MatriculaTurmaTest extends TestCase
{
    use RefreshDatabase;

    private function curso(string $titulo = 'Teologia'): Curso
    {
        return Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);
    }

    private function aluno(): User
    {
        return User::query()->create([
            'name' => 'Aluno Teste',
            'email' => 'aluno'.uniqid().'@cetsadp.test',
            'password' => 'segredo',
            'nivel' => 'aluno',
            'ativo' => true,
        ]);
    }

    private function turma(Curso $curso, string $nome = 'Turma teste'): Turma
    {
        return Turma::query()->create([
            'curso_id' => $curso->id,
            'nome' => $nome,
            'ano_letivo' => 2026,
            'valor_total_curso' => 1200,
            'qtd_parcelas' => 12,
            'status' => Turma::STATUS_PLANEJADA,
            'ativa' => true,
        ]);
    }

    private function matricula(User $aluno, Curso $curso, string $numero): Matricula
    {
        return Matricula::query()->create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'turma_id' => null,
            'data_matricula' => now()->toDateString(),
            'status' => 'ativo',
            'numero_matricula' => $numero,
            'dia_vencimento' => 10,
        ]);
    }

    public function test_vincula_matricula_e_turma_do_mesmo_curso(): void
    {
        $curso = $this->curso();
        $aluno = $this->aluno();
        $turma = $this->turma($curso);
        $matricula = $this->matricula($aluno, $curso, 'MAT-0001');

        $service = app(VincularMatriculaTurmaService::class);
        $vinculo = $service->executar($matricula, $turma);

        $this->assertInstanceOf(MatriculaTurma::class, $vinculo);

        $this->assertDatabaseHas('matricula_turma', [
            'matricula_id' => $matricula->id,
            'turma_id' => $turma->id,
            'status' => MatriculaTurma::STATUS_ATIVO,
        ]);
    }

    public function test_impede_vinculo_duplicado(): void
    {
        $curso = $this->curso();
        $aluno = $this->aluno();
        $turma = $this->turma($curso);
        $matricula = $this->matricula($aluno, $curso, 'MAT-0002');

        $service = app(VincularMatriculaTurmaService::class);
        $service->executar($matricula, $turma);

        $this->expectException(ValidationException::class);

        $service->executar($matricula, $turma);
    }

    public function test_impede_vinculo_de_cursos_diferentes(): void
    {
        $cursoA = $this->curso('Teologia');
        $cursoB = $this->curso('Diaconato');

        $aluno = $this->aluno();
        $turmaB = $this->turma($cursoB);
        $matriculaA = $this->matricula($aluno, $cursoA, 'MAT-0003');

        $service = app(VincularMatriculaTurmaService::class);

        $this->expectException(ValidationException::class);

        $service->executar($matriculaA, $turmaB);
    }

    public function test_matricula_pode_participar_de_varias_turmas(): void
    {
        $curso = $this->curso();
        $aluno = $this->aluno();
        $turmaA = $this->turma($curso, 'Turma A');
        $turmaB = $this->turma($curso, 'Turma B');
        $matricula = $this->matricula($aluno, $curso, 'MAT-0004');

        $service = app(VincularMatriculaTurmaService::class);
        $service->executar($matricula, $turmaA);
        $service->executar($matricula, $turmaB);

        $this->assertSame(
            2,
            MatriculaTurma::query()->where('matricula_id', $matricula->id)->count()
        );
    }

    public function test_turma_pode_receber_varias_matriculas(): void
    {
        $curso = $this->curso();
        $turma = $this->turma($curso);

        $alunoA = $this->aluno();
        $alunoB = $this->aluno();

        $matriculaA = $this->matricula($alunoA, $curso, 'MAT-0005');
        $matriculaB = $this->matricula($alunoB, $curso, 'MAT-0006');

        $service = app(VincularMatriculaTurmaService::class);
        $service->executar($matriculaA, $turma);
        $service->executar($matriculaB, $turma);

        $this->assertSame(
            2,
            MatriculaTurma::query()->where('turma_id', $turma->id)->count()
        );
    }
}
