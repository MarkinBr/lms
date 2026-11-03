<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Jobs\EnviarFinanceiroAgendado;
use App\Models\FinanceiroEnvio;
use App\Models\Mensalidade;
use App\Services\MercadoPagoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CentralFinanceiroController extends Controller
{
    public function index(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 100);
        $situacao = (string) $request->query('situacao', '');
        $mensalidades = Mensalidade::query()->with(['matricula.user:id,name,email', 'matricula.curso:id,titulo'])
            ->when($busca !== '', fn ($q) => $q->whereHas('matricula.user', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($busca).'%'])))
            ->when(in_array($situacao, ['Pago', 'Pendente', 'Atrasado'], true), fn ($q) => $q->where('status', $situacao))
            ->orderByDesc('data_vencimento')->paginate(18)->withQueryString();

        $totais = [
            'recebido_mes' => (float) Mensalidade::query()->where('status', 'Pago')
                ->whereYear('data_pagamento', now()->year)->whereMonth('data_pagamento', now()->month)->sum('valor_pago'),
            'em_aberto' => (float) Mensalidade::query()->whereIn('status', ['Pendente', 'Atrasado'])->sum('valor'),
            'vencidas' => Mensalidade::query()->whereIn('status', ['Pendente', 'Atrasado'])
                ->whereDate('data_vencimento', '<', today())->count(),
            'agendamentos' => FinanceiroEnvio::query()->where('status', 'agendado')->count(),
        ];
        $envios = FinanceiroEnvio::query()->with('mensalidade:id,data_vencimento')
            ->orderByDesc('id')->limit(10)->get();

        return view('secretaria.central.financeiro.index',
            compact('mensalidades', 'totais', 'envios', 'busca', 'situacao'));
    }

    /**
     * Emissão REAL pelo SDK existente, protegida por uma chave operacional.
     * A chamada não confirma pagamento; webhook/extrato são a fonte de baixa.
     */
    public function gerarPix(Request $request, Mensalidade $mensalidade): RedirectResponse
    {
        return $this->emitir($request, $mensalidade, 'pix');
    }

    public function gerarBoleto(Request $request, Mensalidade $mensalidade): RedirectResponse
    {
        return $this->emitir($request, $mensalidade, 'boleto');
    }

    private function emitir(Request $request, Mensalidade $mensalidade, string $metodo): RedirectResponse
    {
        $request->validate(['conferi_cobranca' => ['accepted']]);
        if (! config('financeiro.habilitar_emissao_mercadopago_secretaria', false)) {
            throw ValidationException::withMessages(['conferi_cobranca' =>
                'Emissão real desativada nesta instalação. Ative após homologação e conferência das credenciais no servidor.']);
        }
        if (! config('services.mercadopago.token')) {
            throw ValidationException::withMessages(['conferi_cobranca' => 'Mercado Pago não está configurado.']);
        }
        $mensalidade->refresh();
        if (! $mensalidade->podeGerarPagamento() || (float) $mensalidade->valor <= 0) {
            throw ValidationException::withMessages(['conferi_cobranca' => 'Esta mensalidade não permite emissão.']);
        }
        // Evita lançar cobranças em dois meios ao mesmo tempo; a liquidação
        // de uma transação pendente precisa ser conferida antes da troca.
        if ($mensalidade->transacao_id && $mensalidade->metodo_pagamento &&
            ! in_array($mensalidade->metodo_pagamento, [$metodo, $metodo === 'boleto' ? 'bolbradesco' : 'pix'], true)) {
            throw ValidationException::withMessages(['conferi_cobranca' =>
                'Já existe uma cobrança em outro meio. Confira a transação anterior antes de mudar.']);
        }

        try {
            $gateway = app(MercadoPagoService::class);
            if ($metodo === 'pix') {
                $gateway->gerarPix($mensalidade);
            } else {
                $gateway->gerarBoleto($mensalidade);
            }
        } catch (\Throwable $erro) {
            Log::error('Falha em emissão financeira administrativa', [
                'mensalidade_id' => $mensalidade->id,
                'tipo' => $metodo,
                'classe' => get_class($erro),
            ]);
            throw ValidationException::withMessages(['conferi_cobranca' =>
                'Não foi possível emitir a cobrança. Verifique os dados do aluno e a configuração do gateway.']);
        }

        return redirect()->route('secretaria.financeiro.painel', ['busca' => $mensalidade->matricula?->user?->name])
            ->with('status', strtoupper($metodo).' emitido pelo Mercado Pago. Aguardando liquidação oficial.');
    }

    public function recibo(Mensalidade $mensalidade): View
    {
        abort_unless($mensalidade->estaPaga(), 404);
        $mensalidade->load(['matricula.user', 'matricula.curso']);
        $verificacao = route('recibo.publico.form', $mensalidade->codigo_validacao);

        return view('secretaria.central.financeiro.recibo', compact('mensalidade', 'verificacao'));
    }

    public function relatorio(Request $request): Response
    {
        $f = $request->validate([
            'inicio' => ['required', 'date_format:Y-m-d'],
            'fim' => ['required', 'date_format:Y-m-d', 'after_or_equal:inicio'],
        ]);
        $inicio = Carbon::parse($f['inicio']);
        $fim = Carbon::parse($f['fim']);
        if ($inicio->diffInDays($fim) > 366) {
            throw ValidationException::withMessages(['fim' => 'O período máximo é de 366 dias.']);
        }
        $query = Mensalidade::query()->where('status', 'Pago')
            ->whereBetween('data_pagamento', [$inicio->toDateString(), $fim->toDateString()]);
        $count = (clone $query)->count();
        if ($count > 1200) {
            throw ValidationException::withMessages(['fim' =>
                'O relatório contém muitos pagamentos. Escolha um período menor para impressão.']);
        }
        $mensalidades = $query->with(['matricula.user:id,name', 'matricula.curso:id,titulo'])
            ->orderBy('data_pagamento')->get();
        $totais = [
            'total' => (float) $mensalidades->sum(fn ($m) => (float) ($m->valor_pago ?? $m->valor)),
            'dinheiro' => (float) $mensalidades->filter(fn ($m) => $m->metodo_pagamento === 'dinheiro')
                ->sum(fn ($m) => (float) ($m->valor_pago ?? $m->valor)),
            'quantidade' => $mensalidades->count(),
        ];
        $pdf = Pdf::loadView('pdf.financeiro-relatorio-central',
            ['mensalidades' => $mensalidades, 'totais' => $totais, 'inicio' => $inicio, 'fim' => $fim])
            ->setPaper('a4', 'landscape');

        return $pdf->stream('relatorio-financeiro-'.$f['inicio'].'-'.$f['fim'].'.pdf');
    }

    public function agendar(Request $request, Mensalidade $mensalidade): RedirectResponse
    {
        $dados = $request->validate([
            'canal' => ['required', Rule::in(['email', 'whatsapp'])],
            'modelo' => ['required', Rule::in(['lembrete', 'cobranca', 'pix', 'boleto', 'recibo'])],
            'agendado_para' => ['required', 'date', 'after_or_equal:now'],
            'confirmou_contato' => ['accepted'],
        ]);
        $mensalidade->load('matricula.user');
        if ($dados['modelo'] === 'recibo' && ! $mensalidade->estaPaga()) {
            throw ValidationException::withMessages(['modelo' => 'Recibos exigem mensalidade paga e confirmada.']);
        }
        if ($dados['modelo'] !== 'recibo' && ! $mensalidade->podeGerarPagamento()) {
            throw ValidationException::withMessages(['modelo' => 'Não envie cobrança para mensalidade paga ou cancelada.']);
        }
        if (in_array($dados['modelo'], ['pix', 'boleto'], true)) {
            $campo = $dados['modelo'] === 'pix' ? 'pix_copia_cola' : 'link_boleto';
            if (! filled($mensalidade->{$campo})) {
                throw ValidationException::withMessages(['modelo' =>
                    'Primeiro emita a cobrança real pelo Mercado Pago e confirme o código/URL gerados.']);
            }
        }
        $usuario = $mensalidade->matricula?->user;
        $destinatario = $dados['canal'] === 'email' ? (string) $usuario?->email :
            (string) ($usuario?->telefone ?? '');
        if ($dados['canal'] === 'email' && ! filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['canal' => 'O aluno não possui e-mail válido.']);
        }
        if ($dados['canal'] === 'whatsapp' && ! preg_match('/^\+?\d{10,13}$/', preg_replace('/[\s()-]/', '', $destinatario))) {
            throw ValidationException::withMessages(['canal' => 'O aluno não possui telefone válido para o WhatsApp.']);
        }
        if (! config('financeiro.habilitar_envios_automaticos_secretaria', false)) {
            throw ValidationException::withMessages(['canal' =>
                'Mensagens automáticas estão desativadas; habilite apenas após homologar consentimento e provedor.']);
        }

        $envio = DB::transaction(fn () => FinanceiroEnvio::query()->create([
            'mensalidade_id' => $mensalidade->id,
            'criado_por' => $request->user()->id,
            'canal' => $dados['canal'],
            'modelo' => $dados['modelo'],
            'status' => 'agendado',
            'destinatario' => $destinatario,
            'agendado_para' => Carbon::parse($dados['agendado_para']),
        ]));

        EnviarFinanceiroAgendado::dispatch($envio->id)
            ->delay($envio->agendado_para)->afterCommit();

        return back()->with('status', 'Mensagem agendada no servidor. Consulte o status de entrega no painel.');
    }

    public function cancelarEnvio(FinanceiroEnvio $envio): RedirectResponse
    {
        $alterados = FinanceiroEnvio::query()
            ->whereKey($envio->id)->where('status', 'agendado')
            ->update(['status' => 'cancelado']);
        if ($alterados === 0) {
            throw ValidationException::withMessages(['envio' => 'Somente mensagens ainda agendadas podem ser canceladas.']);
        }

        return back()->with('status', 'Agendamento cancelado sem envio.');
    }
}
