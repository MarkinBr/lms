{{-- Issue #61 — Topbar acadêmica contextual do shell de Resources. --}}
<header class="ga-topbar">
    <div class="ga-topbar-leading">
        <button
            x-ref="menuButton"
            type="button"
            class="ga-menu-toggle"
            x-on:click="menuOpen = ! menuOpen"
            x-bind:aria-expanded="menuOpen.toString()"
            x-bind:aria-label="menuOpen ? 'Fechar menu da gestão acadêmica' : 'Abrir menu da gestão acadêmica'"
            aria-controls="ga-navegacao"
            aria-label="Abrir menu da gestão acadêmica"
        >
            <x-filament::icon icon="heroicon-o-bars-3" />
        </button>
        <div class="ga-topbar-heading">
            <h1>{{ $livewire->getTitle() }}</h1>
            <p>Gestão Acadêmica · CETSADP</p>
        </div>
    </div>

    <div class="ga-topbar-actions">
        <a class="ga-back-link ga-back-link--primary" href="{{ $gaUrlCentral }}">
            <x-filament::icon icon="heroicon-o-arrow-left" />
            <span>Voltar à Central</span>
        </a>
        <a class="ga-back-link" href="{{ $gaUrlPainel }}">
            <x-filament::icon icon="heroicon-o-home" />
            <span>Dashboard</span>
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
