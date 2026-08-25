<?php

namespace Tests\Feature\Secretaria;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Modulo;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use Database\Seeders\MatrizCurricularTeologiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MatrizCurricularTeologiaSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedMatriz(): Curso
    {
        $this->seed(MatrizCurricularTeologiaSeeder::class);

        return Curso::query()->where('titulo', MatrizCurricularTeologiaSeeder::CURSO_TITULO)->firstOrFail();
    }

    public function test_cria_o_curso_seis_modulos_e_quarenta_disciplinas(): void
    {
        $curso = $this->seedMatriz();

        $this->assertSame(1, Curso::query()->where('titulo', MatrizCurricularTeologiaSeeder::CURSO_TITULO)->count());
        $this->assertSame(6, Modulo::query()->where('curso_id', $curso->id)->count());
        $this->assertSame(40, Disciplina::query()->where('curso_id', $curso->id)->count());
    }

    public function test_cada_modulo_soma_cento_e_sessenta_horas_e_curso_soma_novecentas_e_sessenta(): void
    {
        $curso = $this->seedMatriz();

        for ($ordem = 1; $ordem <= 6; $ordem++) {
            $modulo = Modulo::query()
                ->where('curso_id', $curso->id)
                ->where('ordem', $ordem)
                ->firstOrFail();

            $total = (int) Disciplina::query()
                ->where('modulo_id', $modulo->id)
                ->sum('carga_horaria');

            $this->assertSame(
                160,
                $total,
                "O módulo {$ordem} deveria totalizar 160 horas."
            );
        }

        $totalCurso = (int) Disciplina::query()
            ->where('curso_id', $curso->id)
            ->sum('carga_horaria');

        $this->assertSame(960, $totalCurso);
    }

    public function test_ordem_global_das_disciplinas_e_um_a_quarenta(): void
    {
        $curso = $this->seedMatriz();

        $ordens = Disciplina::query()
            ->where('curso_id', $curso->id)
            ->orderBy('ordem')
            ->pluck('ordem')
            ->all();

        $this->assertSame(range(1, 40), array_map('intval', $ordens));
    }

    public function test_todas_as_disciplinas_pertencem_ao_curso_e_a_um_modulo_do_mesmo_curso(): void
    {
        $curso = $this->seedMatriz();

        $disciplinas = Disciplina::query()
            ->where('curso_id', $curso->id)
            ->with('modulo')
            ->get();

        $this->assertCount(40, $disciplinas);

        foreach ($disciplinas as $disciplina) {
            $this->assertNotNull($disciplina->modulo_id, "Disciplina {$disciplina->codigo} deve estar vinculada a um módulo.");
            $this->assertNotNull($disciplina->modulo, "Relacionamento modulo() da disciplina {$disciplina->codigo} deve resolver.");
            $this->assertSame((int) $curso->id, (int) $disciplina->modulo->curso_id, "Módulo da disciplina {$disciplina->codigo} deve pertencer ao mesmo curso.");
        }
    }

    public function test_codigos_estaveis_seguem_o_padrao_teo_m_xx_yy(): void
    {
        $curso = $this->seedMatriz();

        $codigos = Disciplina::query()
            ->where('curso_id', $curso->id)
            ->orderBy('ordem')
            ->pluck('codigo')
            ->all();

        $this->assertSame('TEO-M01-01', $codigos[0]);
        $this->assertSame('TEO-M06-40', end($codigos));

        foreach ($codigos as $codigo) {
            $this->assertMatchesRegularExpression('/^TEO-M0[1-6]-\d{2}$/', $codigo);
        }
    }

    public function test_descricao_das_disciplinas_permanece_nula(): void
    {
        $curso = $this->seedMatriz();

        $comDescricao = Disciplina::query()
            ->where('curso_id', $curso->id)
            ->whereNotNull('descricao')
            ->count();

        $this->assertSame(0, $comDescricao, 'O seeder não deve inventar ementas para as disciplinas.');
    }

    public function test_execucao_repetida_e_idempotente(): void
    {
        $curso = $this->seedMatriz();

        $modulosAntes = Modulo::query()->where('curso_id', $curso->id)->count();
        $disciplinasAntes = Disciplina::query()->where('curso_id', $curso->id)->count();

        $this->seed(MatrizCurricularTeologiaSeeder::class);
        $this->seed(MatrizCurricularTeologiaSeeder::class);

        $this->assertSame(1, Curso::query()->where('titulo', MatrizCurricularTeologiaSeeder::CURSO_TITULO)->count());
        $this->assertSame($modulosAntes, Modulo::query()->where('curso_id', $curso->id)->count());
        $this->assertSame($disciplinasAntes, Disciplina::query()->where('curso_id', $curso->id)->count());
    }

    public function test_seeder_nao_cria_periodos_letivos_nem_turmas(): void
    {
        $this->seedMatriz();

        $this->assertSame(0, PeriodoLetivo::query()->count(), 'A matriz curricular não deve criar períodos letivos.');
        $this->assertSame(0, Turma::query()->count(), 'A matriz curricular não deve criar turmas.');
    }

    public function test_seeder_nao_altera_curso_existente_com_o_mesmo_titulo(): void
    {
        $cursoExistente = Curso::query()->create([
            'titulo' => MatrizCurricularTeologiaSeeder::CURSO_TITULO,
            'descricao' => 'Descrição original preservada',
            'ativo' => true,
        ]);

        $this->seed(MatrizCurricularTeologiaSeeder::class);

        $cursoExistente->refresh();

        $this->assertSame('Descrição original preservada', $cursoExistente->descricao);
        $this->assertSame(1, Curso::query()->where('titulo', MatrizCurricularTeologiaSeeder::CURSO_TITULO)->count());
        $this->assertSame(6, Modulo::query()->where('curso_id', $cursoExistente->id)->count());
        $this->assertSame(40, Disciplina::query()->where('curso_id', $cursoExistente->id)->count());
    }

    public function test_nova_disciplina_sem_modulo_e_rejeitada(): void
    {
        $curso = Curso::query()->create([
            'titulo' => 'Curso Legado',
            'ativo' => true,
        ]);

        $this->expectException(ValidationException::class);

        Disciplina::query()->create([
            'curso_id' => $curso->id,
            'codigo' => 'LEG-01',
            'nome' => 'Disciplina Legada Sem Módulo',
            'carga_horaria' => 20,
            'ordem' => 1,
            'ativa' => true,
        ]);
    }

    public function test_modulo_com_disciplinas_nao_pode_ser_apagado(): void
    {
        $curso = $this->seedMatriz();

        $modulo = Modulo::query()
            ->where('curso_id', $curso->id)
            ->where('ordem', 1)
            ->firstOrFail();

        $this->assertTrue(Disciplina::query()->where('modulo_id', $modulo->id)->exists());
        $this->expectException(ValidationException::class);

        $modulo->delete();
    }
}
