<?php

namespace App\Http\Controllers;

use App\Helpers\InstituicaoHelper;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExtratoAlunoController extends Controller
{
    /**
     * REL-08: Extrato Individual do Aluno em PDF.
     * Inclui todas as mensalidades + pagamentos + débitos.
     */
    public function gerar(Request $request, int $userId)
    {
        $aluno = User::with([
            'matriculas.curso',
            'matriculas.mensalidades' => fn ($q) => $q->orderBy('data_vencimento'),
        ])->findOrFail($userId);

        // Autorização: o próprio aluno OU admin/secretaria
        $podeVer = auth()->id() === $aluno->id
            || in_array(auth()->user()?->nivel ?? '', ['admin', 'secretario']);

        abort_unless($podeVer, 403, 'Sem permissão para ver extrato deste aluno.');

        // Consolida estatísticas
        $todasMensalidades = $aluno->matriculas->flatMap->mensalidades;

        $stats = [
            'total_mensalidades' => $todasMensalidades->count(),
            'total_pago'         => (float) $todasMensalidades->where('status', 'Pago')
                                       ->sum(fn ($m) => $m->valor_pago ?? $m->valor),
            'total_pendente'     => (float) $todasMensalidades->where('status', 'Pendente')->sum('valor'),
            'total_atrasado'     => (float) $todasMensalidades->where('status', 'Atrasado')
                                       ->sum(fn ($m) => $m->calcularValorComMulta()),
            'qtd_atrasadas'      => $todasMensalidades->where('status', 'Atrasado')->count(),
            'qtd_pagas'          => $todasMensalidades->where('status', 'Pago')->count(),
        ];

        $stats['debito_atual'] = $stats['total_pendente'] + $stats['total_atrasado'];
        $stats['situacao']     = $stats['qtd_atrasadas'] > 0 ? '🔴 Inadimplente' : '✅ Em Dia';

        $pdf = Pdf::loadView('pdf.extrato-aluno', [
            'aluno' => $aluno,
            'stats' => $stats,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('extrato-' . str_replace(' ', '-', strtolower($aluno->name)) . '.pdf');
    }
}