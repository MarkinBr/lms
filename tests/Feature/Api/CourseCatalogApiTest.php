<?php

namespace Tests\Feature\Api;

use App\Models\CourseModality;
use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_apenas_cursos_e_modalidades_ativos_em_ordem_alfabetica(): void
    {
        $bacharelado = Curso::query()->create([
            'titulo' => 'Bacharelado em Teologia',
            'ativo' => true,
        ]);
        $ead = CourseModality::query()->create([
            'curso_id' => $bacharelado->id,
            'nome' => 'EAD',
            'ativo' => true,
        ]);
        $presencialBacharelado = CourseModality::query()->create([
            'curso_id' => $bacharelado->id,
            'nome' => 'Presencial',
            'ativo' => true,
        ]);
        $cursoMedio = Curso::query()->create([
            'titulo' => 'Curso Médio em Teologia',
            'ativo' => true,
        ]);
        $presencialMedio = CourseModality::query()->create([
            'curso_id' => $cursoMedio->id,
            'nome' => 'Presencial',
            'ativo' => true,
        ]);
        CourseModality::query()->create([
            'curso_id' => $cursoMedio->id,
            'nome' => 'Semipresencial',
            'ativo' => false,
        ]);
        $cursoInativo = Curso::query()->create([
            'titulo' => 'Curso Descontinuado',
            'ativo' => false,
        ]);
        CourseModality::query()->create([
            'curso_id' => $cursoInativo->id,
            'nome' => 'EAD',
            'ativo' => true,
        ]);

        $response = $this->getJson('/api/v1/cursos');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $bacharelado->id)
            ->assertJsonPath('data.0.modalidades.0.id', $ead->id)
            ->assertJsonPath('data.0.modalidades.1.id', $presencialBacharelado->id)
            ->assertJsonPath('data.1.id', $cursoMedio->id)
            ->assertJsonPath('data.1.modalidades.0.id', $presencialMedio->id)
            ->assertJsonMissing(['titulo' => 'Curso Descontinuado'])
            ->assertJsonMissing(['nome' => 'Semipresencial']);
    }

    public function test_nao_expoe_campos_financeiros_ou_administrativos(): void
    {
        $curso = Curso::query()->create([
            'titulo' => 'Curso Médio em Teologia',
            'valor' => 199.90,
            'ativo' => true,
        ]);
        CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'Presencial',
            'valor_total' => 1999.90,
            'numero_parcelas' => 12,
            'ativo' => true,
        ]);

        $response = $this->getJson('/api/v1/cursos')->assertOk();

        $this->assertEqualsCanonicalizing(
            ['id', 'titulo', 'modalidades'],
            array_keys($response->json('data.0')),
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'nome'],
            array_keys($response->json('data.0.modalidades.0')),
        );
    }

    public function test_retorna_lista_vazia_quando_nao_ha_curso_ativo(): void
    {
        Curso::query()->create(['titulo' => 'Curso Inativo', 'ativo' => false]);

        $this->getJson('/api/v1/cursos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }
}
