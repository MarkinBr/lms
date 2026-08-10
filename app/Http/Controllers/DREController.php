<?php

namespace App\Http\Controllers;

use App\Models\MovimentacaoFinanceira;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DREController extends Controller
{
    /**
     * REL-18 — DRE simplificado com comparativo (REL-11).
     */
    public function gerar(Request $request)
    {
        $data = $request->validate([
            'mes' => 'sometimes|integer|between:1,12',
            'ano' => 'required|integer|min:2020|max:2100',
        ]);

        $tz = config('app.timezone', 'America/Sao_Paulo');

        if (isset($data['mes'])) {
            $inicio = Carbon::create($data['ano'], $data['mes'], 1, 0, 0, 0, $tz)->startOfMonth();
            $fim    = $inicio->copy()->endOfMonth();
            $titulo = $inicio->locale('pt-BR')->isoFormat('MMMM [de] YYYY');
        } else {
            $inicio = Carbon::create($data['ano'], 1, 1, 0, 0, 0, $tz)->startOfYear();
            $fim    = $inicio->copy()->endOfYear();
            $titulo = 'Ano de ' . $data['ano'];
        }

        $inicioAnt = $inicio->copy()->subYear();
        $fimAnt    = $fim->copy()->subYear();

        $dadosAtual    = $this->coletarDadosDRE($inicio, $fim);
        $dadosAnterior = $this->coletarDadosDRE($inicioAnt, $fimAnt);
        $variacao      = $this->calcularVariacao($dadosAtual, $dadosAnterior);

        $pdf = Pdf::loadView('pdf.dre', [
            'titulo'         => $titulo,
            'dados'          => $dadosAtual,
            'dados_anterior' => $dadosAnterior,
            'variacao'       => $variacao,
            'inicio'         => $inicio,
            'fim'            => $fim,
        ])->setPaper('a4');

        return $pdf->stream('dre-' . $inicio->format('Y-m') . '.pdf');
    }

    private function coletarDadosDRE(Carbon $inicio, Carbon $fim): array
    {
        $base = MovimentacaoFinanceira::query()
            ->whereBetween('data_movimentacao', [$inicio, $fim])
            ->where('status', 'efetivado');

        $receitas = (clone $base)->where('tipo', 'entrada')
            ->join('categoria_financeiras', 'movimentacoes_financeiras.categoria_id', '=', 'categoria_financeiras.id')
            ->groupBy('categoria_financeiras.nome')
            ->selectRaw('categoria_financeiras.nome as categoria, SUM(movimentacoes_financeiras.valor) as total')
            ->pluck('total', 'categoria');

        $despesas = (clone $base)->where('tipo', 'saida')
            ->join('categoria_financeiras', 'movimentacoes_financeiras.categoria_id', '=', 'categoria_financeiras.id')
            ->groupBy('categoria_financeiras.nome')
            ->selectRaw('categoria_financeiras.nome as categoria, SUM(movimentacoes_financeiras.valor) as total')
            ->pluck('total', 'categoria');

        $receitaMensalidades = (float) ($receitas['Mensalidades']   ?? 0);
        $receitaMultas       = (float) ($receitas['Multas e Juros'] ?? 0);
        $receitaTaxas        = (float) ($receitas['Taxas Avulsas']  ?? 0);
        $receitaMatricula    = (float) ($receitas['Matrículas']     ?? 0);
        $receitaOutras       = (float) collect($receitas)->except([
            'Mensalidades', 'Multas e Juros', 'Taxas Avulsas', 'Matrículas',
        ])->sum();

        $totalReceitasOp = $receitaMensalidades + $receitaMultas + $receitaTaxas + $receitaMatricula;
        $totalReceitas   = $totalReceitasOp + $receitaOutras;

        $despesasOp      = 0.0;
        $despesasAdm     = 0.0;
        $despesasDetalhe = [];

        foreach ($despesas as $cat => $valor) {
            $valor = (float) $valor;
            $despesasDetalhe[$cat] = $valor;
            $catLower = strtolower((string) $cat);
            $isAdm = str_contains($catLower, 'escrit')
                  || str_contains($catLower, 'aluguel')
                  || str_contains($catLower, 'admin')
                  || str_contains($catLower, 'limpez')
                  || str_contains($catLower, 'agua')
                  || str_contains($catLower, 'luz')
                  || str_contains($catLower, 'internet');
            if ($isAdm) { $despesasAdm += $valor; } else { $despesasOp += $valor; }
        }

        $resultadoLiquido = $totalReceitas - ($despesasOp + $despesasAdm);

        return [
            'receita_mensalidades' => $receitaMensalidades,
            'receita_multas'       => $receitaMultas,
            'receita_taxas'        => $receitaTaxas,
            'receita_matricula'    => $receitaMatricula,
            'receita_outras'       => $receitaOutras,
            'total_receitas_op'    => $totalReceitasOp,
            'total_receitas'       => $totalReceitas,
            'despesas_op'          => $despesasOp,
            'despesas_adm'         => $despesasAdm,
            'despesas_detalhe'     => $despesasDetalhe,
            'total_despesas'       => $despesasOp + $despesasAdm,
            'resultado_liquido'    => $resultadoLiquido,
            'margem_liquida'       => $totalReceitas > 0
                ? ($resultadoLiquido / $totalReceitas) * 100
                : 0,
        ];
    }

    private function calcularVariacao(array $atual, array $anterior): array
    {
        $variacao = [];
        foreach ($atual as $key => $val) {
            if (!is_numeric($val)) { continue; }
            $base = $anterior[$key] ?? 0;
            $variacao[$key] = $base != 0
                ? round((($val - $base) / abs($base)) * 100, 2)
                : null;
        }

        return $variacao;
    }
}