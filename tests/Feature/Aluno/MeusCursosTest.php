<?php

namespace Tests\Feature\Aluno;

use App\Filament\Aluno\Pages\MeuCursoDetalhe;
use App\Filament\Aluno\Pages\MeusCursos;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\VincularMatriculaTurmaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MeusCursosTest extends TestCase
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

    public function test_aluno_ve_somente_suas_matriculas_ativas(): void
    {
        $curso = $this->curso();
        $aluno = $this->usuario('aluno');

        $ativa = $this->matricula($aluno, $curso, 'MAT-ALUNO-1');
        $this->matricula($aluno, $curso, 'MAT-ALUNO-2', ['status' => 'trancada']);
        $this->matricula($aluno, $curso, 'MAT-ALUNO-3', ['status' => 'cancelada']);

        $itens = MeusCursos::matriculasDoAluno($aluno);

        $this->assertCount(1, $itens);
        $this->assertSame($ativa->id, $itens->first()['id']);
        $this->assertSame('MAT-ALUNO-1', $itens->first()['numero_matricula']);
        $this->assertSame('Teologia', $itens->first()['curso_titulo']);
        $this->assertSame('Híbrida (online e presencial)', $itens->first()['modalidade_label']);
    }

    public function test_aluno_nao_ve_matricula_ativa_de_outro_aluno(): void
    {
        $curso = $this->curso();
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');

        $this->matricula($aluno, $curso, 'MAT-ALUNO-4');
        $deOutro = $this->matricula($outroAluno, $curso, 'MAT-ALUNO-5');

        $itens = MeusCursos::matriculasDoAluno($aluno);

        $this->assertCount(1, $itens);
        $this->assertNotContains($deOutro->id, $itens->pluck('id')->all());

        // Reforço na camada de autorização (MatriculaPolicy).
        $this->assertFalse($aluno->can('view', $deOutro));
    }

    public function test_aluno_nao_acessa_detalhe_de_matricula_de_outro_aluno(): void
    {
        $curso = $this->curso();
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');

        $propria = $this->matricula($aluno, $curso, 'MAT-ALUNO-6');
        $deOutro = $this->matricula($outroAluno, $curso, 'MAT-ALUNO-7');

        $this->actingAs($aluno);

        // ID conhecido de outro aluno: 403 seguro (MatriculaPolicy@view).
        $this->get(MeuCursoDetalhe::getUrl(['matricula' => $deOutro->id], panel: 'aluno'))
            ->assertForbidden();

        // ID inexistente: 404 seguro.
        $this->get(MeuCursoDetalhe::getUrl(['matricula' => 999999], panel: 'aluno'))
            ->assertNotFound();

        // A própria matrícula é acessível.
        $this->get(MeuCursoDetalhe::getUrl(['matricula' => $propria->id], panel: 'aluno'))
            ->assertOk();
    }

    public function test_matricula_com_turma_legada_aparece(): void
    {
        $curso = $this->curso();
        $aluno = $this->usuario('aluno');
        $turma = $this->turma($curso, ['nome' => 'Turma Legada 2026']);

        $matricula = $this->matricula($aluno, $curso, 'MAT-ALUNO-8', [
            'turma_id' => $turma->id,
        ]);

        $itens = MeusCursos::matriculasDoAluno($aluno);
        $item = $itens->firstWhere('id', $matricula->id);

        $this->assertNotNull($item);
        $this->assertTrue($item['possui_turma']);
        $this->assertCount(1, $item['turmas']);
        $this->assertSame($turma->id, $item['turmas'][0]['id']);
        $this->assertSame('Turma Legada 2026', $item['turmas'][0]['nome']);
    }

    public function test_matricula_com_vinculo_em_matricula_turma_aparece(): void
    {
        $curso = $this->curso();
        $aluno = $this->usuario('aluno');
        $turmaAtiva = $this->turma($curso, ['nome' => 'Turma Pivot Ativa']);
        $turmaTrancada = $this->turma($curso, ['nome' => 'Turma Pivot Trancada']);

        $matricula = $this->matricula($aluno, $curso, 'MAT-ALUNO-9');

        app(VincularMatriculaTurmaService::class)->executar($matricula, $turmaAtiva);
        app(VincularMatriculaTurmaService::class)->executar(
            $matricula,
            $turmaTrancada,
            MatriculaTurma::STATUS_TRANCADO,
        );

        $itens = MeusCursos::matriculasDoAluno($aluno);
        $item = $itens->firstWhere('id', $matricula->id);

        $this->assertNotNull($item);
        $this->assertTrue($item['possui_turma']);

        // Somente o vínculo com status ativo aparece.
        $this->assertCount(1, $item['turmas']);
        $this->assertSame($turmaAtiva->id, $item['turmas'][0]['id']);
    }

    public function test_vinculo_legado_e_pivot_para_a_mesma_turma_nao_duplicam(): void
    {
        $curso = $this->curso();
        $aluno = $this->usuario('aluno');
        $turma = $this->turma($curso, ['nome' => 'Turma Única']);

        $matricula = $this->matricula($aluno, $curso, 'MAT-ALUNO-10', [
            'turma_id' => $turma->id,
        ]);

        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);

        $itens = MeusCursos::matriculasDoAluno($aluno);
        $item = $itens->firstWhere('id', $matricula->id);

        $this->assertNotNull($item);
        $this->assertCount(1, $item['turmas']);
        $this->assertSame($turma->id, $item['turmas'][0]['id']);
    }

    public function test_matricula_sem_turma_nao_lanca_excecao_e_mostra_estado_esperado(): void
    {
        $curso = $this->curso();
        $aluno = $this->usuario('aluno');

        $matricula = $this->matricula($aluno, $curso, 'MAT-ALUNO-11');

        $itens = MeusCursos::matriculasDoAluno($aluno);
        $item = $itens->firstWhere('id', $matricula->id);

        $this->assertNotNull($item);
        $this->assertFalse($item['possui_turma']);
        $this->assertSame([], $item['turmas']);
        $this->assertSame('Teologia', $item['curso_titulo']);
    }

    public function test_consulta_da_pagina_nao_altera_tabelas_academicas(): void
    {
        $curso = $this->curso();
        $aluno = $this->usuario('aluno');
        $turma = $this->turma($curso);
        $matricula = $this->matricula($aluno, $curso, 'MAT-ALUNO-12', ['turma_id' => $turma->id]);
        app(VincularMatriculaTurmaService::class)->executar($matricula, $this->turma($curso, ['nome' => 'Turma B']));

        $tabelas = ['matriculas', 'matricula_turma', 'turmas', 'cursos', 'periodo_letivos'];

        $antes = collect($tabelas)->mapWithKeys(
            fn (string $tabela) => [$tabela => DB::table($tabela)->count()]
        );

        MeusCursos::matriculasDoAluno($aluno);
        MeusCursos::matriculasDoAluno($aluno, 'teologia');

        $depois = collect($tabelas)->mapWithKeys(
            fn (string $tabela) => [$tabela => DB::table($tabela)->count()]
        );

        $this->assertSame($antes->all(), $depois->all());
    }
}
