<?php

namespace Tests\Unit;

use App\Support\TranscricaoVideo;
use PHPUnit\Framework\TestCase;

class TranscricaoVideoTest extends TestCase
{
    public function test_aula_longa_preserva_todos_os_trechos_ate_o_final(): void
    {
        $vtt = "WEBVTT\n\n";
        for ($i = 0; $i < 1219; $i++) {
            $inicio = gmdate('H:i:s', $i * 9).'.000';
            $fim = gmdate('H:i:s', $i * 9 + 8).'.000';
            $vtt .= "$inicio --> $fim\n".str_repeat('Conteúdo da aula. ', 9)."Final do trecho $i\n\n";
        }
        $this->assertGreaterThan(100000, mb_strlen($vtt));
        $r = TranscricaoVideo::preparar($vtt);
        $this->assertCount(1219, $r['cues']);
        $this->assertStringContainsString('Final do trecho 1218', $r['text']);
        $this->assertSame(10962.0, $r['cues'][1218]['start']);
    }

    public function test_conteudo_excessivo_avisa_em_vez_de_exibir_transcricao_cortada(): void
    {
        $r = TranscricaoVideo::preparar(str_repeat('á', 500001));
        $this->assertNotEmpty($r['warning'] ?? null);
        $this->assertSame('', $r['text']);
        $this->assertSame([], $r['cues']);
    }

    public function test_quantidade_excessiva_de_trechos_avisa_sem_exibir_versao_parcial(): void
    {
        $r = TranscricaoVideo::preparar("WEBVTT\n\n".str_repeat("00:00.000 --> 00:01.000\nTrecho\n\n", 5001));
        $this->assertNotEmpty($r['warning'] ?? null);
        $this->assertSame([], $r['cues']);
    }

    public function test_texto_sem_tempos_nao_inventa_timestamps(): void
    {
        $this->assertSame(['text' => 'Uma explicação real.', 'cues' => []], TranscricaoVideo::preparar('<b>Uma explicação real.</b>'));
        $this->assertSame(['text' => '', 'cues' => []], TranscricaoVideo::preparar(null));
    }

    public function test_webvtt_converte_tempos_e_remove_marcacao(): void
    {
        $vtt = "WEBVTT\r\n\r\nNOTE Observação editorial\r\nSem legenda aqui\r\n\r\n2\r\n01:02:03.500 --> 01:02:05.000 align:start\r\n<v Professor><b>Fé &amp; prática</b></v>\r\n\r\n00:01.000 --> 00:04.000\r\nPrimeiro trecho.";
        $r = TranscricaoVideo::preparar($vtt);
        $this->assertCount(2, $r['cues']);
        $this->assertSame(1.0, $r['cues'][0]['start']);
        $this->assertSame(3723.5, $r['cues'][1]['start']);
        $this->assertSame('Fé & prática', $r['cues'][1]['text']);
        $this->assertStringNotContainsString('Observação', $r['text']);
    }

    public function test_tempos_invertidos_invalidos_e_blocos_de_estilo_nao_viram_legendas(): void
    {
        $vtt = "WEBVTT\n\nSTYLE\n::cue {color:red}\n\n00:04.000 --> 00:01.000\nInvertida\n\n00:99.000 --> 01:40.000\nInválida\n\n00:01.000 --> 00:03.000\n<img src=x onerror=alert(1)>Trecho seguro";
        $r = TranscricaoVideo::preparar($vtt);
        $this->assertCount(1, $r['cues']);
        $this->assertSame('Trecho seguro', $r['text']);
    }
}
