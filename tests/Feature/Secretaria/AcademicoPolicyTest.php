<?php

namespace Tests\Feature\Secretaria;

use App\Filament\Secretario\Resources\DisciplinaSecretariaResource;
use App\Filament\Secretario\Resources\PeriodoLetivoSecretariaResource;
use App\Filament\Secretario\Resources\TurmaSecretariaResource;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use App\Models\User;
use App\Services\VincularMatriculaTurmaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicoPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $nivel): User
    {
        return User::query()->create([
            'name' => 'Usuário '.ucfirst($nivel),
            'email' => $nivel.uniqid().'@cetsadp.test',
            'password' => 'segredo',
            'nivel' => $nivel,
            'ativo' => true,
        ]);
    }

    private function curso(): Curso
    {
        return Curso::query()->create([
            'titulo' => 'Teologia',
            'ativo' => true,
        ]);
    }

    private function periodo(): PeriodoLetivo
    {
        return PeriodoLetivo::query()->create([
            'nome' => '2026.1',
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-06-30',
            'ativo' => false,
        ]);
    }

    private function disciplina(Curso $curso): Disciplina
    {
        $modulo = Modulo::query()->firstOrCreate(
            ['curso_id' => $curso->id, 'ordem' => 1],
            ['titulo' => 'Módulo acadêmico'],
        );

        return Disciplina::query()->create([
            'curso_id' => $curso->id,
            'modulo_id' => $modulo->id,
            'codigo' => 'TEO01',
            'nome' => 'Introdução à Teologia',
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

    public function test_admin_e_secretario_gerenciam_base_academica(): void
    {
        $curso = $this->curso();
        $periodo = $this->periodo();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso);
        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-POLICY-1');

        foreach (['admin', 'secretario'] as $nivel) {
            $user = $this->usuario($nivel);
            $this->actingAs($user);

            $this->assertTrue($user->can('view', $periodo));
            $this->assertTrue($user->can('update', $periodo));
            $this->assertTrue($user->can('delete', $periodo));

            $this->assertTrue($user->can('view', $disciplina));
            $this->assertTrue($user->can('update', $disciplina));
            $this->assertTrue($user->can('delete', $disciplina));

            $this->assertTrue($user->can('view', $turma));
            $this->assertTrue($user->can('update', $turma));
            $this->assertTrue($user->can('delete', $turma));

            $this->assertTrue($user->can('view', $matricula));
            $this->assertTrue($user->can('update', $matricula));
            $this->assertTrue($user->can('delete', $matricula));

            $this->assertTrue(PeriodoLetivoSecretariaResource::canAccess());
            $this->assertTrue(DisciplinaSecretariaResource::canAccess());
            $this->assertTrue(TurmaSecretariaResource::canAccess());
        }
    }

    public function test_professor_tem_acesso_restrito_as_proprias_turmas(): void
    {
        $curso = $this->curso();
        $periodo = $this->periodo();
        $disciplina = $this->disciplina($curso);

        $professor = $this->usuario('professor');
        $outroProfessor = $this->usuario('professor');

        $turmaDoProfessor = $this->turma($curso, [
            'professor_id' => $professor->id,
        ]);

        $turmaDeOutroProfessor = $this->turma($curso, [
            'professor_id' => $outroProfessor->id,
            'nome' => 'Turma outro professor',
        ]);

        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-POLICY-2');

        app(VincularMatriculaTurmaService::class)->executar($matricula, $turmaDoProfessor);

        $this->actingAs($professor);

        $this->assertTrue($professor->can('view', $turmaDoProfessor));
        $this->assertFalse($professor->can('view', $turmaDeOutroProfessor));
        $this->assertFalse($professor->can('update', $turmaDoProfessor));
        $this->assertFalse($professor->can('delete', $turmaDoProfessor));

        $this->assertFalse($professor->can('view', $periodo));
        $this->assertFalse($professor->can('view', $disciplina));

        $this->assertTrue($professor->can('view', $matricula));

        $this->assertFalse(PeriodoLetivoSecretariaResource::canAccess());
        $this->assertFalse(DisciplinaSecretariaResource::canAccess());
        $this->assertFalse(TurmaSecretariaResource::canAccess());
    }

    public function test_aluno_visualiza_apenas_turmas_e_matriculas_relacionadas(): void
    {
        $curso = $this->curso();

        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');

        $turmaVinculada = $this->turma($curso, ['nome' => 'Turma vinculada']);
        $turmaNaoVinculada = $this->turma($curso, ['nome' => 'Turma não vinculada']);

        $matriculaDoAluno = $this->matricula($aluno, $curso, 'MAT-POLICY-3');
        $matriculaDeOutroAluno = $this->matricula($outroAluno, $curso, 'MAT-POLICY-4');

        app(VincularMatriculaTurmaService::class)->executar($matriculaDoAluno, $turmaVinculada);

        $this->actingAs($aluno);

        $this->assertTrue($aluno->can('view', $turmaVinculada));
        $this->assertFalse($aluno->can('view', $turmaNaoVinculada));
        $this->assertFalse($aluno->can('update', $turmaVinculada));
        $this->assertFalse($aluno->can('delete', $turmaVinculada));

        $this->assertTrue($aluno->can('view', $matriculaDoAluno));
        $this->assertFalse($aluno->can('view', $matriculaDeOutroAluno));
        $this->assertFalse($aluno->can('update', $matriculaDoAluno));
        $this->assertFalse($aluno->can('delete', $matriculaDoAluno));

        $this->assertFalse(PeriodoLetivoSecretariaResource::canAccess());
        $this->assertFalse(DisciplinaSecretariaResource::canAccess());
        $this->assertFalse(TurmaSecretariaResource::canAccess());
    }

    public function test_visitante_nao_acessa_resources_academicos(): void
    {
        auth()->logout();

        $this->assertFalse(PeriodoLetivoSecretariaResource::canAccess());
        $this->assertFalse(DisciplinaSecretariaResource::canAccess());
        $this->assertFalse(TurmaSecretariaResource::canAccess());
    }
}
