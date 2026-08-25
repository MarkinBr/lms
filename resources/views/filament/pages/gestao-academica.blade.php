<div
    class="ga-canvas ga-standalone-layout"
    x-data="{ menuOpen: false }"
    x-on:keydown.escape.window="menuOpen = false"
>
    @php
        $cursoResource = \App\Filament\Resources\CursoResource::class;
        $moduloResource = \App\Filament\Resources\ModuloResource::class;
        $disciplinaResource = \App\Filament\Resources\DisciplinaResource::class;
        $periodoLetivoResource = \App\Filament\Resources\PeriodoLetivoResource::class;
        $turmaResource = \App\Filament\Resources\TurmaResource::class;
        $matriculaResource = \App\Filament\Resources\MatriculaResource::class;
        $alunoResource = \App\Filament\Resources\AlunoResource::class;
        $userResource = \App\Filament\Resources\UserResource::class;
        $aulaAoVivoResource = \App\Filament\Resources\AulaAoVivoResource::class;

        $secoes = [
            ['slug' => 'visao-geral',   'titulo' => 'Visão Geral',         'grupo' => 'Painel',                  'icone' => 'heroicon-o-squares-2x2'],
            ['slug' => 'cursos',        'titulo' => 'Cursos',              'grupo' => 'Estrutura Pedagógica',    'icone' => 'heroicon-o-book-open'],
            ['slug' => 'estrutura',     'titulo' => 'Estrutura Curricular','grupo' => 'Estrutura Pedagógica',    'icone' => 'heroicon-o-list-bullet'],
            ['slug' => 'operacao',      'titulo' => 'Períodos & Turmas',   'grupo' => 'Estrutura Pedagógica',    'icone' => 'heroicon-o-calendar-days'],
            ['slug' => 'pessoas',       'titulo' => 'Pessoas',             'grupo' => 'Pessoas',                 'icone' => 'heroicon-o-user-group'],
            ['slug' => 'acompanhamento','titulo' => 'Acompanhamento',      'grupo' => 'Turmas & Acompanhamento', 'icone' => 'heroicon-o-chart-bar'],
            ['slug' => 'documentos',    'titulo' => 'Avaliação & Documentos','grupo' => 'Avaliação & Documentos','icone' => 'heroicon-o-document-text'],
        ];

        $gruposNav = collect($secoes)->groupBy('grupo');
        $secaoAtiva = $this->secao;
        $totalPendencias = collect($pendencias)->sum('count');
        $secaoAtual = collect($secoes)->firstWhere('slug', $secaoAtiva) ?? $secoes[0];
        $nomeAdministrador = auth()->user()?->name ?? 'Administrador';
        $iniciaisAdministrador = collect(preg_split('/\s+/', trim($nomeAdministrador)))
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');
    @endphp

    <a class="ga-skip-link" href="#ga-conteudo">Pular para o conteúdo</a>

    {{-- Navegação exclusiva da Central: não é a sidebar global do Filament. --}}
    <aside
        id="ga-navegacao"
        class="ga-localnav"
        x-bind:class="{ 'is-open': menuOpen }"
        aria-label="Navegação da gestão acadêmica"
    >
        <a class="ga-brand" href="{{ filament()->getUrl() }}" aria-label="Voltar ao painel administrativo">
            <span class="ga-brand-mark" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-academic-cap" />
            </span>
            <span class="ga-brand-copy">
                <span class="ga-brand-title">Gestão Acadêmica</span>
                <span class="ga-brand-subtitle">CETSADP · Ambiente administrativo</span>
            </span>
        </a>

        <div class="ga-localnav-scroll">
            @foreach ($gruposNav as $grupo => $itens)
                <div class="ga-localnav-section">
                    <p class="ga-localnav-title">{{ $grupo }}</p>
                    @foreach ($itens as $item)
                        @php $ativo = $secaoAtiva === $item['slug']; @endphp
                        <button
                            type="button"
                            wire:click="trocarSecao('{{ $item['slug'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="trocarSecao"
                            x-on:click="menuOpen = false"
                            class="ga-localnav-item {{ $ativo ? 'is-active' : '' }}"
                            @if ($ativo) aria-current="page" @endif
                        >
                            <x-filament::icon icon="{{ $item['icone'] }}" class="ga-localnav-icon" />
                            <span>{{ $item['titulo'] }}</span>
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="ga-sidebar-foot">
            <span class="ga-status-dot" aria-hidden="true"></span>
            <span>Sistema acadêmico conectado</span>
        </div>
    </aside>

    <button
        type="button"
        class="ga-nav-overlay"
        x-cloak
        x-show="menuOpen"
        x-on:click="menuOpen = false"
        aria-label="Fechar menu da gestão acadêmica"
    ></button>

    <div class="ga-main">
        <header class="ga-topbar">
            <div class="ga-topbar-leading">
                <button
                    type="button"
                    class="ga-menu-toggle"
                    x-on:click="menuOpen = ! menuOpen"
                    x-bind:aria-expanded="menuOpen.toString()"
                    aria-controls="ga-navegacao"
                    aria-label="Abrir menu da gestão acadêmica"
                >
                    <x-filament::icon icon="heroicon-o-bars-3" />
                </button>
                <div class="ga-topbar-heading">
                    <h1>Central de Gestão Acadêmica</h1>
                    <p>{{ $secaoAtual['titulo'] }} · Dados institucionais em tempo real</p>
                </div>
            </div>

            <div class="ga-topbar-actions">
                <a class="ga-back-link" href="{{ filament()->getUrl() }}">
                    <x-filament::icon icon="heroicon-o-arrow-left" />
                    <span>Voltar ao painel</span>
                </a>
                <div class="ga-admin-profile" aria-label="Administrador autenticado">
                    <span class="ga-admin-avatar" aria-hidden="true">{{ $iniciaisAdministrador ?: 'AD' }}</span>
                    <span class="ga-admin-copy">
                        <strong>{{ $nomeAdministrador }}</strong>
                        <small>Administrador</small>
                    </span>
                </div>
            </div>
        </header>

        <main
            id="ga-conteudo"
            class="ga-content"
            wire:loading.class="ga-content--loading"
            wire:target="trocarSecao"
        >
            {{-- Cabeçalho institucional da seção --}}
            <section class="ga-hero" aria-labelledby="ga-titulo-secao">
                <div class="ga-hero-body">
                    <p class="ga-eyebrow">Painel do administrador</p>
                    <h2 id="ga-titulo-secao" class="ga-title">Gestão acadêmica integrada</h2>
                    <p class="ga-subtitle">
                        Cursos, módulos, disciplinas, aulas, turmas e matrículas — toda a gestão
                        acadêmica do CETSADP em um único ambiente. Todos os cursos são híbridos
                        (on-line + presencial).
                    </p>
                </div>
                <div class="ga-hero-actions">
                    <a href="{{ $cursoResource::getUrl('create') }}" class="ga-btn ga-btn--gold">
                        <x-filament::icon icon="heroicon-o-plus" class="ga-btn-icon" />
                        Novo curso
                    </a>
                    <a href="{{ $matriculaResource::getUrl('create') }}" class="ga-btn ga-btn--ghost">
                        <x-filament::icon icon="heroicon-o-identification" class="ga-btn-icon" />
                        Nova matrícula
                    </a>
                </div>
            </section>

                <div
                    class="ga-section-loading"
                    wire:loading.flex
                    wire:target="trocarSecao"
                    role="status"
                    aria-live="polite"
                >
                    <span class="ga-visually-hidden">Carregando a seção…</span>
                    <span class="ga-skeleton ga-skeleton--wide" aria-hidden="true"></span>
                    <span class="ga-skeleton ga-skeleton--medium" aria-hidden="true"></span>
                </div>

                {{-- ======================= VISÃO GERAL ======================= --}}
                @if ($secaoAtiva === 'visao-geral')
                    <section aria-label="Visão geral" class="ga-view">
                        <div class="ga-section-heading">
                            <h3>Estrutura &amp; Operação</h3>
                            <span>Indicadores reais</span>
                        </div>
                        <div class="ga-stats-grid">
                            <a href="{{ $cursoResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-book-open" class="ga-stat-icon" />
                                <span class="ga-stat-name">Cursos</span>
                                <span class="ga-stat-num">{{ $estatisticas['cursos'] }}</span>
                                <span class="ga-stat-hint">cursos ativos</span>
                                <span class="ga-stat-cta">Gerenciar cursos</span>
                            </a>
                            <a href="{{ $moduloResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-squares-2x2" class="ga-stat-icon" />
                                <span class="ga-stat-name">Módulos</span>
                                <span class="ga-stat-num">{{ $estatisticas['modulos'] }}</span>
                                <span class="ga-stat-hint">módulos cadastrados</span>
                                <span class="ga-stat-cta">Gerenciar módulos</span>
                            </a>
                            <a href="{{ $disciplinaResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-document-text" class="ga-stat-icon" />
                                <span class="ga-stat-name">Disciplinas</span>
                                <span class="ga-stat-num">{{ $estatisticas['disciplinas'] }}</span>
                                <span class="ga-stat-hint">disciplinas ativas</span>
                                <span class="ga-stat-cta">Gerenciar disciplinas</span>
                            </a>
                            <a href="{{ $turmaResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-user-group" class="ga-stat-icon" />
                                <span class="ga-stat-name">Turmas</span>
                                <span class="ga-stat-num">{{ $estatisticas['turmas'] }}</span>
                                <span class="ga-stat-hint">turmas / ofertas</span>
                                <span class="ga-stat-cta">Gerenciar turmas</span>
                            </a>
                            <a href="{{ $matriculaResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-identification" class="ga-stat-icon" />
                                <span class="ga-stat-name">Matrículas</span>
                                <span class="ga-stat-num">{{ $estatisticas['matriculas_ativas'] }}</span>
                                <span class="ga-stat-hint">matrículas ativas</span>
                                <span class="ga-stat-cta">Gerenciar matrículas</span>
                            </a>
                            <a href="{{ $periodoLetivoResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-calendar-days" class="ga-stat-icon" />
                                <span class="ga-stat-name">Períodos</span>
                                <span class="ga-stat-num">{{ $estatisticas['periodos'] }}</span>
                                <span class="ga-stat-hint">períodos letivos</span>
                                <span class="ga-stat-cta">Gerenciar períodos</span>
                            </a>
                            <a href="{{ $aulaAoVivoResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-video-camera" class="ga-stat-icon" />
                                <span class="ga-stat-name">Aulas ao vivo</span>
                                <span class="ga-stat-num">{{ $estatisticas['aulas_ao_vivo'] }}</span>
                                <span class="ga-stat-hint">encontros híbridos</span>
                                <span class="ga-stat-cta">Gerenciar ao vivo</span>
                            </a>
                            <a href="{{ $disciplinaResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-play" class="ga-stat-icon" />
                                <span class="ga-stat-name">Aulas</span>
                                <span class="ga-stat-num">{{ $estatisticas['aulas'] }}</span>
                                <span class="ga-stat-hint">aulas cadastradas</span>
                                <span class="ga-stat-cta">Ver disciplinas</span>
                            </a>
                        </div>

                        <div class="ga-section-heading">
                            <h3>Pessoas</h3>
                            <span>Indicadores reais</span>
                        </div>
                        <div class="ga-stats-grid ga-stats-grid--two">
                            <a href="{{ $userResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-briefcase" class="ga-stat-icon" />
                                <span class="ga-stat-name">Professores</span>
                                <span class="ga-stat-num">{{ $estatisticas['professores'] }}</span>
                                <span class="ga-stat-hint">professores ativos</span>
                                <span class="ga-stat-cta">Gerenciar professores</span>
                            </a>
                            <a href="{{ $alunoResource::getUrl('index') }}" class="ga-stat-box">
                                <x-filament::icon icon="heroicon-o-user" class="ga-stat-icon" />
                                <span class="ga-stat-name">Alunos</span>
                                <span class="ga-stat-num">{{ $estatisticas['alunos'] }}</span>
                                <span class="ga-stat-hint">alunos ativos</span>
                                <span class="ga-stat-cta">Gerenciar alunos</span>
                            </a>
                        </div>

                        {{-- Pendências reais --}}
                        <div class="ga-card">
                            <div class="ga-section-heading ga-section-heading--flush">
                                <h3>Pendências &amp; Atividade</h3>
                                <span>Ação recomendada</span>
                            </div>
                            @if ($totalPendencias > 0)
                                <div class="ga-pending-list">
                                    @if ($pendencias['disciplinas_sem_modulo']['count'] > 0)
                                        <div class="ga-pending-item">
                                            <div class="ga-pending-ic ga-pending-ic--gold">
                                                <x-filament::icon icon="heroicon-o-document-text" class="ga-pending-icon" />
                                            </div>
                                            <p class="ga-pending-text">
                                                <strong>{{ $pendencias['disciplinas_sem_modulo']['count'] }}</strong>
                                                disciplina(s) sem módulo vinculado
                                            </p>
                                            <div class="ga-pending-links">
                                                @foreach ($pendencias['disciplinas_sem_modulo']['ids'] as $id)
                                                    <a class="ga-pending-link" href="{{ $disciplinaResource::getUrl('edit', ['record' => $id]) }}">#{{ $id }}</a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($pendencias['aulas_sem_disciplina']['count'] > 0)
                                        <div class="ga-pending-item">
                                            <div class="ga-pending-ic ga-pending-ic--blue">
                                                <x-filament::icon icon="heroicon-o-play" class="ga-pending-icon" />
                                            </div>
                                            <p class="ga-pending-text">
                                                <strong>{{ $pendencias['aulas_sem_disciplina']['count'] }}</strong>
                                                aula(s) legada(s) sem disciplina — vincule após revisão
                                            </p>
                                            <div class="ga-pending-links">
                                                <a class="ga-pending-link" href="{{ $moduloResource::getUrl('index') }}">Revisar nos módulos →</a>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($pendencias['turmas_sem_periodo']['count'] > 0)
                                        <div class="ga-pending-item">
                                            <div class="ga-pending-ic ga-pending-ic--gold">
                                                <x-filament::icon icon="heroicon-o-calendar-days" class="ga-pending-icon" />
                                            </div>
                                            <p class="ga-pending-text">
                                                <strong>{{ $pendencias['turmas_sem_periodo']['count'] }}</strong>
                                                turma(s) sem período letivo
                                            </p>
                                            <div class="ga-pending-links">
                                                @foreach ($pendencias['turmas_sem_periodo']['ids'] as $id)
                                                    <a class="ga-pending-link" href="{{ $turmaResource::getUrl('edit', ['record' => $id]) }}">#{{ $id }}</a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($pendencias['turmas_sem_disciplina']['count'] > 0)
                                        <div class="ga-pending-item">
                                            <div class="ga-pending-ic ga-pending-ic--red">
                                                <x-filament::icon icon="heroicon-o-user-group" class="ga-pending-icon" />
                                            </div>
                                            <p class="ga-pending-text">
                                                <strong>{{ $pendencias['turmas_sem_disciplina']['count'] }}</strong>
                                                turma(s) sem disciplina
                                            </p>
                                            <div class="ga-pending-links">
                                                @foreach ($pendencias['turmas_sem_disciplina']['ids'] as $id)
                                                    <a class="ga-pending-link" href="{{ $turmaResource::getUrl('edit', ['record' => $id]) }}">#{{ $id }}</a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="ga-pending-ok" role="status">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="ga-pending-ok-icon" />
                                    <p>Nenhuma pendência estrutural no momento. A estrutura curricular está íntegra.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Acesso rápido --}}
                        <div class="ga-section-heading">
                            <h3>Acesso Rápido</h3>
                            <span>Operação diária</span>
                        </div>
                        <div class="ga-quick-grid">
                            <button type="button" wire:click="trocarSecao('estrutura')" class="ga-quick-card">
                                <span class="ga-quick-ic"><x-filament::icon icon="heroicon-o-list-bullet" class="ga-quick-icon" /></span>
                                <span class="ga-quick-title">Estrutura Curricular</span>
                                <span class="ga-quick-desc">Organize cursos completos com módulos, disciplinas e aulas em uma visão de árvore.</span>
                                <span class="ga-quick-link">Ver estrutura →</span>
                            </button>
                            <a href="{{ $turmaResource::getUrl('index') }}" class="ga-quick-card">
                                <span class="ga-quick-ic"><x-filament::icon icon="heroicon-o-user-group" class="ga-quick-icon" /></span>
                                <span class="ga-quick-title">Turmas</span>
                                <span class="ga-quick-desc">Ofertas por período letivo, disciplina e professor, sempre híbridas.</span>
                                <span class="ga-quick-link">Gerenciar turmas →</span>
                            </a>
                            <a href="{{ $aulaAoVivoResource::getUrl('index') }}" class="ga-quick-card">
                                <span class="ga-quick-ic"><x-filament::icon icon="heroicon-o-video-camera" class="ga-quick-icon" /></span>
                                <span class="ga-quick-title">Aulas ao Vivo</span>
                                <span class="ga-quick-desc">Encontros síncronos híbridos com curso, turma, data e modalidade.</span>
                                <span class="ga-quick-link">Ver agenda →</span>
                            </a>
                        </div>
                    </section>
                @endif

                {{-- ======================= CURSOS ======================= --}}
                @if ($secaoAtiva === 'cursos')
                    <section aria-label="Cursos" class="ga-view">
                        <div class="ga-card">
                            <div class="ga-section-heading ga-section-heading--flush">
                                <h3>Cursos ativos</h3>
                                <span>{{ $estatisticas['cursos'] }} curso(s)</span>
                            </div>
                            @if ($cursosLista->isEmpty())
                                <div class="ga-empty" data-testid="ga-empty-state">
                                    <x-filament::icon icon="heroicon-o-academic-cap" class="ga-empty-icon" />
                                    <p class="ga-empty-title">Nenhum curso ativo</p>
                                    <p class="ga-empty-text">Cadastre o primeiro curso para começar.</p>
                                    <a href="{{ $cursoResource::getUrl('create') }}" class="ga-btn ga-btn--gold">Criar curso</a>
                                </div>
                            @else
                                <div class="ga-table-scroll">
                                    <table class="ga-table">
                                        <thead>
                                            <tr>
                                                <th>Curso</th>
                                                <th>Nível</th>
                                                <th>Módulos</th>
                                                <th>Disciplinas</th>
                                                <th>Status</th>
                                                <th class="ga-th-actions">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($cursosLista as $curso)
                                                <tr>
                                                    <td><span class="ga-td-title">{{ $curso['titulo'] }}</span></td>
                                                    <td>{{ $curso['nivel'] ?? '—' }}</td>
                                                    <td>{{ $curso['modulos_count'] }}</td>
                                                    <td>{{ $curso['disciplinas_count'] }}</td>
                                                    <td><span class="ga-badge ga-badge--success">Ativo</span></td>
                                                    <td class="ga-td-actions">
                                                        <a class="ga-action-btn" href="{{ $cursoResource::getUrl('view', ['record' => $curso['id']]) }}" aria-label="Ver curso">
                                                            <x-filament::icon icon="heroicon-o-eye" class="ga-action-icon" />
                                                        </a>
                                                        <a class="ga-action-btn" href="{{ $cursoResource::getUrl('edit', ['record' => $curso['id']]) }}" aria-label="Editar curso">
                                                            <x-filament::icon icon="heroicon-o-pencil-square" class="ga-action-icon" />
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="ga-card-footer">
                                    <a class="ga-link ga-link--gold" href="{{ $cursoResource::getUrl('index') }}">Ver todos os cursos →</a>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

                {{-- ======================= ESTRUTURA CURRICULAR ======================= --}}
                @if ($secaoAtiva === 'estrutura')
                    <section aria-label="Estrutura curricular" class="ga-view">
                        <div class="ga-card">
                            <div class="ga-section-heading ga-section-heading--flush">
                                <h3>Árvore curricular</h3>
                                <span>Curso → Módulo → Disciplina → Aula</span>
                            </div>

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

                            @if ($cursos->isEmpty())
                                <div class="ga-empty" data-testid="ga-empty-state">
                                    <x-filament::icon icon="heroicon-o-academic-cap" class="ga-empty-icon" />
                                    <p class="ga-empty-title">Nenhum curso ativo encontrado</p>
                                    <p class="ga-empty-text">Cadastre o primeiro curso para começar a montar a árvore curricular.</p>
                                    <a href="{{ $cursoResource::getUrl('create') }}" class="ga-btn ga-btn--gold">Criar curso</a>
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
                        </div>
                    </section>
                @endif

                {{-- ======================= PERÍODOS & TURMAS ======================= --}}
                @if ($secaoAtiva === 'operacao')
                    <section aria-label="Períodos letivos e turmas" class="ga-view">
                        <div class="ga-duo">
                            <div class="ga-card">
                                <div class="ga-section-heading ga-section-heading--flush">
                                    <h3>Períodos letivos</h3>
                                    <span>{{ $estatisticas['periodos'] }}</span>
                                </div>
                                <p class="ga-card-text">Semestres e anos letivos que organizam turmas e matrículas.</p>
                                <a class="ga-link ga-link--gold" href="{{ $periodoLetivoResource::getUrl('index') }}">Gerenciar períodos →</a>
                            </div>
                            <div class="ga-card">
                                <div class="ga-section-heading ga-section-heading--flush">
                                    <h3>Turmas</h3>
                                    <span>{{ $estatisticas['turmas'] }}</span>
                                </div>
                                <p class="ga-card-text">Ofertas híbridas por período, disciplina e professor.</p>
                                <a class="ga-link ga-link--gold" href="{{ $turmaResource::getUrl('index') }}">Gerenciar turmas →</a>
                            </div>
                        </div>
                        <div class="ga-card">
                            <div class="ga-section-heading ga-section-heading--flush">
                                <h3>Matrículas</h3>
                                <span>{{ $estatisticas['matriculas_ativas'] }} ativa(s)</span>
                            </div>
                            <p class="ga-card-text">Vínculos de alunos a cursos e turmas.</p>
                            <a class="ga-link ga-link--gold" href="{{ $matriculaResource::getUrl('index') }}">Gerenciar matrículas →</a>
                        </div>
                    </section>
                @endif

                {{-- ======================= PESSOAS ======================= --}}
                @if ($secaoAtiva === 'pessoas')
                    <section aria-label="Pessoas" class="ga-view">
                        <div class="ga-duo">
                            <div class="ga-card">
                                <div class="ga-section-heading ga-section-heading--flush">
                                    <h3>Professores</h3>
                                    <span>{{ $estatisticas['professores'] }} ativo(s)</span>
                                </div>
                                <p class="ga-card-text">Usuários com nível professor, sem exposição de dados sensíveis.</p>
                                <a class="ga-link ga-link--gold" href="{{ $userResource::getUrl('index') }}">Gerenciar professores →</a>
                            </div>
                            <div class="ga-card">
                                <div class="ga-section-heading ga-section-heading--flush">
                                    <h3>Alunos</h3>
                                    <span>{{ $estatisticas['alunos'] }} ativo(s)</span>
                                </div>
                                <p class="ga-card-text">Alunos ativos da instituição.</p>
                                <a class="ga-link ga-link--gold" href="{{ $alunoResource::getUrl('index') }}">Gerenciar alunos →</a>
                            </div>
                        </div>
                    </section>
                @endif

                {{-- ======================= ACOMPANHAMENTO ======================= --}}
                @if ($secaoAtiva === 'acompanhamento')
                    <section aria-label="Acompanhamento" class="ga-view">
                        <div class="ga-card">
                            <div class="ga-section-heading ga-section-heading--flush">
                                <h3>Próximas aulas ao vivo</h3>
                                <span>Híbridas</span>
                            </div>
                            @if ($proximasAulasAoVivo->isEmpty())
                                <div class="ga-empty">
                                    <x-filament::icon icon="heroicon-o-video-camera" class="ga-empty-icon" />
                                    <p class="ga-empty-title">Nenhuma aula ao vivo agendada</p>
                                    <p class="ga-empty-text">Quando houver encontros agendados, eles aparecem aqui.</p>
                                    <a href="{{ $aulaAoVivoResource::getUrl('create') }}" class="ga-btn ga-btn--gold">Agendar aula ao vivo</a>
                                </div>
                            @else
                                <div class="ga-table-scroll">
                                    <table class="ga-table">
                                        <thead>
                                            <tr>
                                                <th>Aula</th>
                                                <th>Curso</th>
                                                <th>Turma</th>
                                                <th>Data / hora</th>
                                                <th>Modalidade</th>
                                                <th class="ga-th-actions">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($proximasAulasAoVivo as $aula)
                                                <tr>
                                                    <td><span class="ga-td-title">{{ $aula['titulo'] }}</span></td>
                                                    <td>{{ $aula['curso'] ?? '—' }}</td>
                                                    <td>{{ $aula['turma'] ?? '—' }}</td>
                                                    <td>{{ $aula['data_hora'] ?? '—' }}</td>
                                                    <td><span class="ga-badge ga-badge--info">{{ $aula['modalidade'] ?? 'híbrida' }}</span></td>
                                                    <td class="ga-td-actions">
                                                        <a class="ga-action-btn" href="{{ $aulaAoVivoResource::getUrl('edit', ['record' => $aula['id']]) }}" aria-label="Editar aula ao vivo">
                                                            <x-filament::icon icon="heroicon-o-pencil-square" class="ga-action-icon" />
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="ga-card-footer">
                                    <a class="ga-link ga-link--gold" href="{{ $aulaAoVivoResource::getUrl('index') }}">Ver todas as aulas ao vivo →</a>
                                </div>
                            @endif
                        </div>

                        @foreach (collect($areasIndisponiveis)->whereIn('slug', ['frequencia', 'notas']) as $area)
                            <div class="ga-card ga-card--disabled" aria-disabled="true">
                                <div class="ga-section-heading ga-section-heading--flush">
                                    <h3>{{ $area['titulo'] }}</h3>
                                    <span class="ga-badge ga-badge--muted">Em preparação</span>
                                </div>
                                <p class="ga-card-text">{{ $area['motivo'] }}</p>
                                <button type="button" class="ga-btn ga-btn--ghost" disabled aria-disabled="true">
                                    Indisponível
                                </button>
                            </div>
                        @endforeach
                    </section>
                @endif

                {{-- ======================= AVALIAÇÃO & DOCUMENTOS ======================= --}}
                @if ($secaoAtiva === 'documentos')
                    <section aria-label="Avaliação e documentos" class="ga-view">
                        <div class="ga-card">
                            <div class="ga-section-heading ga-section-heading--flush">
                                <h3>Avaliação &amp; Documentos</h3>
                                <span>Em preparação</span>
                            </div>
                            <p class="ga-card-text">
                                As áreas abaixo fazem parte do desenho da Gestão Acadêmica, mas ainda não
                                possuem fonte administrativa confirmada. Nenhum dado é inventado.
                            </p>
                        </div>
                        <div class="ga-quick-grid">
                            @foreach (collect($areasIndisponiveis)->whereIn('slug', ['avaliacoes', 'certificados', 'requerimentos']) as $area)
                                <div class="ga-quick-card ga-quick-card--disabled" aria-disabled="true">
                                    <span class="ga-quick-ic ga-quick-ic--muted">
                                        <x-filament::icon icon="heroicon-o-document-text" class="ga-quick-icon" />
                                    </span>
                                    <span class="ga-quick-title">{{ $area['titulo'] }}</span>
                                    <span class="ga-quick-desc">{{ $area['motivo'] }}</span>
                                    <button type="button" class="ga-btn ga-btn--ghost" disabled aria-disabled="true">Indisponível</button>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
        </main>
    </div>
</div>
