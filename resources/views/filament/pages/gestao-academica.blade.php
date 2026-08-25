<x-filament-panels::page>
    @php
        $cursoResource = \App\Filament\Resources\CursoResource::class;
        $moduloResource = \App\Filament\Resources\ModuloResource::class;
        $disciplinaResource = \App\Filament\Resources\DisciplinaResource::class;
        $periodoLetivoResource = \App\Filament\Resources\PeriodoLetivoResource::class;
        $turmaResource = \App\Filament\Resources\TurmaResource::class;
        $matriculaResource = \App\Filament\Resources\MatriculaResource::class;
        $aulaAoVivoResource = \App\Filament\Resources\AulaAoVivoResource::class;
    @endphp

    <div class="ga-canvas">
        {{-- Cabeçalho da central --}}
        <header class="ga-header">
            <div>
                <p class="ga-eyebrow">Painel do administrador</p>
                <h2 class="ga-title">Central de Gestão Acadêmica</h2>
                <p class="ga-subtitle">
                    Estrutura curricular, períodos letivos, turmas e matrículas em um só lugar.
                    Todos os cursos da instituição são híbridos (on-line + presencial).
                </p>
            </div>
            <div class="ga-header-actions">
                <a href="{{ $cursoResource::getUrl('create') }}" class="ga-btn ga-btn--gold">
                    <x-filament::icon icon="heroicon-o-plus" class="ga-btn-icon" />
                    Novo curso
                </a>
                <a href="{{ $periodoLetivoResource::getUrl('index') }}" class="ga-btn ga-btn--ghost">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="ga-btn-icon" />
                    Períodos letivos
                </a>
            </div>
        </header>

        {{-- Indicadores reais --}}
        <section aria-label="Indicadores acadêmicos" class="ga-grid ga-grid--stats">
            <a href="{{ $cursoResource::getUrl('index') }}" class="ga-stat">
                <span class="ga-stat-label">Cursos ativos</span>
                <span class="ga-stat-value">{{ $estatisticas['cursos'] }}</span>
                <span class="ga-stat-hint">modalidade híbrida</span>
            </a>
            <a href="{{ $moduloResource::getUrl('index') }}" class="ga-stat">
                <span class="ga-stat-label">Módulos</span>
                <span class="ga-stat-value">{{ $estatisticas['modulos'] }}</span>
            </a>
            <a href="{{ $disciplinaResource::getUrl('index') }}" class="ga-stat">
                <span class="ga-stat-label">Disciplinas ativas</span>
                <span class="ga-stat-value">{{ $estatisticas['disciplinas'] }}</span>
            </a>
            <div class="ga-stat ga-stat--static">
                <span class="ga-stat-label">Aulas cadastradas</span>
                <span class="ga-stat-value">{{ $estatisticas['aulas'] }}</span>
            </div>
            <a href="{{ $periodoLetivoResource::getUrl('index') }}" class="ga-stat">
                <span class="ga-stat-label">Períodos letivos</span>
                <span class="ga-stat-value">{{ $estatisticas['periodos'] }}</span>
            </a>
            <a href="{{ $turmaResource::getUrl('index') }}" class="ga-stat">
                <span class="ga-stat-label">Turmas / ofertas</span>
                <span class="ga-stat-value">{{ $estatisticas['turmas'] }}</span>
            </a>
            <a href="{{ $matriculaResource::getUrl('index') }}" class="ga-stat">
                <span class="ga-stat-label">Matrículas ativas</span>
                <span class="ga-stat-value">{{ $estatisticas['matriculas_ativas'] }}</span>
            </a>
            <a href="{{ $aulaAoVivoResource::getUrl('index') }}" class="ga-stat">
                <span class="ga-stat-label">Aulas ao vivo (híbridas)</span>
                <span class="ga-stat-value">{{ $estatisticas['aulas_ao_vivo'] }}</span>
            </a>
        </section>

        {{-- Atalhos operacionais --}}
        <section aria-label="Atalhos operacionais" class="ga-grid ga-grid--shortcuts">
            <a href="{{ $cursoResource::getUrl('index') }}" class="ga-shortcut">
                <x-filament::icon icon="heroicon-o-academic-cap" class="ga-shortcut-icon" />
                <span class="ga-shortcut-title">Cursos</span>
                <span class="ga-shortcut-desc">Catálogo, matrizes e módulos</span>
            </a>
            <a href="{{ $disciplinaResource::getUrl('index') }}" class="ga-shortcut">
                <x-filament::icon icon="heroicon-o-book-open" class="ga-shortcut-icon" />
                <span class="ga-shortcut-title">Disciplinas</span>
                <span class="ga-shortcut-desc">Conteúdo e aulas por disciplina</span>
            </a>
            <a href="{{ $periodoLetivoResource::getUrl('index') }}" class="ga-shortcut">
                <x-filament::icon icon="heroicon-o-calendar-days" class="ga-shortcut-icon" />
                <span class="ga-shortcut-title">Períodos letivos</span>
                <span class="ga-shortcut-desc">Semestres e anos letivos</span>
            </a>
            <a href="{{ $turmaResource::getUrl('index') }}" class="ga-shortcut">
                <x-filament::icon icon="heroicon-o-user-group" class="ga-shortcut-icon" />
                <span class="ga-shortcut-title">Turmas</span>
                <span class="ga-shortcut-desc">Ofertas por período e professor</span>
            </a>
            <a href="{{ $matriculaResource::getUrl('index') }}" class="ga-shortcut">
                <x-filament::icon icon="heroicon-o-identification" class="ga-shortcut-icon" />
                <span class="ga-shortcut-title">Matrículas</span>
                <span class="ga-shortcut-desc">Vínculos de alunos</span>
            </a>
            <a href="{{ $aulaAoVivoResource::getUrl('index') }}" class="ga-shortcut">
                <x-filament::icon icon="heroicon-o-video-camera" class="ga-shortcut-icon" />
                <span class="ga-shortcut-title">Aulas ao vivo</span>
                <span class="ga-shortcut-desc">Encontros síncronos híbridos</span>
            </a>
        </section>

        {{-- Pendências estruturais reais --}}
        @php $totalPendencias = collect($pendencias)->sum('count'); @endphp
        @if ($totalPendencias > 0)
            <section aria-label="Pendências estruturais" class="ga-alerts">
                <h3 class="ga-section-title">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="ga-section-icon" />
                    Pendências estruturais ({{ $totalPendencias }})
                </h3>

                @if ($pendencias['disciplinas_sem_modulo']['count'] > 0)
                    <div class="ga-alert ga-alert--warning">
                        <p>
                            <strong>{{ $pendencias['disciplinas_sem_modulo']['count'] }}</strong>
                            disciplina(s) sem módulo vinculado.
                        </p>
                        <div class="ga-alert-links">
                            @foreach ($pendencias['disciplinas_sem_modulo']['ids'] as $id)
                                <a href="{{ $disciplinaResource::getUrl('edit', ['record' => $id]) }}">#{{ $id }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($pendencias['aulas_sem_disciplina']['count'] > 0)
                    <div class="ga-alert ga-alert--warning">
                        <p>
                            <strong>{{ $pendencias['aulas_sem_disciplina']['count'] }}</strong>
                            aula(s) legada(s) sem disciplina. Não são remapeadas automaticamente;
                            vincule manualmente após revisão.
                        </p>
                        <div class="ga-alert-links">
                            <a href="{{ $moduloResource::getUrl('index') }}">
                                Revisar nos módulos
                            </a>
                        </div>
                    </div>
                @endif

                @if ($pendencias['turmas_sem_periodo']['count'] > 0)
                    <div class="ga-alert ga-alert--warning">
                        <p>
                            <strong>{{ $pendencias['turmas_sem_periodo']['count'] }}</strong>
                            turma(s) sem período letivo.
                        </p>
                        <div class="ga-alert-links">
                            @foreach ($pendencias['turmas_sem_periodo']['ids'] as $id)
                                <a href="{{ $turmaResource::getUrl('edit', ['record' => $id]) }}">#{{ $id }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($pendencias['turmas_sem_disciplina']['count'] > 0)
                    <div class="ga-alert ga-alert--warning">
                        <p>
                            <strong>{{ $pendencias['turmas_sem_disciplina']['count'] }}</strong>
                            turma(s) sem disciplina.
                        </p>
                        <div class="ga-alert-links">
                            @foreach ($pendencias['turmas_sem_disciplina']['ids'] as $id)
                                <a href="{{ $turmaResource::getUrl('edit', ['record' => $id]) }}">#{{ $id }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

        {{-- Árvore curricular do curso selecionado --}}
        <section aria-label="Árvore curricular" class="ga-tree-section">
            <div class="ga-tree-toolbar">
                <h3 class="ga-section-title">
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="ga-section-icon" />
                    Árvore curricular
                </h3>
                <div class="ga-tree-filters">
                    <label class="ga-field">
                        <span class="ga-field-label">Buscar curso</span>
                        <input
                            type="search"
                            wire:model.live.debounce.400ms="buscaCurso"
                            class="ga-input"
                            placeholder="Nome do curso…"
                        >
                    </label>
                    <label class="ga-field">
                        <span class="ga-field-label">Curso</span>
                        <select wire:model.live="cursoId" class="ga-input">
                            @foreach ($cursos as $curso)
                                <option value="{{ $curso->id }}">{{ $curso->titulo }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            @if ($cursos->isEmpty())
                <div class="ga-empty" data-testid="ga-empty-state">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="ga-empty-icon" />
                    <p class="ga-empty-title">Nenhum curso ativo encontrado</p>
                    <p class="ga-empty-text">
                        Cadastre o primeiro curso para começar a montar a árvore curricular.
                    </p>
                    <a href="{{ $cursoResource::getUrl('create') }}" class="ga-btn ga-btn--gold">
                        Criar curso
                    </a>
                </div>
            @elseif ($arvore === null)
                <div class="ga-empty" data-testid="ga-empty-state">
                    <p class="ga-empty-title">Selecione um curso</p>
                    <p class="ga-empty-text">Escolha um curso acima para visualizar a estrutura.</p>
                </div>
            @else
                <div
                    class="ga-tree-loading"
                    wire:loading.flex
                    wire:target="buscaCurso, cursoId"
                    role="status"
                    aria-live="polite"
                >
                    <span class="ga-visually-hidden">Atualizando a árvore curricular…</span>
                    <span class="ga-skeleton ga-skeleton--wide" aria-hidden="true"></span>
                    <span class="ga-skeleton ga-skeleton--medium" aria-hidden="true"></span>
                </div>

                <div
                    class="ga-tree"
                    wire:loading.class="ga-tree--loading"
                    wire:target="buscaCurso, cursoId"
                    aria-live="polite"
                >
                    <div class="ga-tree-course">
                        <div>
                            <p class="ga-tree-course-title">{{ $arvore['curso']['titulo'] }}</p>
                            <p class="ga-tree-course-meta">
                                {{ $arvore['curso']['modulos_count'] }} módulo(s) ·
                                {{ $arvore['curso']['disciplinas_count'] }} disciplina(s) ·
                                {{ $arvore['curso']['aulas_count'] }} aula(s)
                            </p>
                        </div>
                        <span class="ga-badge ga-badge--hybrid">Híbrido</span>
                    </div>

                    @forelse ($arvore['modulos'] as $modulo)
                        <details class="ga-modulo" open>
                            <summary class="ga-modulo-summary">
                                <span class="ga-modulo-ordem">{{ $modulo['ordem'] }}</span>
                                <span class="ga-modulo-titulo">{{ $modulo['titulo'] }}</span>
                                <span class="ga-badge">{{ count($modulo['disciplinas']) }} disciplina(s)</span>
                            </summary>
                            <div class="ga-modulo-actions">
                                <a
                                    href="{{ $moduloResource::getUrl('edit', ['record' => $modulo['id']]) }}"
                                    class="ga-link"
                                >Editar módulo</a>
                            </div>

                            <ul class="ga-disciplinas">
                                @forelse ($modulo['disciplinas'] as $disciplina)
                                    <li class="ga-disciplina">
                                        <div class="ga-disciplina-head">
                                            <span class="ga-disciplina-nome">
                                                @if ($disciplina['codigo'])
                                                    <span class="ga-disciplina-codigo">{{ $disciplina['codigo'] }}</span>
                                                @endif
                                                {{ $disciplina['nome'] }}
                                            </span>
                                            @unless ($disciplina['ativa'])
                                                <span class="ga-badge ga-badge--muted">Inativa</span>
                                            @endunless
                                            <span class="ga-badge">{{ count($disciplina['aulas']) }} aula(s)</span>
                                            <a
                                                href="{{ $disciplinaResource::getUrl('edit', ['record' => $disciplina['id']]) }}"
                                                class="ga-link"
                                            >Gerenciar disciplina e aulas</a>
                                        </div>

                                        @if (count($disciplina['aulas']) > 0)
                                            <ul class="ga-aulas">
                                                @foreach ($disciplina['aulas'] as $aula)
                                                    <li class="ga-aula">
                                                        <span class="ga-aula-ordem">{{ $aula['ordem'] }}</span>
                                                        <span class="ga-aula-titulo">{{ $aula['titulo'] }}</span>
                                                        <span class="ga-badge ga-badge--soft">{{ $aula['tipo_conteudo'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @empty
                                    <li class="ga-disciplina ga-disciplina--empty">
                                        Nenhuma disciplina neste módulo.
                                        <a class="ga-link" href="{{ $moduloResource::getUrl('edit', ['record' => $modulo['id']]) }}">
                                            Adicionar pelo módulo
                                        </a>
                                    </li>
                                @endforelse
                            </ul>
                        </details>
                    @empty
                        <div class="ga-empty">
                            <p class="ga-empty-title">Curso sem módulos</p>
                            <p class="ga-empty-text">Crie o primeiro módulo para estruturar o curso.</p>
                            <a href="{{ $moduloResource::getUrl('create') }}" class="ga-btn ga-btn--ghost">Criar módulo</a>
                        </div>
                    @endforelse

                    @if (count($arvore['aulas_legadas']) > 0)
                        <div class="ga-alert ga-alert--neutral" role="note">
                            <p>
                                <strong>{{ count($arvore['aulas_legadas']) }}</strong>
                                aula(s) legada(s) deste curso ainda sem disciplina.
                                Elas permanecem acessíveis pelo módulo até serem vinculadas.
                            </p>
                            <div class="ga-alert-links">
                                @foreach (collect($arvore['aulas_legadas'])->pluck('modulo_id')->unique() as $moduloId)
                                    <a href="{{ $moduloResource::getUrl('edit', ['record' => $moduloId]) }}">
                                        Corrigir no módulo #{{ $moduloId }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
