<?php

namespace App\Http\Controllers;

use App\Models\Mensalidade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MensalidadeReportController extends Controller
{
    /**
     * Gera recibo individual de pagamento
     */
    public function gerarRecibo($id)
    {
        $mensalidade = Mensalidade::with(['matricula.user', 'matricula.curso'])->findOrFail($id);

        if ($mensalidade->status !== 'Pago') {
            abort(400, 'Mensalidade ainda não está paga');
        }

        $pdf = Pdf::loadView('pdf.recibo-mensalidade', compact('mensalidade'));
        
        return $pdf->stream("recibo-{$mensalidade->id}.pdf");
    }

    /**
     * Relatório geral de mensalidades
     */
    public function relatorioGeral()
    {
        $mensalidades = Mensalidade::with(['matricula.user', 'matricula.curso'])
            ->orderBy('data_vencimento', 'desc')
            ->get();

        $totais = [
            'pago' => $mensalidades->where('status', 'Pago')->sum('valor'),
            'pendente' => $mensalidades->where('status', 'Pendente')->sum('valor'),
            'atrasado' => $mensalidades->where('status', 'Atrasado')->sum('valor'),
            'total' => $mensalidades->sum('valor'),
        ];

        $pdf = Pdf::loadView('pdf.relatorio-mensalidades-geral', compact('mensalidades', 'totais'))
            ->setPaper('a4', 'landscape');
        
        return $pdf->stream('relatorio-mensalidades-geral.pdf');
    }

    /**
     * Relatório por período
     */
    public function relatorioPeriodo(Request $request)
    {
        $dataInicio = $request->data_inicio;
        $dataFim = $request->data_fim;
        $status = $request->status;

        $query = Mensalidade::with(['matricula.user', 'matricula.curso'])
            ->whereBetween('data_vencimento', [$dataInicio, $dataFim]);

        if ($status && $status !== 'todos') {
            $query->where('status', $status);
        }

        $mensalidades = $query->orderBy('data_vencimento', 'desc')->get();

        $totais = [
            'pago' => $mensalidades->where('status', 'Pago')->sum('valor'),
            'pendente' => $mensalidades->where('status', 'Pendente')->sum('valor'),
            'atrasado' => $mensalidades->where('status', 'Atrasado')->sum('valor'),
            'total' => $mensalidades->sum('valor'),
        ];

        $pdf = Pdf::loadView('pdf.relatorio-mensalidades-periodo', compact('mensalidades', 'totais', 'dataInicio', 'dataFim'))
            ->setPaper('a4', 'landscape');
        
        return $pdf->stream('relatorio-periodo.pdf');
    }

    /**
     * Relatório em lote (selecionadas)
     */
    public function relatorioLote(Request $request)
    {
        $ids = explode(',', $request->ids);
        
        $mensalidades = Mensalidade::with(['matricula.user', 'matricula.curso'])
            ->whereIn('id', $ids)
            ->orderBy('data_vencimento', 'desc')
            ->get();

        $totais = [
            'pago' => $mensalidades->where('status', 'Pago')->sum('valor'),
            'pendente' => $mensalidades->where('status', 'Pendente')->sum('valor'),
            'atrasado' => $mensalidades->where('status', 'Atrasado')->sum('valor'),
            'total' => $mensalidades->sum('valor'),
        ];

        $pdf = Pdf::loadView('pdf.relatorio-mensalidades-lote', compact('mensalidades', 'totais'))
            ->setPaper('a4', 'landscape');
        
        return $pdf->stream('relatorio-selecionadas.pdf');
    }
}
