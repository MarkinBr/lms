<?php

namespace Tests\Feature\Autorizacao;

use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #49 — Relatórios de matrícula (declaração, histórico, comprovante)
 * só podem ser gerados por quem tem vínculo autorizado pela MatriculaPolicy.
 */
class MatriculaRelatorioAuthTest extends TestCase
{
    use RefreshDatabase;

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

    private function curso(): Curso
    {
        return Curso::query()->create(['titulo' => 'Teologia', 'ativo' => true]);
    }

    private function turma(Curso $curso, User $professor): Turma
    {
        return Turma::query()->create([
            'curso_id' => $curso->id,
            'professor_id' => $professor->id,
            'nome' => 'Turma teste',
            'ano_letivo' => 2026,
            'valor_total_curso' => 1200,
            'qtd_parcelas' => 12,
            'status' => Turma::STATUS_ABERTA,
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

    // 1. Secretaria gera declaração de matrícula (permitido).
    public function test_secretario_pode_gerar_declaracao(): void
    {
        $secretario = $this->usuario('secretario');
        $aluno = $this->usuario('aluno');
        $curso = $this->curso();
        $matricula = $this->matricula($aluno, $curso, 'MAT-DECL-1');

        $this->actingAs($secretario)
            ->get(route('matricula.declaracao.pdf', $matricula->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    // 2. Aluno não gera declaração de matrícula de outro aluno (negado).
    public function test_aluno_nao_gera_declaracao_de_outro_aluno(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $curso = $this->curso();
        $matriculaOutro = $this->matricula($outroAluno, $curso, 'MAT-DECL-2');

        $this->actingAs($aluno)
            ->get(route('matricula.declaracao.pdf', $matriculaOutro->id))
            ->assertForbidden();
    }

    // 3. Professor não acessa declaração de turma que não lhe pertence (negado).
    public function test_professor_nao_gera_declaracao_de_turma_alheia(): void
    {
        $professor = $this->usuario('professor');
        $outroProfessor = $this->usuario('professor');
        $aluno = $this->usuario('aluno');
        $curso = $this->curso();
        $turmaOutro = $this->turma($curso, $outroProfessor);
        $matricula = $this->matricula($aluno, $curso, 'MAT-DECL-3');
        $matricula->update(['turma_id' => $turmaOutro->id]);

        $this->actingAs($professor)
            ->get(route('matricula.declaracao.pdf', $matricula->id))
            ->assertForbidden();
    }

    // 4. Professor pode gerar declaração de aluno da própria turma (permitido).
    public function test_professor_gera_declaracao_de_aluno_da_propria_turma(): void
    {
        $professor = $this->usuario('professor');
        $aluno = $this->usuario('aluno');
        $curso = $this->curso();
        $turma = $this->turma($curso, $professor);
        $matricula = $this->matricula($aluno, $curso, 'MAT-DECL-4');
        $matricula->update(['turma_id' => $turma->id]);

        $this->actingAs($professor)
            ->get(route('matricula.declaracao.pdf', $matricula->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    // 5. Usuário inativo é negado mesmo sendo secretário (Issue #49).
    public function test_secretario_inativo_nao_gera_declaracao(): void
    {
        $secretario = $this->usuario('secretario', ativo: false);
        $aluno = $this->usuario('aluno');
        $curso = $this->curso();
        $matricula = $this->matricula($aluno, $curso, 'MAT-DECL-5');

        $this->actingAs($secretario)
            ->get(route('matricula.declaracao.pdf', $matricula->id))
            ->assertForbidden();
    }

    // 6. Histórico de matrícula alheia retorna 403 para aluno (negado).
    public function test_aluno_nao_gera_historico_de_outro_aluno(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $curso = $this->curso();
        $matriculaOutro = $this->matricula($outroAluno, $curso, 'MAT-HIST-1');

        $this->actingAs($aluno)
            ->get(route('matricula.historico.pdf', $matriculaOutro->id))
            ->assertForbidden();
    }

    // 7. Histórico/comprovante de aluno da própria turma: professor autorizado (501 stub).
    public function test_professor_autorizado_recebe_501_do_stub(): void
    {
        $professor = $this->usuario('professor');
        $aluno = $this->usuario('aluno');
        $curso = $this->curso();
        $turma = $this->turma($curso, $professor);
        $matricula = $this->matricula($aluno, $curso, 'MAT-HIST-2');
        $matricula->update(['turma_id' => $turma->id]);

        $this->actingAs($professor)
            ->get(route('matricula.historico.pdf', $matricula->id))
            ->assertStatus(501);

        $this->actingAs($professor)
            ->get(route('matricula.comprovante.pdf', $matricula->id))
            ->assertStatus(501);
    }
}
