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

        {{-- Grade curricular real: curso → módulo → disciplina → aula. --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Conteúdo do curso</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Aulas organizadas por módulo e disciplina, conforme a matriz curricular.
                    </p>
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-300">
                    {{ $dados['grade']['aulas_concluidas'] }} / {{ $dados['grade']['total_aulas'] }} aulas concluídas
                </span>
            </div>

            <div class="space-y-3">
                @forelse($dados['grade']['modulos'] as $modulo)
                    <details class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" {{ $loop->first ? 'open' : '' }}>
                        <summary class="flex cursor-pointer items-center justify-between gap-3 bg-gray-50 px-4 py-3 font-semibold text-gray-900 dark:bg-gray-800 dark:text-white">
                            <span>{{ $modulo['titulo'] }}</span>
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $modulo['total_aulas'] }} aula(s)</span>
                        </summary>
                        <div class="space-y-4 p-4">
                            @forelse($modulo['disciplinas'] as $disciplina)
                                <div>
                                    <h4 class="mb-2 font-medium text-gray-800 dark:text-gray-100">{{ $disciplina['nome'] }}</h4>
                                    @if(empty($disciplina['aulas']))
                                        <p class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                            Nenhuma aula publicada nesta disciplina.
                                        </p>
                                    @else
                                        <ul class="space-y-2">
                                            @foreach($disciplina['aulas'] as $aula)
                                                <li>
                                                    @if($aula['liberada'])
                                                        <a href="{{ route('aluno.aula', $aula['id']) }}"
                                                           class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm transition hover:border-primary-400 hover:bg-primary-50 dark:border-gray-700 dark:hover:bg-primary-500/10">
                                                            <span class="font-medium text-gray-900 dark:text-white">{{ $aula['titulo'] }}</span>
                                                            <span class="shrink-0 text-xs {{ $aula['concluida'] ? 'text-success-600 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                                {{ $aula['concluida'] ? 'Concluída' : ($aula['duracao'] ?: 'Assistir') }}
                                                            </span>
                                                        </a>
                                                    @else
                                                        <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-4 py-3 text-sm dark:bg-gray-800">
                                                            <span class="font-medium text-gray-500 dark:text-gray-400">{{ $aula['titulo'] }}</span>
                                                            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">Bloqueada</span>
                                                        </div>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Este módulo ainda não tem disciplinas cadastradas.</p>
                            @endforelse
                        </div>
                    </details>
                @empty
                    <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        O conteúdo curricular deste curso ainda está sendo preparado.
                    </p>
                @endforelse
            </div>
        </section>

        {{-- Issue #32 — Frequência real do aluno (somente a própria), resumida por turma/disciplina. --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Frequência
            </h3>

            @php
                $frequencias = collect($dados['frequencias'] ?? []);
                $totais = [
                    'chamadas' => $frequencias->sum('chamadas_concluidas'),
                    'presentes' => $frequencias->sum('presentes'),
                    'faltas' => $frequencias->sum('faltas'),
                    'justificadas' => $frequencias->sum('faltas_justificadas'),
                ];
                $percentualGeral = $totais['chamadas'] > 0
                    ? round((($totais['presentes'] + $totais['justificadas']) / $totais['chamadas']) * 100, 2)
                    : 0;
            @endphp

            @if($frequencias->isNotEmpty())
                <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Chamadas</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $totais['chamadas'] }}</p>
                    </div>
                    <div class="rounded-lg bg-success-50 p-3 text-center dark:bg-success-500/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-success-700 dark:text-success-400">Presentes</p>
                        <p class="mt-1 text-2xl font-bold text-success-700 dark:text-success-400">{{ $totais['presentes'] }}</p>
                    </div>
                    <div class="rounded-lg bg-danger-50 p-3 text-center dark:bg-danger-500/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-danger-700 dark:text-danger-400">Faltas</p>
                        <p class="mt-1 text-2xl font-bold text-danger-700 dark:text-danger-400">{{ $totais['faltas'] }}</p>
                    </div>
                    <div class="rounded-lg bg-warning-50 p-3 text-center dark:bg-warning-500/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-warning-700 dark:text-warning-400">Faltas justif.</p>
                        <p class="mt-1 text-2xl font-bold text-warning-700 dark:text-warning-400">{{ $totais['justificadas'] }}</p>
                    </div>
                    <div class="col-span-2 rounded-lg bg-primary-50 p-3 text-center sm:col-span-1 dark:bg-primary-500/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-400">Frequência</p>
                        <p class="mt-1 text-2xl font-bold text-primary-700 dark:text-primary-400">{{ number_format($percentualGeral, 1, ',', '.') }}%</p>
                    </div>
                </div>

                <ul class="space-y-3">
                    @foreach($frequencias as $frequencia)
                        @php
                            $corPercentual = $frequencia['percentual'] >= 75
                                ? 'bg-success-500'
                                : ($frequencia['percentual'] >= 50 ? 'bg-warning-500' : 'bg-danger-500');
                        @endphp
                        <li class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-800">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $frequencia['disciplina'] }}</p>
                                    <p class="text-gray-500 dark:text-gray-400">{{ $frequencia['turma'] }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700">
                                    {{ number_format($frequencia['percentual'], 1, ',', '.') }}%
                                </span>
                            </div>

                            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-2 rounded-full {{ $corPercentual }}" style="width: {{ min(100, max(0, $frequencia['percentual'])) }}%"></div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $frequencia['chamadas_concluidas'] }} chamada(s) concluída(s)</span>
                                <span class="text-success-600 dark:text-success-400">{{ $frequencia['presentes'] }} presente(s)</span>
                                <span class="text-danger-600 dark:text-danger-400">{{ $frequencia['faltas'] }} falta(s)</span>
                                <span class="text-warning-600 dark:text-warning-400">{{ $frequencia['faltas_justificadas'] }} justificada(s)</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    Ainda não há chamadas de frequência concluídas para as suas turmas. Quando a Secretaria registrar e concluir as chamadas, o seu resumo de presença aparecerá aqui.
                </p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
