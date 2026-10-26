<?php

namespace App\Services\Telegram;

use App\Models\Application;
use App\Models\Aula;
use App\Models\AulaAoVivo;
use App\Models\Matricula;

class ResumoAvisosTelegram
{
    /**
     * Só emite indicadores agregados; não inclui candidatos, alunos, contato ou documentos.
     */
    public function texto(string $tipo): string
    {
        if ($tipo === 'diario') {
            $inicio = now()->subDay();
            $inscricoes = Application::query()->whereBetween('created_at', [$inicio, now()])->count();
            $pendencias = Application::query()->emAnalise()->count();
            $aulas = Aula::query()->where('liberada', false)->count();
            $fim = now()->addDay();
            $proximos = AulaAoVivo::query()
                ->whereBetween('data_hora', [now(), $fim])
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', AulaAoVivo::STATUS_CANCELADA);
                })->count();

            return "CETSADP • Aviso diário\n"
                ."Novas inscrições nas últimas 24h: {$inscricoes}\n"
                ."Inscrições em análise: {$pendencias}\n"
                ."Aulas não liberadas: {$aulas}\n"
                ."Encontros previstos nas próximas 24h: {$proximos}\n"
                .'Sem dados pessoais. Consulte detalhes no painel autenticado.';
        }

        $inicio = now()->subDays(7);
        $fim = now();
        $inscricoes = Application::query()->whereBetween('created_at', [$inicio, $fim])->count();
        $matriculas = Matricula::query()->whereBetween('created_at', [$inicio, $fim])->count();
        $encontros = AulaAoVivo::query()->whereBetween('data_hora', [$inicio, $fim])->count();

        return "CETSADP • Resumo semanal\n"
            ."Novas inscrições nos últimos 7 dias: {$inscricoes}\n"
            ."Novas matrículas nos últimos 7 dias: {$matriculas}\n"
            ."Encontros com data no período: {$encontros}\n"
            .'Contagens agregadas; consulte o painel autenticado para detalhes.';
    }
}
