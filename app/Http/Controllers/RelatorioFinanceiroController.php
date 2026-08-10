<?php

namespace App\Http\Controllers;

use App\Models\MovimentacaoFinanceira;
use App\Models\Conta;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class RelatorioFinanceiroController extends Controller
{
    public function relatorioMensal(Request $request)
    {
        $mes = $request->mes;
        $ano = $request->ano;

        $dataInicio = "{$ano}-{$mes}-01";
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        $movimentacoes = MovimentacaoFinanceira::with(['conta', 'categoria', 'criadoPor'])
            ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->where('status', 'efetivado')
            ->orderBy('data_movimentacao')
            ->get();

        $entradas = $movimentacoes->where('tipo', 'entrada');
        $saidas = $movimentacoes->where('tipo', 'saida');

        $totalEntradas = $entradas->sum('valor');
        $totalSaidas = $saidas->sum('valor');
        $saldo = $totalEntradas - $totalSaidas;

        // Agrupar por categoria
        $entradasPorCategoria = $entradas->groupBy('categoria.nome')->map(fn($items) => $items->sum('valor'));
        $saidasPorCategoria = $saidas->groupBy('categoria.nome')->map(fn($items) => $items->sum('valor'));

        // Saldo por conta
        $contas = Conta::all()->map(function ($conta) use ($movimentacoes) {
            $entradasConta = $movimentacoes->where('conta_id', $conta->id)->where('tipo', 'entrada')->sum('valor');
            $saidasConta = $movimentacoes->where('conta_id', $conta->id)->where('tipo', 'saida')->sum('valor');
            
            return [
                'nome' => $conta->nome,
                'saldo_inicial' => $conta->saldo_inicial,
                'entradas' => $entradasConta,
                'saidas' => $saidasConta,
                'saldo_final' => $conta->saldo_inicial + $entradasConta - $saidasConta,
            ];
        });

        $pdf = PDF::loadView('relatorios.financeiro-mensal', compact(
            'mes',
            'ano',
            'movimentacoes',
            'entradas',
            'saidas',
            'totalEntradas',
            'totalSaidas',
            'saldo',
            'entradasPorCategoria',
            'saidasPorCategoria',
            'contas'
        ));

        $nomeMes = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
            '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
            '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ][$mes];

        return $pdf->download("relatorio-financeiro-{$nomeMes}-{$ano}.pdf");
    }

    public function relatorioAnual(Request $request)
    {
        $ano = $request->ano;

        $movimentacoes = MovimentacaoFinanceira::with(['conta', 'categoria'])
            ->whereYear('data_movimentacao', $ano)
            ->where('status', 'efetivado')
            ->orderBy('data_movimentacao')
            ->get();

        // Dados por mês
        $dadosPorMes = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $mesFormatado = str_pad($mes, 2, '0', STR_PAD_LEFT);
            $movimentacoesMes = $movimentacoes->filter(function ($mov) use ($mes) {
                return $mov->data_movimentacao->month == $mes;
            });

            $entradas = $movimentacoesMes->where('tipo', 'entrada')->sum('valor');
            $saidas = $movimentacoesMes->where('tipo', 'saida')->sum('valor');

            $dadosPorMes[$mes] = [
                'mes' => $mesFormatado,
                'nome_mes' => [
                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                ][$mes],
                'entradas' => $entradas,
                'saidas' => $saidas,
                'saldo' => $entradas - $saidas,
            ];
        }

        $totalEntradas = $movimentacoes->where('tipo', 'entrada')->sum('valor');
        $totalSaidas = $movimentacoes->where('tipo', 'saida')->sum('valor');
        $saldoAnual = $totalEntradas - $totalSaidas;

        // Por categoria
        $entradas = $movimentacoes->where('tipo', 'entrada');
        $saidas = $movimentacoes->where('tipo', 'saida');
        $entradasPorCategoria = $entradas->groupBy('categoria.nome')->map(fn($items) => $items->sum('valor'));
        $saidasPorCategoria = $saidas->groupBy('categoria.nome')->map(fn($items) => $items->sum('valor'));

        $pdf = PDF::loadView('relatorios.financeiro-anual', compact(
            'ano',
            'dadosPorMes',
            'totalEntradas',
            'totalSaidas',
            'saldoAnual',
            'entradasPorCategoria',
            'saidasPorCategoria'
        ));

        return $pdf->download("relatorio-financeiro-anual-{$ano}.pdf");
    }
}
