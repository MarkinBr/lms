@extends('secretaria.central.layout')
@section('title', 'Revisar entrega ao aluno')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.acessos.index') }}">Acessos dos alunos</a><span aria-hidden="true">/</span><a href="{{ route('secretaria.acessos.show', $aluno) }}">Ficha do aluno</a><span aria-hidden="true">/</span><span aria-current="page">Revisar entrega</span>@endsection
@section('content')
    @php
        $canalEntrega = data_get($entrega, 'canal');
        $presencial = $canalEntrega === 'presencial';
        $nomesCanais = ['whatsapp' => 'WhatsApp', 'email' => 'E-mail', 'presencial' => 'Atendimento presencial'];
        $nomesTipos = ['ativacao' => 'Ativação de acesso', 'recuperacao' => 'Recuperação de acesso', 'temporaria' => 'Senha temporária', 'mensagem' => 'Mensagem ao aluno', 'email' => 'Confirmação de e-mail', 'confirmar_email' => 'Confirmação de novo e-mail'];
        $expiraEm = data_get($entrega, 'expira_em');
    @endphp
    <div class="sc-intro"><div><p class="sc-eyebrow">Conferir para entregar com cuidado</p><h1>Revisar entrega ao aluno</h1><p>Confira o destinatário e o conteúdo antes de confirmar.</p></div><span class="sc-tag">{{ $nomesCanais[$canalEntrega] ?? 'Canal não informado' }}</span></div>
    <div class="sc-alert sc-alert-warning" role="status">Esta etapa é de exibição única. Não atualize a página. Ao sair, o conteúdo sensível não poderá ser recuperado pelo histórico.</div>
    <div class="sc-layout">
        <section class="sc-panel" aria-labelledby="entrega-titulo">
            <div class="sc-panel-heading"><h2 id="entrega-titulo">{{ $nomesTipos[data_get($entrega, 'tipo')] ?? 'Entrega ao aluno' }}</h2><p>{{ $aluno->name }} · Matrícula de acesso {{ $aluno->matricula_login ?: 'Não informada' }}</p></div>
            <div class="sc-panel-body sc-stack">
                <dl class="sc-summary"><div><dt>Canal</dt><dd>{{ $nomesCanais[$canalEntrega] ?? 'Não informado' }}</dd></div><div><dt>Destinatário</dt><dd>{{ $presencial ? $aluno->name : (data_get($entrega, 'destino') ?: 'Não informado') }}</dd></div>@if ($expiraEm)<div><dt>Validade da credencial</dt><dd>{{ \Illuminate\Support\Carbon::parse($expiraEm)->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (Brasília)</dd></div>@endif</dl>
                <div class="sc-field"><label for="entrega-mensagem">Conteúdo preparado para o aluno</label><textarea id="entrega-mensagem" class="sc-message-preview" rows="10" readonly autocomplete="off" spellcheck="false" aria-describedby="entrega-aviso">{{ data_get($entrega, 'mensagem') }}</textarea></div>
                <p id="entrega-aviso" class="sc-note">{{ $canalEntrega === 'whatsapp' ? 'Confirmar transmite este texto ao WhatsApp e abre um rascunho. Use a conta institucional da equipe e envie a mensagem manualmente. Abrir a conversa não comprova envio, entrega ou leitura.' : ($presencial ? 'Entregue as instruções diretamente ao próprio aluno, em local reservado. O código não será mostrado novamente.' : 'Confirmar solicita o envio pela fila. Enfileiramento e aceitação pelo transporte não comprovam chegada à caixa de entrada.') }}</p>
                <form action="{{ route('secretaria.acessos.entrega.enviar', ['aluno' => $aluno, 'entrega' => $entregaId]) }}" method="post" class="sc-stack" @if($canalEntrega === 'whatsapp') data-external-handoff @endif>
                    @csrf
                    <input type="hidden" name="confirmar" value="1">
                    <label class="sc-check"><input type="checkbox" name="destino_conferido" value="1" required><span>Conferi novamente {{ $presencial ? 'a identidade do aluno e o conteúdo para entrega presencial' : 'o contato do próprio aluno e o conteúdo desta mensagem' }}.</span></label>
                    <div class="sc-actions"><a class="sc-button" href="{{ route('secretaria.acessos.show', $aluno) }}">Sair da entrega</a><button type="submit" class="sc-button sc-primary">{{ $canalEntrega === 'whatsapp' ? 'Confirmar e abrir rascunho no WhatsApp' : ($presencial ? 'Confirmar entrega presencial' : 'Confirmar envio pela fila de e-mail') }}</button></div>
                </form>
            </div>
        </section>
        <aside class="sc-aside"><section class="sc-side-card sc-side-gold">@include('secretaria.central.icone', ['nome' => 'escudo'])<h2>Conteúdo reservado</h2><p>Não fotografe esta tela nem copie credenciais para anotações, histórico ou campos do cadastro.</p><p>A validade e o destino serão conferidos novamente pelo servidor ao confirmar.</p></section><section class="sc-side-card"><h2>Contato incorreto?</h2><p>Saia desta etapa e corrija o cadastro na ficha. Não encaminhe a mensagem a outro destinatário.</p><a class="sc-text-link" href="{{ route('secretaria.acessos.show', $aluno) }}">Voltar à ficha do aluno</a></section></aside>
    </div>
@endsection
