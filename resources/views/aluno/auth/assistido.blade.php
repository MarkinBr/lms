@extends('aluno.auth.assistido-layout')
@section('titulo', 'Acesso com código temporário')
@section('conteudo')
    <p>Informe sua matrícula ou CPF e o código recebido no atendimento. Em seguida, você escolherá sua senha definitiva.</p>
    <form method="post" action="{{ route('aluno.assistido.entrar') }}">
        @csrf
        <label for="identificador">Matrícula ou CPF</label>
        <input id="identificador" name="identificador" autocomplete="username" maxlength="255" required autofocus>
        <label for="codigo">Código de 8 dígitos</label>
        <input id="codigo" name="codigo" type="password" inputmode="numeric" autocomplete="one-time-code" maxlength="9" pattern="[0-9]{4} ?[0-9]{4}" required aria-describedby="validade">
        <small id="validade">O código vale por 30 minutos e permite até cinco tentativas.</small>
        <button type="submit">Continuar</button>
    </form>
    <p><a href="{{ route('aluno.login') }}">Voltar ao login</a></p>
@endsection
