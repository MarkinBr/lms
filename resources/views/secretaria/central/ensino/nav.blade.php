@push('styles')
    <link rel="stylesheet" href="{{ asset('css/secretaria-ensino.css') }}?v={{ filemtime(public_path('css/secretaria-ensino.css')) }}">
@endpush
<nav class="sc-en-tabs" aria-label="Áreas do ensino e aprendizagem">
    @foreach([
        ['painel', 'Visão de trabalho', 'painel'],
        ['modulos', 'Módulos', 'modulos'],
        ['disciplinas', 'Disciplinas', 'disciplinas'],
        ['aulas', 'Aulas e materiais', 'aulas'],
        ['avaliacoes', 'Quiz, provas e avaliações', 'avaliacoes'],
        ['questoes', 'Banco de questões', 'questoes'],
        ['notas', 'Notas', 'notas'],
        ['frequencias', 'Frequência presencial', 'frequencias'],
        ['ead', 'Frequência EAD', 'ead']
    ] as [$chave, $rotulo, $rota])
        <a class="sc-en-tab" href="{{ route('secretaria.ensino.'.$rota) }}" @if(($secao??'')===$chave) aria-current="page" @endif>{{ $rotulo }}</a>
    @endforeach
</nav>
