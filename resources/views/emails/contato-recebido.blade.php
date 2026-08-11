<h1>Novo contato pelo site</h1>
<p><strong>Nome:</strong> {{ $dados['nome'] }}</p>
<p><strong>E-mail:</strong> {{ $dados['email'] }}</p>
<p><strong>Telefone:</strong> {{ $dados['telefone'] ?: 'Não informado' }}</p>
<p><strong>Assunto:</strong> {{ $dados['assunto'] }}</p>
<p><strong>Mensagem:</strong></p>
<p>{!! nl2br(e($dados['mensagem'])) !!}</p>
