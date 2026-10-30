<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#061a30">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'Central da Secretaria') · CETSADP</title>
    <link rel="icon" href="{{ asset('assets/images/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('vendor/portal/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/secretaria-central.css') }}?v={{ filemtime(public_path('css/secretaria-central.css')) }}">
    <script src="{{ asset('js/secretaria-central.js') }}?v={{ filemtime(public_path('js/secretaria-central.js')) }}" defer></script>
</head>
<body class="central">
    <a class="sc-skip" href="#conteudo">Ir para o conteúdo</a>
    <header class="sc-topbar">
        <div class="sc-topbar-inner">
            <a class="sc-brand" href="{{ route('secretaria.central') }}" aria-label="CETSADP — início da Central da Secretaria">
                <img src="{{ asset('assets/images/logo.png') }}" alt="" width="54" height="54">
                <span><strong>CETSADP</strong><small>Secretaria acadêmica</small></span>
            </a>
            <p class="sc-motto">Cuidar de cada etapa. Aproximar pessoas.</p>
            <div class="sc-identity">
                @include('secretaria.central.icone', ['nome' => 'escudo'])
                <span><strong>{{ auth()->user()?->name ?? 'Secretaria' }}</strong><small>Equipe autorizada</small></span>
            </div>
        </div>
    </header>
    <main id="conteudo" class="sc-shell" tabindex="-1">
        <nav class="sc-breadcrumb" aria-label="Caminho de navegação">
            <a href="{{ url('/secretaria') }}">Painel da Secretaria</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('secretaria.central') }}">Central</a>
            @yield('breadcrumb')
        </nav>
        @if (session('status'))
            <div class="sc-alert sc-alert-success" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="sc-alert sc-alert-error" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="sc-alert sc-alert-error" role="alert" tabindex="-1" data-error-summary>
                <strong>Confira as informações antes de continuar.</strong>
                <ul>@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
        <footer class="sc-footer">
            <span>CETSADP · Centro de Educação Teológica</span>
            <a href="{{ url('/secretaria') }}">Voltar ao painel da Secretaria @include('secretaria.central.icone', ['nome' => 'seta'])</a>
        </footer>
    </main>
</body>
</html>
