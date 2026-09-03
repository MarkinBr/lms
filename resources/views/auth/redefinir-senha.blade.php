@extends('layouts.aluno-auth')

@section('title', 'Definir nova senha')

@section('content')
    <section class="login-sec">
        <div class="wrap login-grid">
            <div class="login-left">
                <h1 class="serif">NOVA SENHA<span class="l2 gold-text">PORTAL DO ALUNO</span></h1>
                <div class="crown-rule" style="justify-content: flex-start">
                    <span class="ln"></span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7z"/><path d="M3 20h18"/></svg>
                    <span class="ln"></span>
                </div>

                <p class="welcome">Crie uma senha exclusiva e difícil de adivinhar.</p>
                <p class="desc">Depois da confirmação, o link será invalidado. Por segurança, você voltará ao login e entrará com a nova senha.</p>

                <div class="login-feats">
                    <div class="lf">
                        <span class="ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                        </span>
                        <div><b>Proteção reforçada</b><small>Use uma combinação que você não utiliza em outros serviços.</small></div>
                    </div>
                    <div class="lf">
                        <span class="ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                        </span>
                        <div><b>Acesso pessoal</b><small>Nunca compartilhe sua senha nem o link recebido por e-mail.</small></div>
                    </div>
                </div>
            </div>

            <form class="login-card" method="POST" action="{{ route('password.update') }}" data-auth-form>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="lc-ic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h2>DEFINIR NOVA SENHA</h2>
                <p class="lc-sub">Complete os campos abaixo para ativar ou recuperar seu acesso.</p>

                @if ($errors->any())
                    <div class="auth-alert" role="alert">
                        <strong>Não foi possível definir a senha</strong>
                        <ul>
                            @foreach ($errors->all() as $erro)
                                <li>{{ $erro }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="lfield">
                    <label for="email">E-MAIL</label>
                    <div class="inp">
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $email) }}"
                            autocomplete="email"
                            maxlength="255"
                            required
                            readonly
                        >
                        <span class="si" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                        </span>
                    </div>
                </div>

                <div class="lfield">
                    <label for="password">NOVA SENHA</label>
                    <div class="inp">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            minlength="12"
                            maxlength="1024"
                            required
                            autofocus
                        >
                        <button type="button" class="si" data-password-toggle="password" aria-label="Mostrar senha">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="lfield">
                    <label for="password_confirmation">CONFIRME A NOVA SENHA</label>
                    <div class="inp">
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            minlength="12"
                            maxlength="1024"
                            required
                        >
                        <button type="button" class="si" data-password-toggle="password_confirmation" aria-label="Mostrar confirmação da senha">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="auth-password-rules">
                    <b>Sua senha deve ter:</b> pelo menos 12 caracteres, letras maiúsculas e minúsculas, um número e um símbolo.
                </div>

                <button type="submit" class="btn btn-navy btn-block btn-lg auth-submit">
                    <span data-submit-text>SALVAR NOVA SENHA</span>
                </button>

                <a class="auth-back" href="{{ route('password.request') }}">Solicitar outro link</a>
            </form>
        </div>
    </section>
@endsection
