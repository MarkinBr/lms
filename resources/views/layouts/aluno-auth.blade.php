<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Portal do Aluno') — CETSADP</title>
    <link rel="stylesheet" href="{{ asset('vendor/portal/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site/main.css') }}">
    <style>
        .auth-body {
            min-height: 100vh;
            background: var(--cream);
        }

        .auth-body .page-bg {
            min-height: calc(100vh - 218px);
        }

        .auth-brand {
            display: inline-flex;
            align-items: center;
            gap: 18px;
        }

        .auth-brand img {
            width: min(300px, 55vw);
            max-height: 82px;
            object-fit: contain;
            object-position: left center;
        }

        .auth-seal {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--navy);
            font-family: var(--serif);
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: 1px;
            line-height: 1.35;
            text-align: center;
        }

        .auth-seal svg {
            width: 50px;
            color: var(--gold-deep);
        }

        .auth-seal em {
            display: block;
            color: var(--gold-deep);
            font-style: normal;
        }

        .auth-alert {
            margin-bottom: 18px;
            padding: 13px 15px;
            border: 1px solid #efc6c2;
            border-radius: 10px;
            background: #fff6f5;
            color: #812d27;
            font-size: .83rem;
        }

        .auth-alert--success {
            border-color: #b9dfca;
            background: #f1fbf5;
            color: #176442;
        }

        .auth-alert strong {
            display: block;
            margin-bottom: 2px;
            color: inherit;
        }

        .auth-card-note {
            margin: -8px 0 20px;
            color: var(--muted);
            font-size: .78rem;
            text-align: center;
        }

        .auth-card-note strong {
            color: var(--navy);
        }

        .auth-back {
            display: inline-flex;
            justify-content: center;
            width: 100%;
            margin-top: 16px;
            color: var(--gold-deep);
            font-size: .82rem;
            font-weight: 700;
        }

        .auth-back:hover,
        .lrow a:hover {
            text-decoration: underline;
        }

        .auth-password-rules {
            margin: -4px 0 18px;
            padding: 12px 14px;
            border-radius: 10px;
            background: var(--paper);
            color: var(--muted);
            font-size: .76rem;
        }

        .auth-password-rules b {
            color: var(--navy);
        }

        .lfield .inp button.si {
            right: 2px;
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            cursor: pointer;
        }

        .lfield .inp button.si:hover,
        .lfield .inp button.si:focus-visible {
            color: var(--navy);
            background: rgba(16, 42, 67, .08);
            outline: none;
        }

        .auth-footer {
            padding: 22px 20px;
            border-top: 1px solid rgba(255, 255, 255, .1);
            background: var(--navy-deep);
            color: #cdd6e8;
            font-size: .76rem;
            text-align: center;
        }

        .auth-footer a {
            color: var(--gold-3);
            font-weight: 700;
        }

        .auth-submit[disabled] {
            cursor: wait;
            opacity: .72;
            transform: none;
        }

        .auth-photo-local {
            filter: saturate(.78) contrast(1.04);
        }

        @media (max-width: 1080px) {
            .auth-seal,
            .portal-header .ph-div {
                display: none;
            }

            .auth-body .login-photo {
                display: none;
            }
        }

        @media (max-width: 760px) {
            .portal-top {
                height: auto;
            }

            .portal-top .wrap {
                padding-block: 9px;
            }

            .portal-header .ph-row {
                justify-content: center;
                padding-block: 18px;
            }

            .auth-brand img {
                width: min(260px, 72vw);
            }

            .login-card {
                padding: 30px 22px;
            }

            .lrow {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/portal-auth-redesign.css') }}?v=1">
</head>
<body class="auth-body">
    <div class="portal-top">
        <div class="wrap">
            <div class="tb-left">
                <span>Paracambi — RJ</span>
                <a href="tel:2126833271">(21) 2683-3271</a>
                <a href="mailto:contato@cetsadp.com.br">contato@cetsadp.com.br</a>
            </div>
            <div class="tb-right">
                <a href="{{ route('site.index') }}">Voltar ao site institucional</a>
            </div>
        </div>
    </div>

    <header class="portal-header">
        <div class="wrap ph-row">
            <a class="auth-brand" href="{{ route('site.index') }}" aria-label="CETSADP — página inicial">
                <img src="{{ asset('assets/images/cetsadp2.png') }}" alt="CETSADP">
            </a>
            <span class="ph-div" aria-hidden="true"></span>
            <div class="auth-seal" aria-label="Ensino que transforma vidas e ministérios">
                <svg viewBox="0 0 64 74" fill="none" aria-hidden="true">
                    <path d="M32 4 52 11v22c0 16-9 27-20 36C21 60 12 49 12 33V11L32 4Z" fill="currentColor" opacity=".18"/>
                    <path d="M32 4 52 11v22c0 16-9 27-20 36C21 60 12 49 12 33V11L32 4Z" stroke="currentColor" stroke-width="2"/>
                    <path d="M32 20v30M22 30h20" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span>ENSINO QUE TRANSFORMA<em>VIDAS E MINISTÉRIOS</em></span>
            </div>
        </div>
    </header>

    <main class="page-bg">
        <div class="dotgrid" aria-hidden="true"></div>
        @yield('content')
    </main>

    <footer class="auth-footer">
        <p>© {{ now()->year }} CETSADP — Centro Educacional, Teológico e Social</p>
        <p><a href="mailto:contato@cetsadp.com.br">Precisa de ajuda? Fale com a Secretaria.</a></p>
    </footer>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordToggle);

                if (! input) return;

                const mostrar = input.type === 'password';
                input.type = mostrar ? 'text' : 'password';
                button.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
            });
        });

        document.querySelectorAll('form[data-auth-form]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');

                if (! button) return;

                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                const text = button.querySelector('[data-submit-text]');
                if (text) text.textContent = 'Aguarde…';
            });
        });
    </script>
</body>
</html>
