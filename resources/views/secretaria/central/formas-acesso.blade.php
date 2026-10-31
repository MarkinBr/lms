@extends('secretaria.central.layout')
@section('title', 'Formas de acesso')
@section('breadcrumb')
    <span aria-hidden="true">/</span><a href="{{ route('secretaria.acessos.index') }}">Acessos dos alunos</a>
    <span aria-hidden="true">/</span><span aria-current="page">Formas de acesso</span>
@endsection

@section('content')
    <a class="sc-back" href="{{ route('secretaria.acessos.index') }}">@include('secretaria.central.icone', ['nome' => 'voltar']) Voltar à pesquisa</a>
    <div class="sc-intro">
        <div>
            <p class="sc-eyebrow">Consulta rápida e protegida</p>
            <h1>Formas de acesso</h1>
            <p>Dados de acesso de {{ $aluno->name }}. Confira a identidade do aluno antes de informar qualquer dado.</p>
        </div>
        <a class="sc-button" href="{{ route('secretaria.acessos.show', $aluno) }}">Abrir ficha completa @include('secretaria.central.icone', ['nome' => 'seta'])</a>
    </div>

    <section class="sc-panel" aria-labelledby="resumo-acesso">
        <div class="sc-panel-heading">
            <h2 id="resumo-acesso">Dados para identificação</h2>
            <p>Esta consulta não altera cadastro, matrícula ou credenciais.</p>
        </div>
        <dl class="sc-data-grid">
            <div><dt>Nome</dt><dd>{{ $aluno->name }}</dd></div>
            <div><dt>CPF</dt><dd>{{ $aluno->cpf ?: 'Não cadastrado' }}</dd></div>
            <div><dt>E-mail</dt><dd>{{ $aluno->email ?: 'Não cadastrado' }}</dd></div>
            <div><dt>WhatsApp</dt><dd>{{ $aluno->whatsapp ?: 'Não cadastrado' }}</dd></div>
            <div><dt>Matrícula de acesso ao portal</dt><dd>{{ $aluno->matricula_login ?: 'Não cadastrada' }}</dd></div>
            <div><dt>Situação da conta</dt><dd>{{ $aluno->ativo ? 'Ativa' : 'Bloqueada' }}</dd></div>
            <div><dt>Confirmação do e-mail</dt><dd>{{ $aluno->email_verified_at ? 'Confirmado' : 'Não confirmado' }}</dd></div>
            <div><dt>Primeiro acesso</dt><dd>{{ $aluno->primeiro_acesso_pendente ? 'Pendente' : 'Concluído' }}</dd></div>
            <div><dt>Senha</dt><dd>{{ $aluno->senha_definitiva_pendente ? 'Troca de senha definitiva pendente' : (filled($aluno->password) ? 'Protegida — não pode ser visualizada' : 'Não cadastrada') }}</dd></div>
        </dl>
    </section>

    <section class="sc-panel" aria-labelledby="matriculas-academicas">
        <div class="sc-panel-heading">
            <h2 id="matriculas-academicas">Matrículas acadêmicas</h2>
            <p>Cada curso pode ter um número de matrícula distinto do identificador de acesso.</p>
        </div>
        <div class="sc-panel-body sc-stack">
            @forelse ($aluno->matriculas as $matricula)
                <p><strong>{{ $matricula->numero_matricula ?: 'Sem número' }}</strong> — {{ $matricula->curso?->titulo ?: 'Curso não informado' }}</p>
            @empty
                <p>Nenhuma matrícula acadêmica vinculada.</p>
            @endforelse
        </div>
    </section>

    <section class="sc-panel" aria-labelledby="atalhos-recuperacao">
        <div class="sc-panel-heading">
            <h2 id="atalhos-recuperacao">Senha e recuperação de acesso</h2>
            <p>A senha atual não pode ser consultada. Use as operações já existentes para permitir que o aluno defina outra.</p>
        </div>
        <div class="sc-panel-body sc-stack">
            <p><strong>Recuperar acesso:</strong> prepara um link individual de redefinição pelo canal autorizado.</p>
            <p><strong>Gerar senha temporária:</strong> emite um código de 8 dígitos, válido por 30 minutos e exibido uma única vez, para o aluno definir uma nova senha.</p>
            <p>As duas operações exigem confirmação da identidade, motivo do atendimento e senha da Secretaria. Nenhum código é gerado apenas por abrir esta consulta.</p>
            <a class="sc-button sc-primary" href="{{ route('secretaria.acessos.show', $aluno) }}#acesso">Recuperar acesso ou gerar código @include('secretaria.central.icone', ['nome' => 'seta'])</a>
        </div>
    </section>
@endsection
