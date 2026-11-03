@push('styles')
    <link rel="stylesheet" href="{{ asset('css/secretaria-documentos.css') }}?v={{ filemtime(public_path('css/secretaria-documentos.css')) }}">
@endpush
<nav class="sc-doc-tabs" aria-label="Áreas de documentos e solicitações">
    @foreach([
        ['painel','Visão de trabalho','painel'],
        ['solicitacoes','Solicitações','solicitacoes'],
        ['atendimento','Atendimento ao aluno','atendimento'],
        ['documentacao','Documentação','documentacao'],
        ['declaracoes','Declarações e emissões','declaracoes'],
        ['assinaturas','Assinaturas','assinaturas'],
        ['aprovacoes','Aprovações','aprovacoes'],
        ['historico','Protocolos e histórico','historico']
    ] as [$sec,$rotulo,$rota])
        <a class="sc-doc-tab" href="{{ route('secretaria.documentos.'.$rota) }}"
            @if(($secao??'')===$sec) aria-current="page" @endif>{{ $rotulo }}</a>
    @endforeach
</nav>
