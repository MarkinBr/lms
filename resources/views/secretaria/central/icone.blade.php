<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($nome)
        @case('chave') <circle cx="8" cy="8" r="5"/><path d="m12 12 9 9m-5-5 3-3m-6 0 3-3M7 7h.01"/> @break
        @case('pessoas') <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M15 3.13a4 4 0 0 1 0 7.75"/><circle cx="9" cy="7" r="4"/> @break
        @case('livro') <path d="M12 5C8 2 3 3 2 4v16c3-2 7-1 10 1 3-2 7-3 10-1V4c-1-1-6-2-10 1Zm0 0v16"/> @break
        @case('documento') <path d="M14 2H5v20h14V7Zm0 0v6h5M8 12h8m-8 4h8"/> @break
        @case('carteira') <path d="M3 6V3l16-1v4M3 6h18v16H3Zm12 5h6v6h-6Zm2 3h.01"/> @break
        @case('agenda') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6m10-6v6M3 11h18M7 15h2m6 0h2M7 18h2"/> @break
        @case('escudo') <path d="m12 2 8 4v6c0 5-8 10-8 10S4 17 4 12V6Zm-4 9 3 3 5-6"/> @break
        @case('busca') <circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/> @break
        @case('mais') <path d="M12 5v14M5 12h14"/> @break
        @case('voltar') <path d="m10 5-7 7 7 7M3 12h18"/> @break
        @case('relogio') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('email') <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 5 10 8L22 5"/> @break
        @case('foto') <path d="M8 5 10 2h4l2 3h5v16H3V5Z"/><circle cx="12" cy="12" r="4"/> @break
        @case('editar') <path d="m15 4 5 5M3 21l5-1L21 7l-5-5L3 15Z"/> @break
        @case('aviso') <path d="m12 3 10 18H2Zm0 6v5m0 3h.01"/> @break
        @default <path d="M4 12h16m-6-6 6 6-6 6"/>
    @endswitch
</svg>
