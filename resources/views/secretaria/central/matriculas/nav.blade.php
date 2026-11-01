<nav class="sc-ac-tabs" aria-label="Áreas da Gestão de Matrículas e Turmas">
    @foreach ([
        ['painel', 'Visão de trabalho', 'painel'],
        ['matriculas', 'Matrículas', 'index'],
        ['turmas', 'Turmas', 'turmas'],
        ['movimentacoes', 'Movimentações', 'movimentacoes'],
    ] as [$chave, $rotulo, $rota])
        <a class="sc-ac-tab" href="{{ route('secretaria.matriculas.'.$rota) }}"
            @if(($secao ?? '') === $chave) aria-current="page" @endif>{{ $rotulo }}</a>
    @endforeach
</nav>
