<x-filament-widgets::widget>
    @php
        /** @var array $resumo */
        $percentual = $resumo['total'] > 0
            ? (int) round(($resumo['enviados'] / $resumo['total']) * 100)
            : 0;
    @endphp

    <div
        role="region"
        aria-label="Documentos pendentes da inscrição"
        class="relative overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-amber-50 p-6 shadow-sm dark:border-amber-500/30 dark:from-amber-500/10 dark:via-gray-900 dark:to-amber-500/5"
    >
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-300" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                    </svg>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Você tem {{ $resumo['pendentes'] }}
                        {{ $resumo['pendentes'] === 1 ? 'documento pendente' : 'documentos pendentes' }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Envie os documentos da sua inscrição para a secretaria concluir a análise.
                    </p>

                    <dl class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
                        <div class="inline-flex items-center gap-1">
                            <dt class="font-medium">Enviados:</dt>
                            <dd>{{ $resumo['enviados'] }} de {{ $resumo['total'] }}</dd>
                        </div>
                        <div class="inline-flex items-center gap-1">
                            <dt class="font-medium">Em análise:</dt>
                            <dd>{{ $resumo['em_analise'] }}</dd>
                        </div>
                        <div class="inline-flex items-center gap-1">
                            <dt class="font-medium">Aprovados:</dt>
                            <dd>{{ $resumo['aprovados'] }}</dd>
                        </div>
                        <div class="inline-flex items-center gap-1">
                            <dt class="font-medium">Rejeitados:</dt>
                            <dd>{{ $resumo['rejeitados'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="flex flex-col items-stretch gap-3 md:items-end">
                <div class="w-full md:w-64" aria-label="Progresso do envio">
                    <div class="mb-1 flex items-center justify-between text-xs font-medium text-gray-700 dark:text-gray-200">
                        <span>{{ $resumo['enviados'] }} de {{ $resumo['total'] }} documentos enviados</span>
                        <span>{{ $percentual }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-amber-100 dark:bg-amber-500/20" role="progressbar"
                         aria-valuenow="{{ $percentual }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-amber-500 transition-all duration-500" style="width: {{ $percentual }}%"></div>
                    </div>
                </div>

                <a
                    href="{{ $url_central }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                >
                    Resolver pendências
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
