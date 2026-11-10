@extends('layouts.aluno-auth')

@section('title', 'Ativar acesso ao Portal do Aluno')

@section('content')
<section class="login-sec">
    <div class="wrap login-grid">
        <div class="login-left">
            <h1 class="serif">PRIMEIRO ACESSO<span class="l2 gold-text">PORTAL DO ALUNO</span></h1>
            <p class="welcome">Defina sua senha pessoal para ativar seu acesso.</p>
            <p class="desc">Sua matrícula permanente: <strong>{{ $aluno->matricula_login }}</strong>.</p>
            <p class="desc">Ao concluir, você confirma a posse de {{ $aluno->email }}. Sua matrícula permanece válida quando trocar de e-mail ou iniciar outro curso.</p>
            <p class="desc">Se precisar de ajuda ou de um novo link, procure a secretaria.</p>
        </div>
        <form class="login-card" method="POST" action="{{ $url }}" data-auth-form>
            @csrf
            <h2>ATIVAR MEU ACESSO</h2>
            <p class="lc-sub">Use pelo menos 12 caracteres, letras maiúsculas e minúsculas, números e símbolos.</p>
            @if($errors->any())
                <div class="auth-alert" role="alert">{{ $errors->first() }}</div>
            @endif
            <div class="lfield">
                <label for="password">NOVA SENHA</label>
                <div class="inp"><input id="password" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="1024" required autofocus></div>
            </div>
            <div class="lfield">
                <label for="password_confirmation">CONFIRME A SENHA</label>
                <div class="inp"><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="1024" required></div>
            </div>
            <button type="submit" class="btn btn-navy btn-block btn-lg auth-submit"><span data-submit-text>ATIVAR ACESSO</span></button>
            <p class="lc-sub">O link é pessoal, tem prazo de validade e só funciona uma vez.</p>
        </form>
    </div>
</section>
@endsection