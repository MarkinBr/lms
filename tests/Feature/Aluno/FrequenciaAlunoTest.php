<?php

namespace Tests\Feature\Aluno;

use App\Filament\Aluno\Pages\MeuCursoDetalhe;
use App\Models\ChamadaFrequencia;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Frequencia;
use App\Models\Matricula;
use App\Models\Turma;
use App\Models\User;
use App\Services\VincularMatriculaTurmaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Issue #32 — visão do aluno sobre a própria frequência (somente leitura).
 */
class FrequenciaAlunoTest extends TestCase
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
        return Curso::query()->create(['titulo' => $titulo, 'ativo' => true]);
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

    private function chamadaConcluida(Turma $turma, Disciplina $disciplina, User $secretario, string $data): ChamadaFrequencia
    {
        return ChamadaFrequencia::query()->create([
            'turma_id' => $turma->id,
            'disciplina_id' => $disciplina->id,
            'data_aula' => $data,
            'status' => ChamadaFrequencia::STATUS_CONCLUIDA,
            'criada_por' => $secretario->id,
            'concluida_por' => $secretario->id,
        ]);
    }

    private function frequencia(ChamadaFrequencia $chamada, Matricula $matricula, User $secretario, string $situacao): Frequencia
    {
        return Frequencia::query()->create([
            'chamada_frequencia_id' => $chamada->id,
            'matricula_id' => $matricula->id,
            'situacao' => $situacao,
            'registrado_por' => $secretario->id,
        ]);
    }

    // 7. Aluno não consulta frequência de outro aluno.
    public function test_aluno_nao_consulta_frequencia_de_outro_aluno(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');

        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');

        $matricula = $this->matricula($aluno, $curso, 'MAT-FREQ-A1');
        $matriculaOutro = $this->matricula($outroAluno, $curso, 'MAT-FREQ-A2');

        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);
        app(VincularMatriculaTurmaService::class)->executar($matriculaOutro, $turma);

        $chamada = $this->chamadaConcluida($turma, $disciplina, $secretario, '2026-08-01');
        $propria = $this->frequencia($chamada, $matricula, $secretario, Frequencia::SITUACAO_PRESENTE);
        $deOutro = $this->frequencia($chamada, $matriculaOutro, $secretario, Frequencia::SITUACAO_FALTA);

        $this->actingAs($aluno);

        $this->assertTrue($aluno->can('view', $propria));
        $this->assertFalse($aluno->can('view', $deOutro));
    }

    // O detalhe da matrícula mostra somente a frequência do próprio aluno.
    public function test_detalhe_da_matricula_mostra_somente_a_propria_frequencia(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso, ['nome' => 'Turma Única']);
        $secretario = $this->usuario('secretario');

        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');

        $matricula = $this->matricula($aluno, $curso, 'MAT-FREQ-A3');
        $matriculaOutro = $this->matricula($outroAluno, $curso, 'MAT-FREQ-A4');

        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);
        app(VincularMatriculaTurmaService::class)->executar($matriculaOutro, $turma);

        $c1 = $this->chamadaConcluida($turma, $disciplina, $secretario, '2026-08-01');
        $c2 = $this->chamadaConcluida($turma, $disciplina, $secretario, '2026-08-08');

        // Aluno: 1 presente + 1 falta justificada => 100% (2/2).
        $this->frequencia($c1, $matricula, $secretario, Frequencia::SITUACAO_PRESENTE);
        $this->frequencia($c2, $matricula, $secretario, Frequencia::SITUACAO_FALTA_JUSTIFICADA);

        // Outro aluno: 2 faltas => 0%. Não deve contaminar o resumo do primeiro.
        $this->frequencia($c1, $matriculaOutro, $secretario, Frequencia::SITUACAO_FALTA);
        $this->frequencia($c2, $matriculaOutro, $secretario, Frequencia::SITUACAO_FALTA);

        $frequencias = MeuCursoDetalhe::frequenciasDaMatricula($matricula);

        $this->assertCount(1, $frequencias);
        $item = $frequencias->first();
        $this->assertSame('Hermenêutica', $item['disciplina']);
        $this->assertSame('Turma Única', $item['turma']);
        $this->assertSame(2, $item['chamadas_concluidas']);
        $this->assertSame(1, $item['presentes']);
        $this->assertSame(0, $item['faltas']);
        $this->assertSame(1, $item['faltas_justificadas']);
        $this->assertEqualsWithDelta(100.0, $item['percentual'], 0.01);

        // O resumo do outro aluno é independente (0%).
        $outroResumo = MeuCursoDetalhe::frequenciasDaMatricula($matriculaOutro)->first();
        $this->assertEqualsWithDelta(0.0, $outroResumo['percentual'], 0.01);
    }

    // Estado vazio claro quando não há chamada concluída.
    public function test_aluno_sem_chamada_concluida_ve_estado_vazio(): void
    {
        $curso = $this->curso();
        $aluno = $this->usuario('aluno');
        $turma = $this->turma($curso);
        $matricula = $this->matricula($aluno, $curso, 'MAT-FREQ-A5');
        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);

        $frequencias = MeuCursoDetalhe::frequenciasDaMatricula($matricula);

        $this->assertTrue($frequencias->isEmpty());
    }

    // A consulta da área do aluno não altera tabelas de frequência.
    public function test_consulta_de_frequencia_do_aluno_nao_altera_dados(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso);
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');
        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-FREQ-A6');
        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);

        $chamada = $this->chamadaConcluida($turma, $disciplina, $secretario, '2026-08-01');
        $this->frequencia($chamada, $matricula, $secretario, Frequencia::SITUACAO_PRESENTE);

        $chamadasAntes = DB::table('chamadas_frequencia')->count();
        $frequenciasAntes = DB::table('frequencias')->count();

        MeuCursoDetalhe::frequenciasDaMatricula($matricula);
        $matricula->resumoFrequencia($turma->id, $disciplina->id);

        $this->assertSame($chamadasAntes, DB::table('chamadas_frequencia')->count());
        $this->assertSame($frequenciasAntes, DB::table('frequencias')->count());
    }
}
