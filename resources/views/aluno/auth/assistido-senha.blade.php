@extends('aluno.auth.assistido-layout')
@section('titulo', 'Escolha sua nova senha')
@section('conteudo')
    <p>Crie uma senha que só você conheça. Seu acesso ao portal será liberado depois dessa etapa.</p>
    <form method="post" action="{{ route('aluno.assistido.definir') }}">
        @csrf
        <label for="password">Nova senha</label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="1024" aria-describedby="regra-senha" required autofocus>
        <small id="regra-senha">Use ao menos 12 caracteres, com letras maiúsculas e minúsculas, número e símbolo.</small>
        <label for="password_confirmation">Repita a nova senha</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="1024" required>
        <button type="submit">Salvar minha senha</button>
    </form>
@endsection
