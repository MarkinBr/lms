<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $dados['curso_titulo'] }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $dados['modalidade_label'] }}</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-500/10 dark:text-success-400">
                    {{ $dados['status_label'] }}
                </span>
            </div>

            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Número da matrícula</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $dados['numero_matricula'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Data da matrícula</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $dados['data_matricula'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Turmas vinculadas
            </h3>

            @if($dados['possui_turma'])
                <ul class="space-y-3">
                    @foreach($dados['turmas'] as $turma)
                        <li class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-800">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $turma['nome'] }}</p>
                            <p class="text-gray-500 dark:text-gray-400">
                                Período letivo: {{ $turma['periodo_letivo'] ?? 'a definir' }}
                            </p>
                            <p class="text-gray-500 dark:text-gray-400">
                                Docente responsável: {{ $turma['professor'] ?? 'a definir' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    Sua turma ainda será definida pela Secretaria. Você será avisado assim que o vínculo for realizado.
                </p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
