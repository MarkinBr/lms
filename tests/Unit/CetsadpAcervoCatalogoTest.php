<?php

namespace Tests\Unit;

use Database\Seeders\MatrizCurricularTeologiaSeeder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CetsadpAcervoCatalogoTest extends TestCase
{
    private function catalogo(): array
    {
        $arquivo = dirname(__DIR__, 2).'/database/seeders/dados/cetsadp-videos-materiais-20261111.json';
        return json_decode((string) file_get_contents($arquivo), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_catalogo_tem_quantidades_e_urls_coerentes(): void
    {
        $dados = $this->catalogo();
        $this->assertCount(17, $dados['videos']);
        $this->assertCount(12, $dados['materiais']);

        $ids = array_column($dados['videos'], 'id');
        $this->assertCount(17, array_unique($ids));

        foreach ($dados['videos'] as $video) {
            $this->assertMatchesRegularExpression(
                '~^https://www\\.youtube\\.com/watch\\?v=[a-zA-Z0-9_-]{11}$~',
                $video['url'],
            );
        }

        $materiaisValidos = array_values(array_filter(
            $dados['materiais'],
            fn ($item): bool => $item['classificacao'] !== 'invalido',
        ));
        $this->assertCount(11, $materiaisValidos);
        foreach ($materiaisValidos as $material) {
            $this->assertSame('https', parse_url($material['url'], PHP_URL_SCHEME));
        }
    }

    public function test_catalogo_nao_inventa_vinculos_a_disciplinas(): void
    {
        $dados = $this->catalogo();
        $videos = collect($dados['videos']);

        $this->assertCount(7, $videos->where('curso', 'Curso de Habilitação ao Diaconato'));
        $this->assertCount(6, $videos->where('curso', 'Liderança Cristã Ministerial'));
        $this->assertCount(4, $videos->where('curso', 'Médio em Teologia'));
        $this->assertSame(
            'TEO-M02-12',
            $videos->firstWhere('id', '20')['disciplina_codigo'],
        );
        $this->assertCount(1, $videos->filter(fn ($item) => filled($item['disciplina_codigo'])));

        $this->assertCount(1, collect($dados['materiais'])->where('classificacao', 'pendente'));
        $this->assertCount(1, collect($dados['materiais'])->where('classificacao', 'invalido'));
    }

    public function test_matriz_confirmada_tem_judaica_no_segundo_periodo(): void
    {
        $matriz = (new ReflectionClass(MatrizCurricularTeologiaSeeder::class))
            ->getReflectionConstant('MATRIZ')
            ->getValue();

        $disciplinas = collect($matriz)->flatMap(fn ($modulo) => $modulo['disciplinas']);
        $this->assertCount(40, $disciplinas);
        $this->assertSame(960, $disciplinas->sum(fn ($disciplina) => $disciplina[2]));
        $this->assertTrue(collect($matriz[2]['disciplinas'])->contains(
            fn ($disciplina): bool => $disciplina[1] === 'História e Cultura Judaica'
                && $disciplina[2] === 16,
        ));
    }
}
