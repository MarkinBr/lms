<?php

namespace Tests\Feature\Autorizacao;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #49 — Operações de aula do portal do aluno só podem ocorrer
 * dentro do vínculo (matrícula) do próprio aluno autenticado.
 */
class AulaAlunoAuthTest extends TestCase
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

    private function curso(string $titulo = 'Teologia'): Curso
    {
        return Curso::query()->create(['titulo' => $titulo, 'ativo' => true]);
    }

    private function modulo(Curso $curso): Modulo
    {
        return Modulo::query()->create([
            'curso_id' => $curso->id,
            'titulo' => 'Módulo 1',
            'ordem' => 1,
        ]);
    }

    private function aula(Modulo $modulo, bool $liberada = true): Aula
    {
        return Aula::query()->create([
            'modulo_id' => $modulo->id,
            'titulo' => 'Aula teste',
            'ordem' => 1,
            'liberada' => $liberada,
        ]);
    }

    private function matricula(User $aluno, Curso $curso, string $status = 'ativo'): Matricula
    {
        return Matricula::query()->create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'turma_id' => null,
            'data_matricula' => now()->toDateString(),
            'status' => $status,
            'numero_matricula' => 'MAT-'.uniqid(),
            'dia_vencimento' => 10,
        ]);
    }

    // 1. Aluno pode concluir aula do próprio vínculo (permitido).
    public function test_aluno_pode_concluir_aula_do_proprio_curso(): void
    {
        $aluno = $this->usuario('aluno');
        $curso = $this->curso();
        $modulo = $this->modulo($curso);
        $aula = $this->aula($modulo);
        $this->matricula($aluno, $curso);

        $this->actingAs($aluno)
            ->postJson(route('aluno.aula.concluir.nova', $aula->id))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    // 2. Aluno não pode concluir aula de curso sem matrícula (negado).
    public function test_aluno_nao_conclui_aula_de_curso_alheio(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $curso = $this->curso();
        $modulo = $this->modulo($curso);
        $aula = $this->aula($modulo);

        // matrícula existe apenas para outro aluno
        $this->matricula($outroAluno, $curso);

        $this->actingAs($aluno)
            ->postJson(route('aluno.aula.concluir.nova', $aula->id))
            ->assertForbidden();
    }

    // 3. Aluno não registra progresso em aula de outro vínculo.
    public function test_aluno_nao_registra_progresso_em_aula_alheia(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $curso = $this->curso();
        $modulo = $this->modulo($curso);
        $aula = $this->aula($modulo);
        $this->matricula($outroAluno, $curso);

        $this->actingAs($aluno)
            ->postJson(route('aluno.aula.progresso.nova', $aula->id), ['progresso' => 50])
            ->assertForbidden();
    }

    // 4. Aluno não registra tempo em aula de outro vínculo.
    public function test_aluno_nao_registra_tempo_em_aula_alheia(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $curso = $this->curso();
        $modulo = $this->modulo($curso);
        $aula = $this->aula($modulo);
        $this->matricula($outroAluno, $curso);

        $this->actingAs($aluno)
            ->postJson(route('aluno.aula.tempo', $aula->id), ['tempo' => 5])
            ->assertForbidden();
    }

    // 5. Aluno não salva quiz em aula de outro vínculo.
    public function test_aluno_nao_salva_quiz_em_aula_alheia(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $curso = $this->curso();
        $modulo = $this->modulo($curso);
        $aula = $this->aula($modulo);
        $this->matricula($outroAluno, $curso);

        $this->actingAs($aluno)
            ->postJson(route('aluno.aula.quiz', $aula->id), [
                'score' => 80,
                'answers' => [0 => 'A'],
                'attempts' => [],
            ])
            ->assertForbidden();
    }

    // 6. Aluno não salva notas em aula de outro vínculo.
    public function test_aluno_nao_salva_notas_em_aula_alheia(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $curso = $this->curso();
        $modulo = $this->modulo($curso);
        $aula = $this->aula($modulo);
        $this->matricula($outroAluno, $curso);

        $this->actingAs($aluno)
            ->postJson(route('aluno.aula.notas', $aula->id), ['notas' => 'anotação'])
            ->assertForbidden();
    }

    // 7. Aluno não comenta em aula de outro vínculo.
    public function test_aluno_nao_comenta_em_aula_alheia(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $curso = $this->curso();
        $modulo = $this->modulo($curso);
        $aula = $this->aula($modulo);
        $this->matricula($outroAluno, $curso);

        $this->actingAs($aluno)
            ->postJson(route('aluno.aula.comentar', $aula->id), ['comentario' => 'oi'])
            ->assertForbidden();
    }

    // 8. Aluno inativo é negado nas rotas aula-v2 (middleware 'aluno').
    public function test_aluno_inativo_e_negado_em_rota_de_aula(): void
    {
        $aluno = $this->usuario('aluno', ativo: false);
        $curso = $this->curso();
        $modulo = $this->modulo($curso);
        $aula = $this->aula($modulo);
        $this->matricula($aluno, $curso);

        $this->actingAs($aluno)
            ->postJson(route('aluno.aula.concluir.nova', $aula->id))
            ->assertForbidden();
    }

    // 9. Secretário não passa pelo middleware 'aluno' nas rotas de aula.
    public function test_secretario_nao_opera_rota_de_aula_do_portal_aluno(): void
    {
        $secretario = $this->usuario('secretario');
        $curso = $this->curso();
        $modulo = $this->modulo($curso);
        $aula = $this->aula($modulo);

        $this->actingAs($secretario)
            ->postJson(route('aluno.aula.concluir.nova', $aula->id))
            ->assertForbidden();
    }
}
