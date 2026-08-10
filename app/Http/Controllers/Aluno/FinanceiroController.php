<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Mensalidade;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceiroController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query Base (Segurança: busca apenas mensalidades do aluno logado)
        $query = Mensalidade::whereHas('matricula', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // --- 1. DADOS ESTATÍSTICOS (Fixos, não mudam com o filtro da tabela) ---
        // Usamos 'clone' para calcular os totais independentemente do que o aluno está vendo na lista
        $statsQuery = clone $query;

        // Widget 1: Próxima a Vencer (ou a mais antiga atrasada)
        $mensalidadeAtual = (clone $statsQuery)
            ->where('status', '!=', 'Pago')
            ->where('status', '!=', 'Cancelado')
            ->orderBy('data_vencimento', 'asc')
            ->first();

        // Widget 2: Total Investido
        $totalPago = (clone $statsQuery)
            ->where('status', 'Pago')
            ->sum('valor');

        $quantidadePagas = (clone $statsQuery)
            ->where('status', 'Pago')
            ->count();

        // Widget 3: Status Geral e Contagem de Atrasos
        $quantidadeAtrasadas = (clone $statsQuery)
            ->where('status', 'Atrasado')
            ->count();
        
        $statusFinanceiro = $quantidadeAtrasadas > 0 ? 'Pendente' : 'Em dia';

        // --- 2. FILTROS DA TABELA (Widgets Clicáveis) ---
        
        $filtro = $request->get('filtro');

        if ($filtro === 'pago') {
            // Clicou no card "Total Investido"
            $query->where('status', 'Pago');
        } 
        elseif ($filtro === 'aberto') {
            // Clicou no card "Próxima" (Mostra pendentes e atrasadas)
            $query->whereIn('status', ['Pendente', 'Atrasado']);
        }
        elseif ($filtro === 'atrasado') {
            // Clicou no card "Status" (se estiver pendente)
            $query->where('status', 'Atrasado');
        }

        // --- 3. LISTA FINAL ---
        $mensalidades = $query->with(['matricula.curso'])
            ->orderBy('data_vencimento', 'desc')
            ->paginate(10);

        return view('aluno.financeiro.index', compact(
            'mensalidades', 
            'mensalidadeAtual', 
            'totalPago', 
            'quantidadePagas', 
            'statusFinanceiro',
            'quantidadeAtrasadas' // Variável nova para exibir "X mensalidades vencidas"
        ));
    }

    public function gerarPix($id)
    {
        try {
            $mensalidade = $this->buscarMensalidadeDoAluno($id);

            if (!class_exists(MercadoPagoService::class)) {
                throw new \Exception('Serviço de pagamento indisponível.');
            }

            // Se já tem PIX gerado e válido, retorna o mesmo
            if ($mensalidade->pix_copia_cola && $mensalidade->status != 'Pago') {
                return response()->json([
                    'success' => true,
                    'qrcode' => $mensalidade->pix_qr_base64,
                    'copia_cola' => $mensalidade->pix_copia_cola,
                    // Informações extras para o Modal
                    'descricao' => $mensalidade->observacao ?? 'Mensalidade CETS',
                    'valor' => number_format($mensalidade->valor, 2, ',', '.')
                ]);
            }

            $mpService = new MercadoPagoService();
            $mpService->gerarPix($mensalidade); 
            
            $mensalidade->refresh();

            return response()->json([
                'success' => true,
                'qrcode' => $mensalidade->pix_qr_base64,
                'copia_cola' => $mensalidade->pix_copia_cola,
                'descricao' => $mensalidade->observacao ?? 'Mensalidade CETS',
                'valor' => number_format($mensalidade->valor, 2, ',', '.')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function gerarBoleto($id)
    {
        try {
            $mensalidade = $this->buscarMensalidadeDoAluno($id);

            if (!class_exists(MercadoPagoService::class)) {
                throw new \Exception('Serviço de pagamento indisponível.');
            }

            // Se já tem boleto gerado
            if ($mensalidade->link_boleto && $mensalidade->status != 'Pago') {
                return response()->json(['success' => true, 'link' => $mensalidade->link_boleto]);
            }

            $mpService = new MercadoPagoService();
            $mpService->gerarBoleto($mensalidade);
            
            $mensalidade->refresh();

            return response()->json([
                'success' => true,
                'link' => $mensalidade->link_boleto
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function reciboTermico($id)
    {
        $mensalidade = $this->buscarMensalidadeDoAluno($id);

        if ($mensalidade->status !== 'Pago') {
            return redirect()->back()->with('error', 'Recibo indisponível. Pagamento pendente.');
        }

        return view('aluno.financeiro.recibo-termico', compact('mensalidade'));
    }

    private function buscarMensalidadeDoAluno($id)
    {
        // Garante que o aluno só acesse suas próprias mensalidades
        return Mensalidade::whereHas('matricula', function ($query) {
            $query->where('user_id', Auth::id());
        })->findOrFail($id);
    }
}