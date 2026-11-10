<?php

namespace Tests\Feature\Admin;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Modulo;
use App\Models\Turma;
use Database\Seeders\AulaTesteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AulaDisciplinaTest extends TestCase
{
    use RefreshDatabase;

    private int $sequenciaDisciplina = 1;

    private function disciplina(
        Curso $curso,
        Modulo $modulo,
        string $nome,
        ?string $codigo = null,
    ): Disciplina {
        $sequencia = $this->sequenciaDisciplina++;

        return Disciplina::create([
            'curso_id' => $curso->id,
            'modulo_id' => $modulo->id,
            'codigo' => $codigo ?? sprintf('DISC-%03d', $sequencia),
            'nome' => $nome,
            'carga_horaria' => 40,
            'ordem' => $sequencia,
            'ativa' => true,
        ]);
    }

    public function test_aula_herda_modulo_da_disciplina_no_servidor(): void
    {
        $curso = Curso::create(['titulo' => 'Curso H', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'M1', 'ordem' => 1]);
        $disciplina = $this->disciplina($curso, $modulo, 'D1');

        $aula = Aula::create(['disciplina_id' => $disciplina->id, 'titulo' => 'A1', 'ordem' => 1]);

        $this->assertSame($modulo->id, $aula->modulo_id);
    }

    public function test_disciplina_inexistente_e_rejeitada(): void
    {
        $this->expectException(ValidationException::class);

        Aula::create(['disciplina_id' => 999999, 'titulo' => 'Fantasma', 'ordem' => 1]);
    }

    public function test_disciplina_legada_sem_modulo_nao_permite_nova_aula(): void
    {
        $curso = Curso::create(['titulo' => 'Curso S', 'ativo' => true]);

        $disciplinaId = DB::table('disciplinas')->insertGetId([
            'curso_id' => $curso->id,
            'modulo_id' => null,
            'codigo' => 'LEG-001',
            'nome' => 'Avulsa legada',
            'carga_horaria' => 40,
            'ordem' => 1,
            'ativa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        Aula::create(['disciplina_id' => $disciplinaId, 'titulo' => 'Livre', 'ordem' => 1]);
    }

    public function test_disciplina_com_aulas_nao_pode_ser_excluida(): void
    {
        $curso = Curso::create(['titulo' => 'Curso P', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'M', 'ordem' => 1]);
        $disciplina = $this->disciplina($curso, $modulo, 'Protegida');
        Aula::create(['disciplina_id' => $disciplina->id, 'titulo' => 'Presa', 'ordem' => 1]);

        $this->expectException(ValidationException::class);

        $disciplina->delete();
    }

    public function test_codigo_duplicado_no_mesmo_curso_e_rejeitado(): void
    {
        $curso = Curso::create(['titulo' => 'Curso C', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo C', 'ordem' => 1]);
        $this->disciplina($curso, $modulo, 'A', 'TEO101');

        $this->expectException(ValidationException::class);

        $this->disciplina($curso, $modulo, 'B', 'TEO101');
    }

    public function test_mesmo_codigo_em_cursos_diferentes_e_permitido(): void
    {
        $cursoA = Curso::create(['titulo' => 'A', 'ativo' => true]);
        $cursoB = Curso::create(['titulo' => 'B', 'ativo' => true]);
        $moduloA = Modulo::create(['curso_id' => $cursoA->id, 'titulo' => 'Módulo A', 'ordem' => 1]);
        $moduloB = Modulo::create(['curso_id' => $cursoB->id, 'titulo' => 'Módulo B', 'ordem' => 1]);

        $this->disciplina($cursoA, $moduloA, 'A', 'TEO101');
        $d = $this->disciplina($cursoB, $moduloB, 'B', 'TEO101');

        $this->assertNotNull($d->id);
    }

    public function test_disciplina_com_aula_nao_pode_ser_movida_para_outro_modulo(): void
    {
        $curso = Curso::create(['titulo' => 'Curso Movimento', 'ativo' => true]);
        $moduloOrigem = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Origem', 'ordem' => 1]);
        $moduloDestino = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Destino', 'ordem' => 2]);
        $disciplina = $this->disciplina($curso, $moduloOrigem, 'Imóvel');
        Aula::create(['disciplina_id' => $disciplina->id, 'titulo' => 'Aula vinculada', 'ordem' => 1]);

        $disciplina->modulo_id = $moduloDestino->id;

        $this->expectException(ValidationException::class);

        $disciplina->save();
    }

    public function test_disciplina_legada_sem_modulo_com_turma_nao_pode_mudar_de_curso(): void
    {
        $cursoOrigem = Curso::create(['titulo' => 'Curso legado origem', 'ativo' => true]);
        $cursoDestino = Curso::create(['titulo' => 'Curso legado destino', 'ativo' => true]);
        $disciplinaId = DB::table('disciplinas')->insertGetId([
            'curso_id' => $cursoOrigem->id,
            'modulo_id' => null,
            'codigo' => 'LEG-TURMA-001',
            'nome' => 'Disciplina legada com turma',
            'carga_horaria' => 40,
            'ordem' => 1,
            'ativa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $disciplina = Disciplina::query()->findOrFail($disciplinaId);

        Turma::create([
            'curso_id' => $cursoOrigem->id,
            'disciplina_id' => $disciplina->id,
            'nome' => 'Turma da disciplina legada',
            'ano_letivo' => 2026,
            'valor_total_curso' => 1200,
            'qtd_parcelas' => 12,
            'status' => Turma::STATUS_PLANEJADA,
            'ativa' => true,
        ]);

        $disciplina->curso_id = $cursoDestino->id;

        $this->expectException(ValidationException::class);

        $disciplina->save();
    }

    public function test_modulo_com_disciplina_nao_pode_ser_movido_para_outro_curso(): void
    {
        $cursoOrigem = Curso::create(['titulo' => 'Curso Origem', 'ativo' => true]);
        $cursoDestino = Curso::create(['titulo' => 'Curso Destino', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $cursoOrigem->id, 'titulo' => 'Módulo com conteúdo', 'ordem' => 1]);
        $this->disciplina($cursoOrigem, $modulo, 'Disciplina vinculada');

        $modulo->curso_id = $cursoDestino->id;

        $this->expectException(ValidationException::class);

        $modulo->save();
    }

    public function test_modulo_com_disciplina_nao_pode_ser_excluido(): void
    {
        $curso = Curso::create(['titulo' => 'Curso Exclusão', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo protegido', 'ordem' => 1]);
        $this->disciplina($curso, $modulo, 'Disciplina vinculada');

        $this->expectException(ValidationException::class);

        $modulo->delete();
    }

    public function test_aula_teste_seeder_cria_disciplina_valida_e_e_idempotente(): void
    {
        $this->seed(AulaTesteSeeder::class);
        $this->seed(AulaTesteSeeder::class);

        $disciplina = Disciplina::query()
            ->where('codigo', 'DEMO-PLAT-001')
            ->firstOrFail();
        $aula = Aula::query()
            ->where('disciplina_id', $disciplina->id)
            ->where('titulo', 'Aula 01 - Demonstração Completa (Áudio, Vídeo, PDF e Quiz)')
            ->firstOrFail();

        $this->assertNotNull($disciplina->modulo_id);
        $this->assertSame($disciplina->modulo_id, $aula->modulo_id);
        $this->assertSame(1, Disciplina::query()->where('codigo', 'DEMO-PLAT-001')->count());
        $this->assertSame(1, Aula::query()->where('disciplina_id', $disciplina->id)->count());
    }
}
