<div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            📱 Pagamento via PIX
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Escaneie o QR Code ou copie o código abaixo
        </p>
    </div>

    <!-- QR Code -->
    @if($mensalidade->pix_qr_base64)
        <div class="flex flex-col items-center justify-center p-6 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                Escaneie o QR Code
            </h4>
            <img 
                src="data:image/png;base64,{{ $mensalidade->pix_qr_base64 }}" 
                alt="QR Code PIX" 
                class="w-64 h-64 border-4 border-gray-300 dark:border-gray-600 rounded-lg shadow-lg"
            >
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                Use o app do seu banco para escanear
            </p>
        </div>
    @endif

    <!-- Código Copia e Cola -->
    @if($mensalidade->pix_copia_cola)
        <div class="space-y-2">
            <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                📋 Código PIX Copia e Cola
            </h4>
            <div class="relative">
                <textarea 
                    readonly 
                    rows="4" 
                    class="w-full p-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-sm font-mono text-gray-900 dark:text-gray-100 resize-none"
                    id="pixCode"
                >{{ $mensalidade->pix_copia_cola }}</textarea>
                <button 
                    type="button"
                    onclick="copyPixCode()"
                    class="absolute top-2 right-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Copiar
                </button>
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-400">
                💡 Copie o código acima e cole no app do seu banco na opção PIX > Copia e Cola
            </p>
        </div>
    @endif

    <!-- Informações da Mensalidade -->
    <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">💰 Valor</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">
                R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">📅 Vencimento</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ $mensalidade->data_vencimento->format('d/m/Y') }}
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">👤 Aluno</p>
            <p class="font-semibold text-gray-900 dark:text-white">
                {{ $mensalidade->matricula->user->name }}
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">📊 Status</p>
            <p class="font-semibold text-yellow-600 dark:text-yellow-400">
                {{ $mensalidade->status }}
            </p>
        </div>
    </div>

    <!-- Instruções -->
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
            ℹ️ Como pagar via PIX
        </h4>
        <ol class="list-decimal list-inside space-y-1 text-sm text-blue-800 dark:text-blue-200">
            <li>Abra o app do seu banco</li>
            <li>Escolha a opção <strong>PIX</strong></li>
            <li>Selecione <strong>Pix Copia e Cola</strong></li>
            <li>Cole o código copiado acima</li>
            <li>Confirme o valor e finalize o pagamento</li>
        </ol>
    </div>

    <!-- Link Externo (Mercado Pago) -->
    @if($mensalidade->link_pagamento)
        <div class="text-center">
            <a 
                href="{{ $mensalidade->link_pagamento }}" 
                target="_blank" 
                class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                Abrir no Mercado Pago
            </a>
        </div>
    @endif

    <!-- Botão Imprimir -->
    <div class="text-center print-hide">
        <button 
            onclick="window.print()"
            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Imprimir
        </button>
    </div>
</div>

<script>
function copyPixCode() {
    const textarea = document.getElementById('pixCode');
    textarea.select();
    textarea.setSelectionRange(0, 99999); // Para mobile
    
    // Tenta copiar usando a API moderna primeiro
    if (navigator.clipboard) {
        navigator.clipboard.writeText(textarea.value).then(() => {
            showCopyFeedback();
        }).catch(() => {
            // Fallback para execCommand
            document.execCommand('copy');
            showCopyFeedback();
        });
    } else {
        // Fallback para navegadores antigos
        document.execCommand('copy');
        showCopyFeedback();
    }
}

function showCopyFeedback() {
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;
    
    button.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>Copiado!</span>
    `;
    button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
    button.classList.add('bg-green-600', 'hover:bg-green-700');
    
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.classList.remove('bg-green-600', 'hover:bg-green-700');
        button.classList.add('bg-blue-600', 'hover:bg-blue-700');
    }, 2000);
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .space-y-6, .space-y-6 * {
        visibility: visible;
    }
    .space-y-6 {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .print-hide {
        display: none !important;
    }
    button {
        display: none !important;
    }
}
</style>