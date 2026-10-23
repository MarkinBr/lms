@extends('layouts.aluno-auth')

@section('title', $primeiroAcesso ? 'Primeiro acesso' : 'Recuperar acesso')

@section('content')
    <section class="login-sec">
        <div class="wrap login-grid">
            <div class="login-left">
                <h1 class="serif">
                    {{ $primeiroAcesso ? 'PRIMEIRO' : 'RECUPERAR' }}
                    <span class="l2 gold-text">{{ $primeiroAcesso ? 'ACESSO' : 'SENHA' }}</span>
                </h1>
                <div class="crown-rule" style="justify-content: flex-start">
                    <span class="ln"></span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7z"/><path d="M3 20h18"/></svg>
                    <span class="ln"></span>
                </div>

                <p class="welcome">{{ $primeiroAcesso ? 'Vamos preparar sua conta.' : 'Recupere seu acesso com segurança.' }}</p>
                <p class="desc">
                    Informe o mesmo e-mail usado na inscrição. Se sua matrícula já foi aprovada e sua conta estiver ativa, você receberá um link pessoal e temporário para definir uma nova senha.
                </p>

                <div class="login-feats">
                    <div class="lf">
                        <span class="ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                        </span>
                        <div><b>Confira seu e-mail</b><small>O link será enviado para o endereço cadastrado pela Secretaria.</small></div>
                    </div>
                    <div class="lf">
                        <span class="ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </span>
                        <div><b>Link temporário</b><small>Por segurança, ele expira e só pode ser usado uma vez.</small></div>
                    </div>
                    <div class="lf">
                        <span class="ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                        </span>
                        <div><b>Resposta confidencial</b><small>A tela não informa se um endereço está ou não cadastrado.</small></div>
                    </div>
                </div>
            </div>

            <form class="login-card" method="POST" action="{{ route('password.email') }}" data-auth-form>
                @csrf

                <div class="lc-ic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 10.2-10.2M16 7l3 3M14 9l2 2"/></svg>
                </div>
                <h2>{{ $primeiroAcesso ? 'DEFINIR MINHA SENHA' : 'RECUPERAR ACESSO' }}</h2>
                <p class="lc-sub">Enviaremos as instruções somente quando a conta pertencer a um aluno ativo.</p>

                @if (session('status'))
                    <div class="auth-alert auth-alert--success" role="status">
                        <strong>Solicitação recebida</strong>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="auth-alert" role="alert">
                        <strong>Confira o e-mail</strong>
                        Informe um endereço de e-mail válido para continuar.
                    </div>
                @endif

                <div class="lfield">
                    <label for="email">E-MAIL CADASTRADO</label>
                    <div class="inp">
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="seuemail@exemplo.com"
                            autocomplete="email"
                            inputmode="email"
                            maxlength="255"
                            required
                            autofocus
                        >
                        <span class="si" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-navy btn-block btn-lg auth-submit">
                    <span data-submit-text>ENVIAR LINK SEGURO</span>
                </button>

                <a class="auth-back" href="{{ route('aluno.login') }}">Voltar para o login</a>

                <div class="secure-box">
                    <span class="ic" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <div><b>Não recebeu?</b><small>Verifique o spam e aguarde um minuto antes de solicitar novamente.</small></div>
                </div>
            </form>
        </div>
    </section>
@endsection
