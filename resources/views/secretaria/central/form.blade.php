@extends('secretaria.central.layout')
@section('title', $aluno ? 'Editar cadastro do aluno' : 'Cadastrar novo acesso')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.acessos.index') }}">Acessos dos alunos</a><span aria-hidden="true">/</span><span aria-current="page">{{ $aluno ? 'Editar cadastro' : 'Novo acesso' }}</span>@endsection
@section('content')
    <a class="sc-back" href="{{ $aluno ? route('secretaria.acessos.show', $aluno) : route('secretaria.acessos.index') }}">@include('secretaria.central.icone', ['nome' => 'voltar']) {{ $aluno ? 'Voltar à ficha do aluno' : 'Voltar à lista de alunos' }}</a>
    <div class="sc-intro"><div><p class="sc-eyebrow">{{ $aluno ? 'Um cadastro sempre atualizado' : 'O início de um novo acesso' }}</p><h1>{{ $aluno ? 'Editar cadastro completo' : 'Cadastrar novo acesso' }}</h1><p>{{ $aluno ? 'Confira os dados e revise as alterações antes de confirmar.' : 'Cadastre o aluno, escolha o canal e confira o destinatário das instruções de primeiro acesso.' }}</p></div></div>
    @if ($aluno)<div class="sc-alert"><strong>{{ $aluno->name }}</strong> · Matrícula de acesso {{ $aluno->matricula_login ?: 'Não informada' }}. O identificador de acesso e os vínculos acadêmicos não são editáveis aqui.</div>@endif
    <form action="{{ $aluno ? route('secretaria.acessos.update', $aluno) : route('secretaria.acessos.store') }}" method="post" class="sc-layout sc-form-layout">
        @csrf
        @if ($aluno)@method('PUT')<input type="hidden" name="_estado" value="{{ $estado }}">@endif
        <div class="sc-stack">
            @include('secretaria.central.campos', ['modo' => 'edicao'])
            @unless ($aluno)
                <section class="sc-panel" aria-labelledby="canal-titulo">
                    <div class="sc-panel-heading"><h2 id="canal-titulo">Entrega do primeiro acesso</h2><p>As instruções usam matrícula e link individual gerados pelo servidor após a confirmação.</p></div>
                    <div class="sc-panel-body sc-stack">
                        <div class="sc-field"><label for="canal">Canal de entrega <span class="sc-required">*</span></label><select name="canal" id="canal" required><option value="whatsapp" @selected(old('canal', 'whatsapp') === 'whatsapp')>WhatsApp do aluno</option><option value="email" @selected(old('canal') === 'email')>E-mail pessoal do aluno</option></select><small>O contato do canal escolhido é obrigatório. WhatsApp não confirma a propriedade do e-mail.</small></div>
                        <label class="sc-check"><input type="checkbox" name="destino_conferido" value="1" required @checked(old('destino_conferido'))><span>Conferi a identidade e o contato informado com o próprio aluno.</span></label>
                        <p class="sc-note">WhatsApp: a revisão prepara um rascunho para envio manual. E-mail: envio pela fila, sem garantia de chegada à caixa de entrada.</p>
                    </div>
                </section>
            @endunless
            <section class="sc-panel" aria-labelledby="motivo-titulo">
                <div class="sc-panel-heading"><h2 id="motivo-titulo">Conferência do atendimento</h2><p>O motivo será registrado no histórico, junto com o autor e a data.</p></div>
                <div class="sc-panel-body sc-stack">
                    <div class="sc-field"><label for="motivo">Motivo do atendimento <span class="sc-required">*</span></label><textarea id="motivo" name="motivo" rows="3" maxlength="500" required aria-describedby="motivo-ajuda">{{ old('motivo') }}</textarea><small id="motivo-ajuda">Descreva a necessidade sem incluir senhas, códigos ou documentos.</small></div>
                    @if ($aluno)<label class="sc-check"><input type="checkbox" name="destino_conferido" value="1" required @checked(old('destino_conferido'))><span>Conferi a identidade do aluno e os contatos que estou atualizando.</span></label>@endif
                    <div class="sc-field"><label for="senha-confirmacao">Sua senha de Secretaria <span class="sc-required">*</span></label><input id="senha-confirmacao" name="senha_confirmacao" type="password" autocomplete="current-password" required><small>Confirma a autorização do atendimento. Nunca informe a senha do aluno.</small></div>
                    <div class="sc-actions"><a class="sc-button" href="{{ $aluno ? route('secretaria.acessos.show', $aluno) : route('secretaria.acessos.index') }}">Cancelar</a><button type="submit" class="sc-button sc-primary">Revisar {{ $aluno ? 'alterações' : 'cadastro e entrega' }} @include('secretaria.central.icone', ['nome' => 'seta'])</button></div>
                </div>
            </section>
        </div>
        <aside class="sc-aside">
            <section class="sc-side-card sc-side-gold"><h2>{{ $aluno ? 'Uma ficha, um cadastro.' : 'Acesso não é matrícula.' }}</h2><p>{{ $aluno ? 'As alterações atualizam o cadastro do aluno. A inscrição original permanece disponível para conferência na ficha.' : 'Este cadastro cria somente a conta de acesso. Cursos, turmas e cobranças seguem seus próprios fluxos.' }}</p></section>
            <nav class="sc-side-card sc-section-nav" aria-label="Grupos do cadastro"><h2>Neste formulário</h2><a href="#dados-pessoais">Dados pessoais</a><a href="#contatos">Contatos</a><a href="#endereco">Endereço</a><a href="#escolaridade">Escolaridade e formação</a><a href="#motivo-titulo">Conferência do atendimento</a></nav>
            <p class="sc-note">Campos com <span class="sc-required">*</span> são obrigatórios. Não copie senhas para o motivo ou para campos cadastrais.</p>
        </aside>
    </form>
@endsection
