{{--
    Issue #61 — Layout standalone compartilhado dos Resources acadêmicos.

    Usa a base de assets/autenticação do Filament (Livewire, Alpine, modais,
    notificações, CSRF e contexto do painel admin) SEM renderizar o chrome
    global: nada de layout.index, fi-main-sidebar ou fi-topbar. O conteúdo
    da página (formulários, tabelas, Relation Managers e ações) continua
    sendo renderizado pelo Filament dentro de {{ $slot }}.
--}}
<x-filament-panels::layout.base :livewire="$livewire">
    @php
        $gaNavegacao ??= \App\Support\GestaoAcademica\NavegacaoAcademica::itens();
        $gaResourceAtivo ??= method_exists($livewire, 'getResource') ? $livewire::getResource() : null;
        $gaUrlCentral ??= \App\Support\GestaoAcademica\NavegacaoAcademica::urlCentral(
            $gaResourceAtivo !== null
                ? \App\Support\GestaoAcademica\NavegacaoAcademica::secaoRetornoParaResource($gaResourceAtivo)
                : 'visao-geral'
        );
        $gaUrlPainel ??= filament()->getPanel('admin')->getUrl();
        $gaAdministrador ??= auth()->user();

        $nomeAdministrador = $gaAdministrador?->name ?? 'Administrador';
        $iniciaisAdministrador = collect(preg_split('/\s+/', trim($nomeAdministrador)))
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');
    @endphp

    <div
        class="ga-canvas ga-resource-layout ga-academic-resource-layout"
        x-data="{
            menuOpen: false,
            closeMenu(returnFocus = false) {
                this.menuOpen = false

                if (returnFocus) {
                    this.$nextTick(() => this.$refs.menuButton?.focus())
                }
            },
        }"
        x-on:keydown.escape.window="if (menuOpen) closeMenu(true)"
    >
        <a class="ga-skip-link" href="#ga-recurso-conteudo">Pular para o conteúdo</a>

        {{-- Sidebar acadêmica própria: não é a sidebar global do Filament. --}}
        @include('gestao-academica.sidebar')

        <button
            type="button"
            class="ga-nav-overlay"
            x-cloak
            x-show="menuOpen"
            x-on:click="closeMenu(true)"
            aria-label="Fechar menu da gestão acadêmica"
        ></button>

        <div class="ga-main">
            {{-- Topbar acadêmica contextual: não é a topbar global do Filament. --}}
            @include('gestao-academica.topbar')

            <main id="ga-recurso-conteudo" class="ga-content ga-resource-content">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-filament-panels::layout.base>
