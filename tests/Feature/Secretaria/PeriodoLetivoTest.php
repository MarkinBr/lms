<?php

namespace Tests\Feature\Secretaria;

use App\Models\Curso;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PeriodoLetivoTest extends TestCase
{
    use RefreshDatabase;

    private function periodo(array $override = []): PeriodoLetivo
    {
        return PeriodoLetivo::query()->create(array_merge([
            'nome' => '2026.1',
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-06-30',
            'ativo' => false,
        ], $override));
    }

    private function curso(): Curso
    {
        return Curso::query()->create([
            'titulo' => 'Teologia',
            'ativo' => true,
        ]);
    }

    private function turma(PeriodoLetivo $periodo, Curso $curso): Turma
    {
        return Turma::query()->create([
            'curso_id' => $curso->id,
            'periodo_letivo_id' => $periodo->id,
            'nome' => 'Turma teste',
            'ano_letivo' => 2026,
            'valor_total_curso' => 1200,
            'qtd_parcelas' => 12,
            'status' => Turma::STATUS_PLANEJADA,
            'ativa' => true,
        ]);
    }

    public function test_cria_periodo_valido(): void
    {
        $periodo = $this->periodo();

        $this->assertDatabaseHas('periodo_letivos', [
            'id' => $periodo->id,
            'nome' => '2026.1',
        ]);
    }

    public function test_data_fim_anterior_data_inicio_falha(): void
    {
        $this->expectException(ValidationException::class);

        $this->periodo([
            'data_inicio' => '2026-02-01',
            'data_fim' => '2026-01-01',
        ]);
    }

    public function test_apenas_um_periodo_ativo(): void
    {
        $this->periodo(['ativo' => true]);

        $this->expectException(ValidationException::class);

        $this->periodo([
            'nome' => '2026.2',
            'ativo' => true,
        ]);
    }

    public function test_nao_exclui_periodo_com_turmas(): void
    {
        $periodo = $this->periodo();
        $curso = $this->curso();
        $this->turma($periodo, $curso);

        $this->expectException(ValidationException::class);

        $periodo->delete();
    }

    public function test_exclui_periodo_sem_turmas(): void
    {
        $periodo = $this->periodo();

        $periodo->delete();

        $this->assertDatabaseMissing('periodo_letivos', [
            'id' => $periodo->id,
        ]);
    }
}
