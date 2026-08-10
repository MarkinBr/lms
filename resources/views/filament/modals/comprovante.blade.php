<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            📄 Comprovante de Pagamento
        </h3>
        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <p>
                <span class="font-medium">Aluno:</span> 
                <strong class="text-gray-900 dark:text-white">{{ $mensalidade->matricula->user->name }}</strong>
            </p>
            <p>
                <span class="font-medium">Valor:</span> 
                <strong class="text-green-600 dark:text-green-400">R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}</strong>
            </p>
            <p>
                <span class="font-medium">Data Pagamento:</span> 
                <strong class="text-gray-900 dark:text-white">{{ $mensalidade->data_pagamento?->format('d/m/Y') ?? '-' }}</strong>
            </p>
            <p>
                <span class="font-medium">Método:</span> 
                <strong class="text-gray-900 dark:text-white">
                    @switch($mensalidade->metodo_pagamento)
                        @case('dinheiro') 💵 Dinheiro @break
                        @case('pix') 📱 PIX @break
                        @case('cartao_credito') 💳 Cartão de Crédito @break
                        @case('cartao_debito') 💳 Cartão de Débito @break
                        @case('boleto') 📄 Boleto @break
                        @case('bolbradesco') 📄 Boleto Bradesco @break
                        @case('transferencia') 🏦 Transferência Bancária @break
                        @case('mercadopago') 🔵 Mercado Pago @break
                        @default {{ $mensalidade->metodo_pagamento ?? 'Não informado' }}
                    @endswitch
                </strong>
            </p>
        </div>
    </div>
    
    <!-- Comprovante -->
    @if($mensalidade->comprovante)
        <div class="mb-6">
            <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-900">
                @php
                    $extension = pathinfo($mensalidade->comprovante, PATHINFO_EXTENSION);
                    $isPdf = strtolower($extension) === 'pdf' || \Illuminate\Support\Str::endsWith($mensalidade->comprovante, '.pdf');
                @endphp

                @if($isPdf)
                    <!-- PDF Embed -->
                    <div class="relative">
                        <embed 
                            src="{{ Storage::url($mensalidade->comprovante) }}" 
                            type="application/pdf" 
                            width="100%" 
                            height="600px"
                            class="dark:invert"
                        />
                        <!-- Fallback para navegadores que não suportam embed -->
                        <div class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800" style="z-index: -1;">
                            <div class="text-center">
                                <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">
                                    Documento PDF
                                </p>
                                <a 
                                    href="{{ Storage::url($mensalidade->comprovante) }}" 
                                    target="_blank"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                >
                                    Abrir PDF em Nova Aba
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Imagem -->
                    <div class="flex justify-center items-center p-4 bg-gray-50 dark:bg-gray-900">
                        <img 
                            src="{{ Storage::url($mensalidade->comprovante) }}" 
                            alt="Comprovante de Pagamento" 
                            class="max-w-full max-h-[600px] w-auto h-auto rounded-lg shadow-lg object-contain"
                        />
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Botões de Ação -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <a 
                href="{{ Storage::url($mensalidade->comprovante) }}" 
                download 
                class="inline-flex items-center justify-center px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                💾 Baixar Arquivo
            </a>

            <a 
                href="{{ Storage::url($mensalidade->comprovante) }}" 
                target="_blank"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                🔍 Abrir em Nova Aba
            </a>

            <button 
                onclick="window.print()"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                🖨️ Imprimir
            </button>
        </div>

        <!-- Informações do Arquivo -->
        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs text-gray-600 dark:text-gray-400">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <span class="font-medium">Tipo:</span> 
                    {{ strtoupper($extension ?? 'Desconhecido') }}
                </div>
                <div>
                    <span class="font-medium">Status:</span> 
                    <span class="text-green-600 dark:text-green-400 font-semibold">✅ Anexado</span>
                </div>
            </div>
        </div>
    @else
        <!-- Estado Vazio -->
        <div class="text-center py-12 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900">
            <svg class="w-20 h-20 mx-auto mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-600 dark:text-gray-400 font-medium mb-2">
                ⚠️ Nenhum comprovante anexado
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-500">
                O comprovante ainda não foi enviado para esta mensalidade
            </p>
        </div>
    @endif

    <!-- Observações -->
    @if($mensalidade->observacao)
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                📝 Observações
            </h4>
            <p class="text-sm text-blue-800 dark:text-blue-200 whitespace-pre-wrap">
                {{ $mensalidade->observacao }}
            </p>
        </div>
    @endif

    <!-- Dados da Transação (se houver) -->
    @if($mensalidade->transacao_id)
        <div class="mt-6 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
            <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-2">
                🔵 Informações da Transação
            </h4>
            <div class="space-y-1 text-sm text-purple-800 dark:text-purple-200">
                <p>
                    <span class="font-medium">ID Transação:</span> 
                    <code class="bg-purple-100 dark:bg-purple-900 px-2 py-0.5 rounded">{{ $mensalidade->transacao_id }}</code>
                </p>
                @if($mensalidade->linha_digitavel)
                    <p>
                        <span class="font-medium">Linha Digitável:</span> 
                        <code class="bg-purple-100 dark:bg-purple-900 px-2 py-0.5 rounded text-xs">{{ $mensalidade->linha_digitavel }}</code>
                    </p>
                @endif
            </div>
        </div>
    @endif
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .p-6, .p-6 * {
        visibility: visible;
    }
    .p-6 {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    button {
        display: none !important;
    }
    a[download] {
        display: none !important;
    }
}
</style>