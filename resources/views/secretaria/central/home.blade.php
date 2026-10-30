@extends('secretaria.central.layout')
@section('title', 'Central da Secretaria')
@section('content')
    <div class="sc-intro">
        <div><p class="sc-eyebrow">Central da Secretaria</p><h1>Como podemos ajudar hoje?</h1><p>Escolha uma área para organizar o atendimento e acompanhar a vida acadêmica dos nossos alunos.</p></div>
        <span class="sc-tag">@include('secretaria.central.icone', ['nome' => 'escudo']) Acesso da equipe autorizada</span>
    </div>
    <section class="sc-welcome" aria-labelledby="boas-vindas">
        @include('secretaria.central.icone', ['nome' => 'livro'])
        <div><h2 id="boas-vindas">Servir com cuidado também faz parte do ensino.</h2><p>Um espaço para resolver o que o aluno precisa, com clareza, segurança e acolhimento.</p></div>
    </section>
    <div class="sc-section-heading"><h2>Áreas de atendimento</h2><span>Um cuidado em cada etapa</span></div>
    <div class="sc-modules">
        <a class="sc-module sc-module-active" href="{{ route('secretaria.acessos.index') }}">
            <span class="sc-icon-box">@include('secretaria.central.icone', ['nome' => 'chave'])</span>
            <h3>Acessos dos alunos</h3><p>Cadastro, ativação, recuperação e segurança das contas.</p>
            <span class="sc-module-footer"><strong>Abrir gestão de acessos</strong>@include('secretaria.central.icone', ['nome' => 'seta'])</span>
        </a>
        @foreach ([['pessoas', 'Matrículas e turmas', 'Inscrições, vínculos e organização acadêmica.'], ['livro', 'Ensino e aprendizagem', 'Aulas, disciplinas, notas e frequência.'], ['documento', 'Documentos e solicitações', 'Documentação, declarações e atendimento ao aluno.'], ['carteira', 'Financeiro', 'Mensalidades e acompanhamento dos pagamentos.'], ['agenda', 'Comunicação e agenda', 'Avisos, notícias e encontros da comunidade.']] as [$icone, $titulo, $descricao])
            <article class="sc-module sc-module-planned">
                <span class="sc-icon-box">@include('secretaria.central.icone', ['nome' => $icone])</span>
                <h3>{{ $titulo }}</h3><p>{{ $descricao }}</p><span class="sc-tag">Próximas etapas da central</span>
            </article>
        @endforeach
    </div>
    <p class="sc-support-note">@include('secretaria.central.icone', ['nome' => 'escudo']) Os recursos atuais continuam disponíveis no <a href="{{ url('/secretaria') }}">painel da Secretaria</a>.</p>
@endsection
