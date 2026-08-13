<?php

namespace Tests\Feature\Secretaria;

use App\Filament\Secretario\Resources\ChamadaFrequenciaResource;
use App\Models\ChamadaFrequencia;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Frequencia;
use App\Models\Matricula;
use App\Models\Turma;
use App\Models\User;
use App\Services\VincularMatriculaTurmaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Issue #32 — Controle de frequência por aula, turma e disciplina.
 *
 * Segue o padrão de tests/Feature/Secretaria/*Test.php: criação manual dos
 * modelos (sem factories), validações testadas no nível do Model/Policy.
 */
class FrequenciaTest extends TestCase
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

    private function curso(string $titulo = 'Teologia'): Curso
    {
        return Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);
    }

    private function disciplina(Curso $curso, string $nome = 'Hermenêutica'): Disciplina
    {
        return Disciplina::query()->create([
            'curso_id' => $curso->id,
            'codigo' => 'DIS-'.uniqid(),
            'nome' => $nome,
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
            'status' => Turma::STATUS_ABERTA,
            'ativa' => true,
        ], $override));
    }

    private function matricula(User $aluno, Curso $curso, string $numero, array $override = []): Matricula
    {
        return Matricula::query()->create(array_merge([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'turma_id' => null,
            'data_matricula' => now()->toDateString(),
            'status' => 'ativo',
            'numero_matricula' => $numero,
            'dia_vencimento' => 10,
        ], $override));
    }

    private function chamada(Turma $turma, Disciplina $disciplina, User $criador, array $override = []): ChamadaFrequencia
    {
        return ChamadaFrequencia::query()->create(array_merge([
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'data_aula' => now()->toDateString(),
            'titulo' => 'Aula teste',
            'status' => ChamadaFrequencia::STATUS_RASCUNHO,
            'criada_por' => $criador->id,
        ], $override));
    }

    private function frequencia(ChamadaFrequencia $chamada, Matricula $matricula, User $registrador, string $situacao = Frequencia::SITUACAO_PRESENTE): Frequencia
    {
        return Frequencia::query()->create([
            'chamada_frequencia_id' => $chamada->id,
            'matricula_id' => $matricula->id,
            'situacao' => $situacao,
            'registrado_por' => $registrador->id,
        ]);
    }

    // 1. Secretaria cria chamada válida com alunos ativos.
    public function test_secretaria_cria_chamada_valida(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');
        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-FREQ-1');
        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);

        $this->actingAs($secretario);

        $this->assertTrue($secretario->can('create', ChamadaFrequencia::class));
        $this->assertTrue(ChamadaFrequenciaResource::canAccess());

        $chamada = $this->chamada($turma, $disciplina, $secretario);
        $frequencia = $this->frequencia($chamada, $matricula, $secretario);

        $this->assertDatabaseHas('chamadas_frequencia', [
            'id' => $chamada->id,
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'status' => ChamadaFrequencia::STATUS_RASCUNHO,
        ]);
        $this->assertDatabaseHas('frequencias', [
            'id' => $frequencia->id,
            'chamada_frequencia_id' => $chamada->id,
            'matricula_id' => $matricula->id,
            'situacao' => Frequencia::SITUACAO_PRESENTE,
            'registrado_por' => $secretario->id,
        ]);
    }

    // 2. Disciplina de outro curso é rejeitada no servidor (Model).
    public function test_disciplina_de_outro_curso_e_rejeitada(): void
    {
        $cursoA = $this->curso('Teologia');
        $cursoB = $this->curso('Diaconato');
        $turmaA = $this->turma($cursoA);
        $disciplinaB = $this->disciplina($cursoB, 'Outra disciplina');
        $secretario = $this->usuario('secretario');

        $this->expectException(ValidationException::class);

        $this->chamada($turmaA, $disciplinaB, $secretario);
    }

    // 3. Aluno de outra turma não pode ser incluído na chamada.
    public function test_aluno_de_outra_turma_nao_pode_ser_incluido(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turmaA = $this->turma($curso, ['nome' => 'Turma A']);
        $turmaB = $this->turma($curso, ['nome' => 'Turma B']);
        $secretario = $this->usuario('secretario');

        $alunoB = $this->usuario('aluno');
        $matriculaB = $this->matricula($alunoB, $curso, 'MAT-FREQ-2');
        app(VincularMatriculaTurmaService::class)->executar($matriculaB, $turmaB);

        $chamadaDaA = $this->chamada($turmaA, $disciplina, $secretario);

        $this->expectException(ValidationException::class);

        $this->frequencia($chamadaDaA, $matriculaB, $secretario);
    }

    // 4. Unique composta impede frequência duplicada na mesma chamada.
    public function test_frequencia_duplicada_na_mesma_chamada_e_bloqueada(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');
        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-FREQ-3');
        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);

        $chamada = $this->chamada($turma, $disciplina, $secretario);
        $this->frequencia($chamada, $matricula, $secretario);

        $this->expectException(QueryException::class);

        $this->frequencia($chamada, $matricula, $secretario, Frequencia::SITUACAO_FALTA);
    }

    // 5. Matrícula legada (matriculas.turma_id) ativa entra na chamada e aparece ao aluno.
    public function test_matricula_legada_ativa_e_incluida(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');
        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-FREQ-4', ['turma_id' => $turma->id]);

        $chamada = $this->chamada($turma, $disciplina, $secretario);
        $frequencia = $this->frequencia($chamada, $matricula, $secretario);

        $this->assertDatabaseHas('frequencias', [
            'chamada_frequencia_id' => $chamada->id,
            'matricula_id' => $matricula->id,
        ]);

        // A chamada reconhece a matrícula legada como aluna ativa da turma.
        $this->assertTrue($chamada->alunosAtivos()->contains('id', $matricula->id));

        // O aluno consegue ver a própria frequência (policy).
        $this->actingAs($aluno);
        $this->assertTrue($aluno->can('view', $frequencia));
    }

    // 6. Vínculo pivot ativo é incluído e não duplica com o legado.
    public function test_matricula_pivot_ativa_e_incluida_sem_duplicar_legada(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');

        // Aluno com AMBOS os vínculos (legado + pivot) para a mesma turma.
        $alunoAmbos = $this->usuario('aluno');
        $matriculaAmbos = $this->matricula($alunoAmbos, $curso, 'MAT-FREQ-5', ['turma_id' => $turma->id]);
        app(VincularMatriculaTurmaService::class)->executar($matriculaAmbos, $turma);

        // Aluno apenas com pivot ativo.
        $alunoPivot = $this->usuario('aluno');
        $matriculaPivot = $this->matricula($alunoPivot, $curso, 'MAT-FREQ-6');
        app(VincularMatriculaTurmaService::class)->executar($matriculaPivot, $turma);

        $chamada = $this->chamada($turma, $disciplina, $secretario);
        $alunos = $chamada->alunosAtivos();

        $this->assertTrue($alunos->contains('id', $matriculaAmbos->id));
        $this->assertTrue($alunos->contains('id', $matriculaPivot->id));
        // Sem duplicar quem tem os dois vínculos.
        $this->assertSame(1, $alunos->where('id', $matriculaAmbos->id)->count());
    }

    // 8. Professor não vê turma alheia nem altera chamada; só lê as próprias.
    public function test_professor_nao_ve_turma_alheia_nem_altera_chamada(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $professor = $this->usuario('professor');
        $outroProfessor = $this->usuario('professor');
        $secretario = $this->usuario('secretario');

        $turmaDoProfessor = $this->turma($curso, ['professor_id' => $professor->id, 'nome' => 'Turma do prof']);
        $turmaAlheia = $this->turma($curso, ['professor_id' => $outroProfessor->id, 'nome' => 'Turma alheia']);

        $propria = $this->chamada($turmaDoProfessor, $disciplina, $secretario);
        $alheia = $this->chamada($turmaAlheia, $disciplina, $secretario);

        $this->actingAs($professor);

        $this->assertTrue($professor->can('view', $propria));
        $this->assertFalse($professor->can('view', $alheia));

        // Professor é somente-leitura nesta Issue.
        $this->assertFalse($professor->can('create', ChamadaFrequencia::class));
        $this->assertFalse($professor->can('update', $propria));
        $this->assertFalse($professor->can('delete', $propria));
        $this->assertFalse(ChamadaFrequenciaResource::canAccess());

        $visiveis = ChamadaFrequencia::query()->paraProfessor($professor->id)->pluck('id');
        $this->assertTrue($visiveis->contains($propria->id));
        $this->assertFalse($visiveis->contains($alheia->id));
    }

    // 9. Percentual conta só chamada concluída e trata falta justificada como presença.
    public function test_percentual_conta_somente_concluida_e_trata_falta_justificada(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');
        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-FREQ-7');
        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);

        $usuario = ['criada_por' => $secretario->id, 'concluida_por' => $secretario->id];

        // 3 concluídas: 1 presente, 1 falta, 1 falta justificada.
        $c1 = $this->chamada($turma, $disciplina, $secretario, ['status' => ChamadaFrequencia::STATUS_CONCLUIDA, 'data_aula' => '2026-08-01'] + $usuario);
        $c2 = $this->chamada($turma, $disciplina, $secretario, ['status' => ChamadaFrequencia::STATUS_CONCLUIDA, 'data_aula' => '2026-08-08'] + $usuario);
        $c3 = $this->chamada($turma, $disciplina, $secretario, ['status' => ChamadaFrequencia::STATUS_CONCLUIDA, 'data_aula' => '2026-08-15'] + $usuario);
        // 1 rascunho: NÃO deve entrar no cálculo.
        $c4 = $this->chamada($turma, $disciplina, $secretario, ['status' => ChamadaFrequencia::STATUS_RASCUNHO, 'data_aula' => '2026-08-22']);

        $this->frequencia($c1, $matricula, $secretario, Frequencia::SITUACAO_PRESENTE);
        $this->frequencia($c2, $matricula, $secretario, Frequencia::SITUACAO_FALTA);
        $this->frequencia($c3, $matricula, $secretario, Frequencia::SITUACAO_FALTA_JUSTIFICADA);
        $this->frequencia($c4, $matricula, $secretario, Frequencia::SITUACAO_PRESENTE);

        $resumo = $matricula->resumoFrequencia($turma->id, $disciplina->id);

        // presentes(1) + justificadas(1) = 2 de 3 concluídas => 66,67%.
        $this->assertSame(3, $resumo['chamadas_concluidas']);
        $this->assertSame(1, $resumo['presentes']);
        $this->assertSame(1, $resumo['faltas']);
        $this->assertSame(1, $resumo['faltas_justificadas']);
        $this->assertEqualsWithDelta(66.67, $resumo['percentual'], 0.01);

        // Via helper do model da chamada, mesmo resultado.
        $this->assertEqualsWithDelta(66.67, ChamadaFrequencia::percentualDaMatricula($matricula->id, $turma->id, $disciplina->id), 0.01);
    }

    // 9b. Sem chamada concluída, o percentual é 0 (não lança exceção).
    public function test_percentual_sem_chamada_concluida_retorna_zero(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');
        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-FREQ-8');
        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);

        $rascunho = $this->chamada($turma, $disciplina, $secretario);
        $this->frequencia($rascunho, $matricula, $secretario);

        $resumo = $matricula->resumoFrequencia($turma->id, $disciplina->id);

        $this->assertSame(0, $resumo['chamadas_concluidas']);
        $this->assertSame(0.0, $resumo['percentual']);
    }

    // 10. Migration e rollback das duas tabelas funcionam em SQLite.
    public function test_rollback_das_migrations_funciona_em_sqlite(): void
    {
        $this->assertTrue(Schema::hasTable('chamadas_frequencia'));
        $this->assertTrue(Schema::hasTable('frequencias'));

        $frequencias = require database_path('migrations/2026_08_13_100100_create_frequencias_table.php');
        $chamadas = require database_path('migrations/2026_08_13_100000_create_chamadas_frequencia_table.php');

        $frequencias->down();
        $chamadas->down();

        $this->assertFalse(Schema::hasTable('frequencias'));
        $this->assertFalse(Schema::hasTable('chamadas_frequencia'));

        $chamadas->up();
        $frequencias->up();

        $this->assertTrue(Schema::hasTable('chamadas_frequencia'));
        $this->assertTrue(Schema::hasTable('frequencias'));
    }
}
