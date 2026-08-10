@extends('layouts.aluno')

@section('title', 'Financeiro')
@section('header_title', 'Financeiro')
@section('header_subtitle', 'Controle suas mensalidades e pagamentos')

@section('content')
<div class="space-y-8 animate-fade-in">
    
    {{-- Resumo Financeiro (Widgets Clicáveis) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {{-- Widget 1: Próximas / Abertas --}}
        <a href="{{ route('aluno.financeiro', ['filtro' => 'aberto']) }}" class="block group">
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden group-hover:scale-[1.02] transition-transform duration-300">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fa-solid fa-calendar-day text-2xl"></i>
                        </div>
                        <span class="px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-[10px] font-black uppercase shadow-lg">
                            Próxima
                        </span>
                    </div>
                    <p class="text-xs font-bold text-blue-100 uppercase tracking-widest mb-1">Valor a Pagar</p>
                    <p class="text-3xl font-black tracking-tight">
                        @if($mensalidadeAtual)
                            R$ {{ number_format($mensalidadeAtual->valor, 2, ',', '.') }}
                        @else
                            R$ 0,00
                        @endif
                    </p>
                    <div class="mt-4 pt-4 border-t border-white/10">
                        <p class="text-xs text-blue-100 font-bold flex items-center">
                            @if($mensalidadeAtual)
                                <i class="fa-regular fa-clock mr-2"></i> 
                                Vence em {{ \Carbon\Carbon::parse($mensalidadeAtual->data_vencimento)->format('d/m/Y') }}
                            @else
                                <i class="fa-solid fa-check-circle mr-2"></i> Tudo em dia!
                            @endif
                        </p>
                    </div>
                </div>
                <i class="fa-solid fa-calendar-day absolute -right-6 -bottom-6 text-white/10 text-[8rem] group-hover:rotate-12 transition-transform duration-500"></i>
            </div>
        </a>
        
        {{-- Widget 2: Total Pago --}}
        <a href="{{ route('aluno.financeiro', ['filtro' => 'pago']) }}" class="block group">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 relative overflow-hidden group-hover:border-green-400 transition-colors">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-sack-dollar text-2xl"></i>
                        </div>
                        <span class="text-green-600 font-black text-xs uppercase tracking-widest bg-green-50 px-3 py-1 rounded-full">Acumulado</span>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Investido</p>
                    <p class="text-3xl font-black text-slate-800">R$ {{ number_format($totalPago, 2, ',', '.') }}</p>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <p class="text-xs text-slate-500 font-bold">
                            <i class="fa-solid fa-receipt mr-1 text-green-500"></i> {{ $quantidadePagas }} mensalidades quitadas
                        </p>
                    </div>
                </div>
            </div>
        </a>
        
        {{-- Widget 3: Status Geral (CORRIGIDO PARA MOSTRAR QUANTIDADE) --}}
        <a href="{{ $statusFinanceiro !== 'Em dia' ? route('aluno.financeiro', ['filtro' => 'atrasado']) : '#' }}" class="block group {{ $statusFinanceiro === 'Em dia' ? 'cursor-default' : '' }}">
            <div class="rounded-2xl p-6 text-white shadow-xl relative overflow-hidden group-hover:scale-[1.02] transition-transform duration-300
                {{ $statusFinanceiro === 'Em dia' ? 'bg-gradient-to-br from-emerald-500 to-teal-600' : 'bg-gradient-to-br from-red-500 to-rose-600' }}">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fa-solid {{ $statusFinanceiro === 'Em dia' ? 'fa-shield-check' : 'fa-triangle-exclamation' }} text-2xl"></i>
                        </div>
                        <span class="px-3 py-1 bg-white/20 text-white rounded-full text-[10px] font-black uppercase">
                            Status
                        </span>
                    </div>
                    <p class="text-xs font-bold text-white/80 uppercase tracking-widest mb-1">Situação Financeira</p>
                    <p class="text-2xl font-black">{{ $statusFinanceiro }}</p>
                    
                    {{-- AQUI ESTÁ A MUDANÇA --}}
                    <div class="mt-4 pt-4 border-t border-white/10">
                        <p class="text-xs text-white/90 font-bold">
                            @if($statusFinanceiro === 'Em dia')
                                Parabéns, nenhuma pendência!
                            @else
                                {{ $quantidadeAtrasadas }} mensalidade{{ $quantidadeAtrasadas > 1 ? 's' : '' }} vencida{{ $quantidadeAtrasadas > 1 ? 's' : '' }}.
                            @endif
                        </p>
                    </div>
                </div>
                <i class="fa-solid {{ $statusFinanceiro === 'Em dia' ? 'fa-shield-check' : 'fa-triangle-exclamation' }} absolute -right-6 -bottom-6 text-white/10 text-[8rem] group-hover:scale-110 transition-transform duration-500"></i>
            </div>
        </a>
    </div>
    
    {{-- Ações Rápidas --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        {{-- Botão Pagar Próxima --}}
        @if($mensalidadeAtual)
        <button onclick="abrirOpcoesPagamento({{ $mensalidadeAtual->id }})" class="flex items-center space-x-3 p-4 bg-white rounded-xl border border-slate-200 hover:border-blue-500 hover:shadow-lg transition-all group text-left cursor-pointer">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors shadow-sm">
                <i class="fa-solid fa-bolt text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-black text-slate-800">Pagar Agora</p>
                <p class="text-xs text-slate-400 group-hover:text-blue-600 transition-colors">Próxima mensalidade</p>
            </div>
        </button>
        @else
        <div class="flex items-center space-x-3 p-4 bg-slate-50 rounded-xl border border-slate-200 opacity-60 cursor-not-allowed">
            <div class="w-12 h-12 bg-slate-200 text-slate-400 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-check text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-black text-slate-600">Pagar Agora</p>
                <p class="text-xs text-slate-400">Nada pendente</p>
            </div>
        </div>
        @endif
        
        {{-- Botão Comprovantes (Filtro) --}}
        <a href="{{ route('aluno.financeiro', ['filtro' => 'pago']) }}" class="flex items-center space-x-3 p-4 bg-white rounded-xl border border-slate-200 hover:border-purple-500 hover:shadow-lg transition-all group">
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors shadow-sm">
                <i class="fa-solid fa-file-invoice text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-black text-slate-800">Comprovantes</p>
                <p class="text-xs text-slate-400 group-hover:text-purple-600 transition-colors">Ver histórico</p>
            </div>
        </a>

        {{-- Botão Negociar (Modal) --}}
        <button onclick="abrirNegociacao()" class="flex items-center space-x-3 p-4 bg-white rounded-xl border border-slate-200 hover:border-orange-500 hover:shadow-lg transition-all group text-left w-full">
            <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors shadow-sm">
                <i class="fa-solid fa-handshake text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-black text-slate-800">Negociar</p>
                <p class="text-xs text-slate-400 group-hover:text-orange-600 transition-colors">Falar com tesouraria</p>
            </div>
        </button>

        {{-- Botão Suporte --}}
        <a href="{{ route('aluno.secretaria') }}" class="flex items-center space-x-3 p-4 bg-white rounded-xl border border-slate-200 hover:border-cyan-500 hover:shadow-lg transition-all group">
            <div class="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-xl flex items-center justify-center group-hover:bg-cyan-600 group-hover:text-white transition-colors shadow-sm">
                <i class="fa-solid fa-headset text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-black text-slate-800">Suporte</p>
                <p class="text-xs text-slate-400 group-hover:text-cyan-600 transition-colors">Problemas?</p>
            </div>
        </a>
    </div>
    
    {{-- Tabela de Mensalidades --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-lg font-black text-slate-800">
                    @if(request('filtro') == 'pago')
                        <i class="fa-solid fa-filter text-purple-600 mr-2"></i> Meus Comprovantes
                    @elseif(request('filtro') == 'aberto')
                        <i class="fa-solid fa-filter text-blue-600 mr-2"></i> Em Aberto
                    @elseif(request('filtro') == 'atrasado')
                        <i class="fa-solid fa-filter text-red-600 mr-2"></i> Em Atraso
                    @else
                        Histórico de Mensalidades
                    @endif
                </h3>
                <p class="text-sm text-slate-500 mt-1">Lista completa de faturas</p>
            </div>
            
            <div class="flex items-center gap-2">
                @if(request('filtro'))
                    <a href="{{ route('aluno.financeiro') }}" class="px-3 py-1 bg-white border border-slate-300 text-slate-600 text-xs font-bold rounded-lg uppercase tracking-widest hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-times mr-1"></i> Limpar Filtro
                    </a>
                @endif
                <span class="px-3 py-1 bg-slate-200 text-slate-600 text-xs font-bold rounded-lg uppercase tracking-widest">
                    {{ $mensalidades->total() }} Registros
                </span>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-widest">Descrição</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-widest">Vencimento</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-widest">Valor</th>
                        <th class="px-6 py-4 text-center text-xs font-black text-slate-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-widest">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mensalidades as $mensalidade)
                        <tr class="hover:bg-slate-50 transition-colors group">
                            {{-- Curso e Parcela --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                                        @if(isset($mensalidade->matricula->curso->capa) && $mensalidade->matricula->curso->capa)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($mensalidade->matricula->curso->capa) }}" class="w-full h-full object-cover">
                                        @else
                                            <i class="fa-solid fa-graduation-cap text-slate-400 text-lg"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 line-clamp-1">
                                            {{ $mensalidade->matricula->curso->titulo ?? 'Mensalidade Avulsa' }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 uppercase font-semibold">
                                            {{ $mensalidade->observacao ?? 'Parcela' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Vencimento --}}
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold {{ $mensalidade->status === 'Atrasado' ? 'text-red-600' : 'text-slate-600' }}">
                                    {{ \Carbon\Carbon::parse($mensalidade->data_vencimento)->format('d/m/Y') }}
                                </p>
                                @if($mensalidade->status !== 'Pago' && $mensalidade->data_vencimento < now()->startOfDay())
                                    <span class="text-[10px] font-bold text-red-500 uppercase bg-red-50 px-1.5 py-0.5 rounded">Vencida</span>
                                @endif
                            </td>
                            
                            {{-- Valor --}}
                            <td class="px-6 py-4">
                                <p class="text-sm font-black text-slate-700 group-hover:text-blue-700 transition-colors">
                                    R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}
                                </p>
                            </td>
                            
                            {{-- Status --}}
                            <td class="px-6 py-4 text-center">
                                @if($mensalidade->status === 'Pago')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                        <i class="fa-solid fa-check"></i> Pago
                                    </span>
                                @elseif($mensalidade->status === 'Atrasado')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                        <i class="fa-solid fa-circle-exclamation"></i> Atrasado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200">
                                        <i class="fa-regular fa-clock"></i> Aberto
                                    </span>
                                @endif
                            </td>
                            
                            {{-- Ações --}}
                            <td class="px-6 py-4 text-right">
                                @if($mensalidade->status === 'Pago')
                                    <div class="flex justify-end gap-2">
                                        {{-- Recibo Normal --}}
                                        <a href="{{ route('mensalidade.recibo.pdf', $mensalidade->id) }}" target="_blank" 
                                           class="inline-flex items-center justify-center w-8 h-8 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors"
                                           title="Recibo A4">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        {{-- Recibo Térmico (NOVO) --}}
                                        <a href="{{ route('financeiro.recibo.termico', $mensalidade->id) }}" target="_blank" 
                                           class="inline-flex items-center justify-center w-8 h-8 bg-yellow-50 hover:bg-yellow-100 text-yellow-600 rounded-lg transition-colors border border-yellow-200"
                                           title="Recibo Cupom (80mm)">
                                            <i class="fa-solid fa-ticket"></i>
                                        </a>
                                    </div>
                                @else
                                    <button onclick="abrirOpcoesPagamento({{ $mensalidade->id }})" 
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-[#003366] hover:bg-[#002244] text-white rounded-lg text-xs font-bold uppercase shadow-sm hover:shadow-md transition-all active:scale-95">
                                        Pagar <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-filter-circle-xmark text-2xl mb-2 opacity-50"></i>
                                <p class="font-bold">Nada encontrado</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($mensalidades->hasPages())
        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $mensalidades->links() }}
        </div>
        @endif
    </div>
</div>

{{-- MODAL OPÇÕES --}}
<div id="modalOpcoes" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="fecharModais()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200">
                <div class="bg-slate-50 px-4 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Escolha a forma de pagamento</h3>
                    <button onclick="fecharModais()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>
                <div class="p-6 space-y-3">
                    <input type="hidden" id="pagamentoIdSelecionado">
                    
                    {{-- PIX --}}
                    <button onclick="selecionarPix()" class="w-full group flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-green-500 hover:bg-green-50 hover:shadow-md transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
                                <i class="fa-brands fa-pix text-2xl"></i>
                            </div>
                            <div class="text-left">
                                <p class="font-bold text-slate-800">PIX (Instantâneo)</p>
                                <p class="text-xs text-slate-500">Liberação imediata</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-green-500"></i>
                    </button>

                    {{-- Boleto --}}
                    <button onclick="selecionarBoleto()" class="w-full group flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50 hover:shadow-md transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-barcode text-2xl"></i>
                            </div>
                            <div class="text-left">
                                <p class="font-bold text-slate-800">Boleto Bancário</p>
                                <p class="text-xs text-slate-500">Até 3 dias úteis</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-blue-500"></i>
                    </button>

                    {{-- Cartão (Novo) --}}
                    <button onclick="alert('Pagamento com cartão estará disponível em breve!')" class="w-full group flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-purple-500 hover:bg-purple-50 hover:shadow-md transition-all opacity-70">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
                                <i class="fa-regular fa-credit-card text-2xl"></i>
                            </div>
                            <div class="text-left">
                                <p class="font-bold text-slate-800">Cartão de Crédito</p>
                                <p class="text-xs text-slate-500">Em breve</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-lock text-slate-300"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PIX --}}
<div id="modalPix" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-sm border border-slate-200">
                <div class="bg-green-600 px-4 py-6 text-center text-white">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/20 mb-3 backdrop-blur-md">
                        <i class="fa-brands fa-pix text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-black uppercase tracking-wide">Pagamento via PIX</h3>
                    
                    {{-- INFO DINÂMICA DO PIX (NOVO) --}}
                    <div class="mt-3 bg-white/10 rounded-lg p-2 text-sm backdrop-blur-sm">
                        <p class="font-bold text-green-100" id="pixDescricao">Carregando...</p>
                        <p class="font-black text-2xl text-white mt-1">R$ <span id="pixValor">0,00</span></p>
                    </div>
                </div>

                <div class="p-6 text-center space-y-4">
                    <div id="pixQrCodeContainer" class="flex justify-center p-2 bg-white border border-slate-100 rounded-xl shadow-inner">
                        <div class="w-48 h-48 bg-slate-100 rounded animate-pulse"></div>
                    </div>
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Copia e Cola</label>
                        <div class="flex gap-2">
                            <input type="text" id="pixCopiaCola" readonly class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-600 focus:outline-none">
                            <button onclick="copiarPix()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-2 rounded-lg font-bold text-xs transition-colors">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <button onclick="fecharModais()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 rounded-xl transition-colors text-sm uppercase">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL NEGOCIAÇÃO --}}
<div id="modalNegociacao" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="fecharModais()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200">
                <div class="bg-orange-600 px-4 py-6 text-center text-white">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/20 mb-3 backdrop-blur-md">
                        <i class="fa-solid fa-handshake text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-black uppercase tracking-wide">Negociação Financeira</h3>
                    <p class="text-orange-100 text-xs mt-1">Entre em contato para regularizar</p>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-600 text-center">
                        Para negociar débitos ou alterar datas de vencimento, fale diretamente com nossa tesouraria.
                    </p>
                    <a href="https://wa.me/5521999999999?text=Ol%C3%A1%2C+sou+o+aluno+{{ auth()->user()->name }}+e+gostaria+de+falar+sobre+meu+financeiro." 
                       target="_blank"
                       class="w-full flex items-center justify-center gap-3 p-4 rounded-xl bg-green-500 hover:bg-green-600 text-white transition-colors shadow-md group">
                        <i class="fa-brands fa-whatsapp text-2xl"></i>
                        <div class="text-left">
                            <p class="font-bold text-sm">Chamar no WhatsApp</p>
                            <p class="text-[10px] text-green-100 opacity-90">Atendimento Seg-Sex 09h às 18h</p>
                        </div>
                    </a>
                    <a href="mailto:financeiro@cets.com.br" 
                       class="w-full flex items-center justify-center gap-3 p-4 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                        <i class="fa-regular fa-envelope text-xl text-slate-400"></i>
                        <div class="text-left">
                            <p class="font-bold text-sm text-slate-700">Enviar E-mail</p>
                            <p class="text-[10px] text-slate-400">financeiro@cets.com.br</p>
                        </div>
                    </a>
                    <button onclick="fecharModais()" class="w-full text-slate-400 hover:text-slate-600 font-bold text-xs uppercase py-2">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function fecharModais() {
        document.getElementById('modalOpcoes').classList.add('hidden');
        document.getElementById('modalPix').classList.add('hidden');
        document.getElementById('modalNegociacao').classList.add('hidden');
    }

    function abrirOpcoesPagamento(id) {
        document.getElementById('pagamentoIdSelecionado').value = id;
        document.getElementById('modalOpcoes').classList.remove('hidden');
    }

    function abrirNegociacao() {
        document.getElementById('modalNegociacao').classList.remove('hidden');
    }

    function selecionarPix() {
        const id = document.getElementById('pagamentoIdSelecionado').value;
        document.getElementById('modalOpcoes').classList.add('hidden');
        document.getElementById('modalPix').classList.remove('hidden');
        
        fetch(`/portal/financeiro/${id}/pix`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Preenche QR e Copia Cola
                const imgTag = `<img src="data:image/jpeg;base64,${data.qrcode}" class="w-48 h-48 mx-auto" />`;
                document.getElementById('pixQrCodeContainer').innerHTML = imgTag;
                document.getElementById('pixCopiaCola').value = data.copia_cola;
                
                // PREENCHE AS NOVAS INFORMAÇÕES
                document.getElementById('pixDescricao').innerText = data.descricao;
                document.getElementById('pixValor').innerText = data.valor;
            } else {
                alert('Erro: ' + data.message);
                fecharModais();
            }
        })
        .catch(error => {
            console.error(error);
            alert('Erro de comunicação.');
            fecharModais();
        });
    }

    function selecionarBoleto() {
        const id = document.getElementById('pagamentoIdSelecionado').value;
        if(!confirm('Deseja gerar o boleto agora?')) return;

        fetch(`/portal/financeiro/${id}/boleto`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                window.open(data.link, '_blank');
                fecharModais();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error(error);
            alert('Erro ao gerar boleto.');
        });
    }

    function copiarPix() {
        const copyText = document.getElementById("pixCopiaCola");
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        navigator.clipboard.writeText(copyText.value);
        alert("Código PIX copiado!");
    }
</script>
@endpush