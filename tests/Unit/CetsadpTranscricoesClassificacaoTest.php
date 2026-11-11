<?php

namespace Tests\Unit;

use Database\Seeders\VincularTranscricoesTeologia2026Seeder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CetsadpTranscricoesClassificacaoTest extends TestCase
{
    public function test_tres_videoaulas_sao_vinculadas_as_disciplinas_corretas(): void
    {
        $r = new ReflectionClass(VincularTranscricoesTeologia2026Seeder::class);
        $mapa = $r->getReflectionConstant('MAPEAMENTO')->getValue();

        $this->assertCount(3, $mapa);
        $porVideo = array_column($mapa, null, 'video');

        $this->assertSame('TEO-M02-12', $porVideo['NOsvbWaaaus']['codigo']);
        $this->assertSame(2, $porVideo['NOsvbWaaaus']['ordem']);

        $this->assertSame('TEO-M01-01', $porVideo['5Osxg5CvQ2w']['codigo']);
        $this->assertSame('TEO-M01-02', $porVideo['pQ8aokW5-Ls']['codigo']);
        $this->assertSame(1, $porVideo['5Osxg5CvQ2w']['ordem']);
        $this->assertSame(1, $porVideo['pQ8aokW5-Ls']['ordem']);

        foreach ($mapa as $entrada) {
            $file = dirname(__DIR__, 2).'/database/seeders/conteudos/teologia/'.$entrada['conteudo'];
            $this->assertFileExists($file);
            $html = (string) file_get_contents($file);
            $this->assertStringContainsString($entrada['video'], $html);
            $this->assertStringNotContainsString('<script', strtolower($html));
        }
    }
}
