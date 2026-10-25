<?php

namespace App\Support;

class TranscricaoVideo
{
    /** Transcrição editorial em texto simples ou WebVTT, sem buscar fontes externas. */
    public static function preparar(?string $conteudo): array
    {
        $conteudo = trim(mb_substr($conteudo ?? '', 0, 100000));
        if (! preg_match('/^\x{FEFF}?WEBVTT(?:\s|$)/u', $conteudo)) {
            return ['text' => self::texto($conteudo), 'cues' => []];
        }

        $trechos = [];
        foreach (preg_split('/\n\s*\n/', str_replace("\r", '', $conteudo)) as $bloco) {
            if (preg_match('/^(?:WEBVTT|NOTE|STYLE|REGION)(?:\s|$)/', $bloco)) {
                continue;
            }
            $linhas = explode("\n", $bloco);
            if (! str_contains($linhas[0], '-->')) {
                array_shift($linhas);
            }
            $tempos = array_shift($linhas) ?? '';
            if (! preg_match('/^((?:\d{2,}:)?\d{2}:\d{2}\.\d{3})\s+-->\s+((?:\d{2,}:)?\d{2}:\d{2}\.\d{3})(?:\s.*)?$/', $tempos, $m)) {
                continue;
            }
            $inicio = self::segundos($m[1]);
            $fim = self::segundos($m[2]);
            $texto = self::texto(implode("\n", $linhas));
            if ($inicio !== null && $fim !== null && $fim > $inicio && $texto !== '') {
                $trechos[] = ['start' => $inicio, 'end' => $fim, 'text' => $texto];
            }
            if (count($trechos) >= 2000) {
                break;
            }
        }
        usort($trechos, fn ($a, $b) => $a['start'] <=> $b['start']);

        return ['text' => implode("\n\n", array_column($trechos, 'text')), 'cues' => $trechos];
    }

    private static function texto(string $valor): string
    {
        return trim(html_entity_decode(strip_tags($valor), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function segundos(string $valor): ?float
    {
        $partes = array_map('floatval', explode(':', $valor));
        $s = array_pop($partes);
        $m = array_pop($partes);
        if ($s >= 60 || $m >= 60) {
            return null;
        }

        return (array_pop($partes) ?? 0) * 3600 + $m * 60 + $s;
    }
}
