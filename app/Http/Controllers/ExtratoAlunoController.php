<?php

namespace App\Http\Controllers;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ExtratoAlunoController extends Controller
{
    /**
     * REL-08: Extrato Individual do Aluno em PDF.
     * Inclui todas as mensalidades + pagamentos + débitos.
     *
     * Issue #49: a autorização é avaliada ANTES de carregar qualquer dado
     * sensível do aluno (matrículas, mensalidades, endereço, telefone).
     * Aluno pedindo extrato de terceiro recebe 404 (não confirma existência).
     */
    public function gerar(Request $request, int $userId)
    {
        $requester = $request->user();

        // Usuário inativo nunca passa, mesmo sendo admin/secretário.
        abort_unless($requester && (bool) $requester->ativo, 403, 'Conta inativa.');

        $ehGestao = in_array($requester->nivel ?? '', ['admin', 'secretario'], true);
        $ehProprioAluno = (int) $requester->id === (int) $userId
            && $requester->nivel === 'aluno';

        if (! $ehGestao && ! $ehProprioAluno) {
            // Para não-gestão, 404 evita confirmar a existência do ID.
            abort(404);
        }

        // Só carrega dados sensíveis depois de autorizado.
        $aluno = User::with([
            'matriculas.curso',
            'matriculas.mensalidades' => fn ($q) => $q->orderBy('data_vencimento'),
        ])->find($userId);

        // Alvo inexistente ou não é aluno (ex.: secretário consultando outro secretário).
        if (! $aluno || $aluno->nivel !== 'aluno') {
            abort(404);
        }

        // Consolida estatísticas
        $todasMensalidades = $aluno->matriculas->flatMap->mensalidades;

        $stats = [
            'total_mensalidades' => $todasMensalidades->count(),
            'total_pago' => (float) $todasMensalidades->where('status', 'Pago')
                ->sum(fn ($m) => $m->valor_pago ?? $m->valor),
            'total_pendente' => (float) $todasMensalidades->where('status', 'Pendente')->sum('valor'),
            'total_atrasado' => (float) $todasMensalidades->where('status', 'Atrasado')
                ->sum(fn ($m) => $m->calcularValorComMulta()),
            'qtd_atrasadas' => $todasMensalidades->where('status', 'Atrasado')->count(),
            'qtd_pagas' => $todasMensalidades->where('status', 'Pago')->count(),
        ];

        $stats['debito_atual'] = $stats['total_pendente'] + $stats['total_atrasado'];
        $stats['situacao'] = $stats['qtd_atrasadas'] > 0 ? '🔴 Inadimplente' : '✅ Em Dia';

        $pdf = Pdf::loadView('pdf.extrato-aluno', [
            'aluno' => $aluno,
            'stats' => $stats,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('extrato-'.str_replace(' ', '-', strtolower($aluno->name)).'.pdf');
    }
}
