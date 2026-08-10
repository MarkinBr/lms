<?php

namespace App\Http\Controllers;

use App\Helpers\FinanceiroHelper;
use App\Models\Mensalidade;
use App\Models\MovimentacaoFinanceira;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class RelatorioFluxoCaixaController extends Controller
{
    /**
     * REL-07: Fluxo de Caixa Projetado dos próximos N dias.
     *
     * Calcula:
     *   - Entradas previstas: mensalidades Pendentes/Atrasadas com vencimento no período
     *   - Saídas previstas: despesas com data_vencimento no período
     *   - Saldo acumulado dia a dia
     */
    public function projetado(Request $request)
    {
        $data = $request->validate([
            'dias' => 'sometimes|integer|min:7|max:365',
        ]);

        $diasFrente = $data['dias'] ?? 90;
        $inicio     = now()->startOfDay();
        $fim        = now()->copy()->addDays($diasFrente)->endOfDay();

        // === ENTRADAS PROJETADAS ===
        $entradasPrev = Mensalidade::query()
            ->whereIn('status', ['Pendente', 'Atrasado'])
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->selectRaw('data_vencimento as data, SUM(valor) as total')
            ->groupBy('data_vencimento')
            ->orderBy('data_vencimento')
            ->get()
            ->keyBy(fn ($r) => $r->data instanceof \DateTime ? $r->data->format('Y-m-d') : (string) $r->data);

        // === SAÍDAS PROJETADAS (despesas pendentes) ===
        $saidasPrev = MovimentacaoFinanceira::query()
            ->where('tipo', 'saida')
            ->where('status', 'pendente')
            ->whereBetween('data_movimentacao', [$inicio, $fim])
            ->selectRaw('data_movimentacao as data, SUM(valor) as total')
            ->groupBy('data_movimentacao')
            ->get()
            ->keyBy(fn ($r) => $r->data instanceof \DateTime ? $r->data->format('Y-m-d') : (string) $r->data);

        // === SALDO ATUAL ===
        $saldoAtual = (float) MovimentacaoFinanceira::query()
            ->where('status', 'efetivado')
            ->selectRaw("
                SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) as saldo
            ")
            ->value('saldo') ?? 0;

        // === MONTAR PROJEÇÃO DIA A DIA ===
        $projecao    = [];
        $acumulado   = $saldoAtual;
        $totalEntradas = 0;
        $totalSaidas   = 0;

        foreach (CarbonPeriod::create($inicio, $fim) as $dia) {
            $key = $dia->format('Y-m-d');
            $ent = (float) ($entradasPrev[$key]->total ?? 0);
            $sai = (float) ($saidasPrev[$key]->total ?? 0);
            $acumulado += $ent - $sai;
            $totalEntradas += $ent;
            $totalSaidas   += $sai;

            $projecao[] = [
                'data'      => $dia,
                'entradas'  => $ent,
                'saidas'    => $sai,
                'saldo_dia' => $ent - $sai,
                'acumulado' => $acumulado,
            ];
        }

        $pdf = Pdf::loadView('pdf.fluxo-projetado', [
            'projecao'       => $projecao,
            'saldo_inicial'  => $saldoAtual,
            'saldo_final'    => $acumulado,
            'total_entradas' => $totalEntradas,
            'total_saidas'   => $totalSaidas,
            'inicio'         => $inicio,
            'fim'            => $fim,
            'dias_frente'    => $diasFrente,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('fluxo-caixa-projetado-' . now()->format('Y-m-d') . '.pdf');
    }
}