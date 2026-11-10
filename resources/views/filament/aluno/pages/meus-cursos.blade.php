<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Cabeçalho simples com o total real de matrículas ativas --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Matrículas ativas</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->matriculas->count() }}</p>
                </div>
                <div class="w-full sm:w-72">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="busca"
                        placeholder="Buscar por título do curso..."
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                </div>
            </div>
        </div>

        {{-- Lista de matrículas reais --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($this->matriculas as $matricula)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $matricula['curso_titulo'] }}
                        </h3>
                        <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-500/10 dark:text-success-400">
                            {{ $matricula['status_label'] }}
                        </span>
                    </div>

                    <dl class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500 dark:text-gray-400">Matrícula</dt>
                            <dd class="font-medium">{{ $matricula['numero_matricula'] ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500 dark:text-gray-400">Data da matrícula</dt>
                            <dd class="font-medium">{{ $matricula['data_matricula'] ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500 dark:text-gray-400">Modalidade</dt>
                            <dd class="font-medium">{{ $matricula['modalidade_label'] }}</dd>
                        </div>
                    </dl>

                    {{-- Turmas reais (legado + pivot, sem duplicar) --}}
                    <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Turmas
                        </p>

                        @if($matricula['possui_turma'])
                            <ul class="space-y-3">
                                @foreach($matricula['turmas'] as $turma)
                                    <li class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $turma['nome'] }}</p>
                                        <p class="text-gray-500 dark:text-gray-400">
                                            Período letivo: {{ $turma['periodo_letivo'] ?? 'a definir' }}
                                        </p>
                                        <p class="text-gray-500 dark:text-gray-400">
                                            Docente: {{ $turma['professor'] ?? 'a definir' }}
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                Sua turma ainda será definida pela Secretaria. Você será avisado assim que o vínculo for realizado.
                            </p>
                        @endif
                    </div>

                    <div class="mt-4">
                        <a
                            href="{{ $matricula['detalhe_url'] }}"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500"
                        >
                            Ver detalhes
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-xl bg-white py-12 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <x-filament::icon icon="heroicon-o-book-open" class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Nenhuma matrícula ativa</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if(filled($this->busca))
                            Nenhum curso corresponde à busca informada.
                        @else
                            Você ainda não possui matrículas ativas. Em caso de dúvidas, procure a Secretaria.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
