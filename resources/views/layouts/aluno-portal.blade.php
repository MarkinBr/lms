@php
    $aluno = auth()->user();
    $fotoAluno = null;
    if (!empty($aluno?->foto)) {
        $fotoAluno = str_starts_with($aluno->foto, 'http') ? $aluno->foto : asset('storage/' . ltrim($aluno->foto, '/'));
    }
    $portalPagina = str_replace('aluno.', '', request()->route()?->getName() ?? 'dashboard');
    $portalMenu = [
        ['Início', 'aluno.dashboard', 'fa-house', ['aluno.dashboard']],
        ['Meus Cursos', 'aluno.cursos', 'fa-book-open', ['aluno.cursos', 'aluno.curso.*', 'aluno.aula*']],
        ['Calendário', 'aluno.calendario', 'fa-calendar-days', ['aluno.calendario*']],
        ['Minhas Notas', 'aluno.notas', 'fa-clipboard-check', ['aluno.notas*']],
        ['Frequência', 'aluno.frequencia', 'fa-list-check', ['aluno.frequencia*']],
        ['Certificados', 'aluno.certificados', 'fa-award', ['aluno.certificados*']],
        ['Mural de Avisos', 'aluno.comunicacao', 'fa-bullhorn', ['aluno.comunicacao', 'aluno.comunicacao.show']],
        ['Mensagens', 'aluno.comunicacao.mensagens', 'fa-envelope', ['aluno.comunicacao.mensagens']],
        ['Fórum', 'aluno.forum', 'fa-comments', ['aluno.forum*']],
        ['Secretaria', 'aluno.secretaria', 'fa-headset', ['aluno.secretaria*']],
    ];
    $portalConta = [
        ['Meu Perfil', 'aluno.perfil', 'fa-user', ['aluno.perfil*']],
        ['Financeiro', 'aluno.financeiro', 'fa-wallet', ['aluno.financeiro*']],
        ['Documentos da inscrição', 'aluno.documentos-inscricao', 'fa-folder-open', ['aluno.documentos-inscricao*']],
        ['Documentos emitidos', 'aluno.documentos.emitidos', 'fa-file-lines', ['aluno.documentos.emitidos']],
        ['Configurações', 'aluno.configuracoes', 'fa-gear', ['aluno.configuracoes']],
    ];
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#061a30">
    <title>@yield('title', 'Portal do Aluno') — CETSADP</title>
    <link rel="stylesheet" href="{{ asset('vendor/portal/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/portal-base.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/portal/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/premium-classroom.css') }}">
    <script defer src="{{ asset('vendor/portal/alpine.min.js') }}"></script>
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/portal-redesign.css') }}?v=1">
</head>
<body class="portal-app" data-portal-page="{{ $portalPagina }}" data-layout-origem="{{ $portalOrigem ?? 'desktop' }}">
    <a href="#portal-conteudo" class="portal-skip">Ir para o conteúdo</a>
    <div class="portal-overlay" data-portal-overlay hidden></div>
    <aside class="portal-sidebar" id="portal-menu" aria-label="Menu principal">
        <button type="button" class="portal-menu-close portal-icon-button" data-portal-close aria-label="Fechar menu"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        <a href="{{ route('aluno.dashboard') }}" class="portal-brand">
            <img src="{{ asset('assets/portal/marca-menu.webp') }}" alt="CETSADP" width="102" height="79">
            <span>Centro Educacional, Teológico e Social<br>da Igreja Evangélica Assembleia<br>de Deus em Paracambi</span>
        </a>
        <nav class="portal-nav" aria-label="Navegação do aluno">
            <p class="portal-nav-label">Navegação</p>
            @foreach($portalMenu as [$label, $rota, $icone, $padroes])
                <a href="{{ route($rota) }}" class="portal-nav-item {{ request()->routeIs(...$padroes) ? 'is-active' : '' }}" @if(request()->routeIs(...$padroes)) aria-current="page" @endif>
                    <i class="fa-solid {{ $icone }}" aria-hidden="true"></i><span>{{ $label }}</span>
                </a>
            @endforeach
            <p class="portal-nav-label portal-nav-label--divider">Minha conta</p>
            @foreach($portalConta as [$label, $rota, $icone, $padroes])
                <a href="{{ route($rota) }}" class="portal-nav-item {{ request()->routeIs(...$padroes) ? 'is-active' : '' }}" @if(request()->routeIs(...$padroes)) aria-current="page" @endif>
                    <i class="fa-solid {{ $icone }}" aria-hidden="true"></i><span>{{ $label }}</span>
                </a>
            @endforeach
            <form method="POST" action="{{ route('aluno.logout') }}" class="portal-logout">
                @csrf
                <button type="submit" class="portal-nav-item"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Sair</span></button>
            </form>
        </nav>
        <div class="portal-seal"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>Ensino que transforma<br>vidas e ministérios</span></div>
    </aside>
    <div class="portal-workspace">
        <header class="portal-topbar">
            <div class="portal-topbar-title">
                <button type="button" class="portal-icon-button portal-menu-toggle" data-portal-open aria-controls="portal-menu" aria-expanded="false" aria-label="Abrir menu"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
                <div><span class="portal-eyebrow">Portal do Aluno</span><h1>@yield('header_title', 'Sala de Aula')</h1></div>
            </div>
            <div class="portal-user-actions">
                <a href="{{ route('aluno.comunicacao') }}" class="portal-icon-button" aria-label="Abrir mural de avisos"><i class="fa-regular fa-bell" aria-hidden="true"></i></a>
                <span class="portal-user-divider" aria-hidden="true"></span>
                <a href="{{ route('aluno.perfil') }}" class="portal-user" aria-label="Abrir meu perfil">
                    @if($fotoAluno)
                        <img src="{{ $fotoAluno }}" alt="" width="38" height="38">
                    @else
                        <span class="portal-avatar">{{ mb_strtoupper(mb_substr($aluno->name ?? 'A', 0, 1)) }}</span>
                    @endif
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </a>
            </div>
        </header>
        <main class="portal-main" id="portal-conteudo" tabindex="-1">
            @if(session('success'))
                <div class="portal-alert portal-alert--success" role="status"><i class="fa-regular fa-circle-check" aria-hidden="true"></i><div><strong>Sucesso</strong><p>{{ session('success') }}</p></div></div>
            @endif
            @if(session('error'))
                <div class="portal-alert portal-alert--error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><div><strong>Não foi possível concluir</strong><p>{{ session('error') }}</p></div></div>
            @endif
            @yield('content')
        </main>
        <footer class="portal-footer">CETSADP <span aria-hidden="true">·</span> Conhecimento que edifica. Ensino que transforma.</footer>
    </div>
    <nav class="portal-bottom-nav" aria-label="Navegação rápida">
        @foreach([
            ['Início', 'aluno.dashboard', 'fa-house', ['aluno.dashboard']],
            ['Meus Cursos', 'aluno.cursos', 'fa-book-open', ['aluno.cursos', 'aluno.curso.*', 'aluno.aula*']],
            ['Agenda', 'aluno.calendario', 'fa-calendar-days', ['aluno.calendario*']],
            ['Financeiro', 'aluno.financeiro', 'fa-wallet', ['aluno.financeiro*']],
        ] as [$label, $rota, $icone, $padroes])
            <a href="{{ route($rota) }}" class="{{ request()->routeIs(...$padroes) ? 'is-active' : '' }}" @if(request()->routeIs(...$padroes)) aria-current="page" @endif><i class="fa-solid {{ $icone }}" aria-hidden="true"></i><span>{{ $label }}</span></a>
        @endforeach
        <button type="button" data-portal-open aria-controls="portal-menu" aria-expanded="false"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i><span>Mais</span></button>
    </nav>
    @if(($portalOrigem ?? 'desktop') === 'desktop')
        {{-- Dependência legada mantida somente onde já era carregada. --}}
        <script type="module" src="{{ asset('js/premium-classroom.js') }}"></script>
    @endif
    <script defer src="{{ asset('js/portal-redesign.js') }}?v=1"></script>
    @stack('scripts')
</body>
</html>
