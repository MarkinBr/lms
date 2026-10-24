@extends('layouts.aluno-auth')

@section('title', 'Portal do Aluno')

@section('content')
    <section class="login-sec">
        <div class="watermark-faces" aria-hidden="true">
            <img src="{{ asset('assets/images/bg_aluno_placeholder.jpg') }}" alt="">
            <img src="{{ asset('assets/images/bg_aluno_placeholder.jpg') }}" alt="">
            <img src="{{ asset('assets/images/bg_aluno_placeholder.jpg') }}" alt="">
        </div>

        <div class="wrap login-grid">
            <div class="login-left">
                <h1 class="serif">PORTAL DO<span class="l2 gold-text">ALUNO</span></h1>
                <div class="crown-rule" style="justify-content: flex-start">
                    <span class="ln"></span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7z"/>
                        <path d="M3 20h18"/>
                    </svg>
                    <span class="ln"></span>
                </div>

                <p class="welcome">Seja bem-vindo!</p>
                <p class="desc">Aqui você acessa seus cursos, materiais, atividades, notas e documentos. Tudo em um só lugar para facilitar sua jornada de aprendizado.</p>

                <div class="login-feats">
                    <div class="lf">
                        <span class="ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/></svg>
                        </span>
                        <div><b>Acesso aos cursos</b><small>Acompanhe o conteúdo das suas matrículas ativas.</small></div>
                    </div>
                    <div class="lf">
                        <span class="ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2Z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7Z"/></svg>
                        </span>
                        <div><b>Materiais exclusivos</b><small>Consulte aulas e materiais disponibilizados pelos professores.</small></div>
                    </div>
                    <div class="lf">
                        <span class="ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="4" width="14" height="18" rx="2"/><path d="M9 4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1H9Z"/><path d="m9 12 2 2 4-4"/></svg>
                        </span>
                        <div><b>Documentos em dia</b><small>Envie pendências e acompanhe a análise da Secretaria.</small></div>
                    </div>
                    <div class="lf">
                        <span class="ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M8 17v-5M13 17V8M18 17V5"/></svg>
                        </span>
                        <div><b>Acompanhe seu progresso</b><small>Consulte frequência, notas e evolução acadêmica.</small></div>
                    </div>
                </div>

                <div class="login-photo">
                    <img class="auth-photo-local" src="{{ asset('assets/portal/biblia-banner.webp') }}" alt="Bíblia aberta sobre uma mesa">
                </div>
            </div>

            <form class="login-card" method="POST" action="{{ route('aluno.login.submit') }}" data-auth-form>
                @csrf

                <div class="lc-ic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h2>ACESSO AO PORTAL</h2>
                <p class="lc-sub">Informe seu e-mail e sua senha para acessar a área exclusiva do aluno.</p>

                @if (session('status'))
                    <div class="auth-alert auth-alert--success" role="status">
                        <strong>Acesso atualizado</strong>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="auth-alert" role="alert">
                        <strong>Não foi possível entrar</strong>
                        {{ $errors->first('email') ?: 'Confira os campos informados e tente novamente.' }}
                    </div>
                @endif

                <div class="lfield">
                    <label for="email">E-MAIL</label>
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
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                    </div>
                </div>

                <div class="lfield">
                    <label for="password">SENHA</label>
                    <div class="inp">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            maxlength="1024"
                            required
                        >
                        <button type="button" class="si" data-password-toggle="password" aria-label="Mostrar senha">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="lrow">
                    <label class="rem" for="remember">
                        <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        Lembrar-me neste dispositivo
                    </label>
                    <a href="{{ route('password.request') }}">Esqueceu sua senha?</a>
                </div>

                <button type="submit" class="btn btn-navy btn-block btn-lg auth-submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span data-submit-text>ENTRAR NO PORTAL</span>
                </button>

                <div class="divider-or">OU</div>

                <a href="{{ route('password.request', ['primeiro_acesso' => 1]) }}" class="btn btn-outline btn-block">
                    PRIMEIRO ACESSO
                </a>

                <div class="secure-box">
                    <span class="ic" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <div><b>Ambiente protegido</b><small>O acesso usa sessão segura e é permitido somente a alunos ativos.</small></div>
                </div>
            </form>
        </div>
    </section>
@endsection
