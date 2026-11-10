{{-- Issue #61 — Sidebar acadêmica do shell de Resources (links reais do painel admin). --}}
<aside
    id="ga-navegacao"
    class="ga-localnav"
    x-bind:class="{ 'is-open': menuOpen }"
    x-trap.noscroll="menuOpen"
    aria-label="Navegação da gestão acadêmica"
>
    <a
        class="ga-brand"
        href="{{ \App\Support\GestaoAcademica\NavegacaoAcademica::urlCentral('visao-geral') }}"
        aria-label="Voltar à Central de Gestão Acadêmica"
    >
        <span class="ga-brand-mark" aria-hidden="true">
            <x-filament::icon icon="heroicon-o-academic-cap" />
        </span>
        <span class="ga-brand-copy">
            <span class="ga-brand-title">Gestão Acadêmica</span>
            <span class="ga-brand-subtitle">CETSADP · Ambiente administrativo</span>
        </span>
    </a>

    <nav class="ga-localnav-scroll" aria-label="Cadastros acadêmicos">
        <div class="ga-localnav-section">
            @foreach ($gaNavegacao as $item)
                @php
                    $ativo = $item['resource'] !== null && $item['resource'] === $gaResourceAtivo;
                @endphp
                <a
                    href="{{ $item['url'] }}"
                    class="ga-localnav-item {{ $ativo ? 'is-active' : '' }}"
                    x-on:click="menuOpen = false"
                    @if ($ativo) aria-current="page" @endif
                >
                    <x-filament::icon icon="{{ $item['icone'] }}" class="ga-localnav-icon" />
                    <span>{{ $item['titulo'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <div class="ga-sidebar-foot">
        <span class="ga-status-dot" aria-hidden="true"></span>
        <span>Sistema acadêmico conectado</span>
    </div>
</aside>
