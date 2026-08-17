@php
    $logo = asset('assets/images/cetsadp.png');
@endphp

<div class="cetsadp-admin-login-root">
<style>
    .cetsadp-admin-login {
        --navy: #102a43;
        --navy-deep: #071a31;
        --gold: #c8993d;
        --ink: #16283f;
        --muted: #64748b;
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(380px, .92fr);
        background: #f3f6fa;
        color: var(--ink);
    }

    .cetsadp-admin-login__brand {
        position: relative;
        display: flex;
        min-height: 100vh;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
        padding: clamp(2rem, 5vw, 5rem);
        background:
            radial-gradient(circle at 10% 5%, rgba(200, 153, 61, .22), transparent 35%),
            linear-gradient(145deg, var(--navy-deep), var(--navy));
        color: white;
    }

    .cetsadp-admin-login__brand::after {
        position: absolute;
        inset: 10% -20% auto auto;
        width: 32rem;
        height: 32rem;
        border: 1px solid rgba(255, 255, 255, .11);
        border-radius: 50%;
        content: '';
        box-shadow: 0 0 0 3rem rgba(255, 255, 255, .025), 0 0 0 6rem rgba(255, 255, 255, .018);
        pointer-events: none;
    }

    .cetsadp-admin-login__brand-content,
    .cetsadp-admin-login__brand-footer {
        position: relative;
        z-index: 1;
        max-width: 40rem;
    }

    .cetsadp-admin-login__brand-header {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .cetsadp-admin-login__logo {
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 1.2rem;
        background: white;
        object-fit: contain;
        padding: .4rem;
    }

    .cetsadp-admin-login__eyebrow {
        margin: 0 0 .75rem;
        color: #e5bd68;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .18em;
        text-transform: uppercase;
    }

    .cetsadp-admin-login__brand h1 {
        max-width: 36rem;
        margin: 4rem 0 1.2rem;
        font-family: Georgia, serif;
        font-size: clamp(2.3rem, 4.5vw, 4.6rem);
        line-height: 1.04;
        letter-spacing: -.04em;
    }

    .cetsadp-admin-login__brand h1 em {
        color: #e5bd68;
        font-style: normal;
    }

    .cetsadp-admin-login__brand-copy {
        max-width: 34rem;
        color: rgba(255, 255, 255, .72);
        font-size: 1rem;
        line-height: 1.7;
    }

    .cetsadp-admin-login__status {
        display: inline-flex;
        align-items: center;
        gap: .6rem;
        margin-top: 2rem;
        padding: .65rem .9rem;
        border: 1px solid rgba(255, 255, 255, .15);
        border-radius: 999px;
        background: rgba(255, 255, 255, .06);
        color: rgba(255, 255, 255, .82);
        font-size: .78rem;
    }

    .cetsadp-admin-login__status-dot {
        width: .5rem;
        height: .5rem;
        border-radius: 50%;
        background: #55ce8a;
        box-shadow: 0 0 0 .25rem rgba(85, 206, 138, .16);
    }

    .cetsadp-admin-login__brand-footer {
        color: rgba(255, 255, 255, .52);
        font-size: .75rem;
    }

    .cetsadp-admin-login__form-side {
        display: grid;
        place-items: center;
        padding: clamp(1.5rem, 5vw, 5rem);
        background: #f8fafc;
    }

    .cetsadp-admin-login__card {
        width: min(100%, 31rem);
        padding: clamp(1.5rem, 4vw, 3rem);
        border: 1px solid #e2e8f0;
        border-radius: 1.5rem;
        background: rgba(255, 255, 255, .95);
        box-shadow: 0 1.5rem 4rem rgba(15, 37, 64, .1);
    }

    .cetsadp-admin-login__card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .cetsadp-admin-login__tag {
        color: var(--gold);
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .15em;
        text-transform: uppercase;
    }

    .cetsadp-admin-login__card h2 {
        margin: 0;
        color: var(--navy);
        font-family: Georgia, serif;
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        letter-spacing: -.03em;
    }

    .admin-login-heading-accent {
        color: var(--gold);
        font-style: normal;
    }

    .cetsadp-admin-login__subtitle {
        margin: .8rem 0 1.8rem;
        color: var(--muted);
        font-size: .9rem;
        line-height: 1.6;
    }

    .cetsadp-admin-login__form {
        display: grid;
        gap: 1.2rem;
    }

    .cetsadp-admin-login__form .fi-fo-field-wrp-label {
        color: #334155;
        font-weight: 700;
    }

    .cetsadp-admin-login__form .fi-input-wrp {
        border-color: #d8e0ea;
        border-radius: .8rem;
        box-shadow: none;
    }

    .cetsadp-admin-login__form .fi-input-wrp:focus-within {
        border-color: var(--gold);
        box-shadow: 0 0 0 3px rgba(200, 153, 61, .15);
    }

    .cetsadp-admin-login__actions {
        margin-top: .4rem;
    }

    .cetsadp-admin-login__actions .fi-btn {
        width: 100%;
        border-radius: .8rem;
        background: var(--navy);
    }

    .cetsadp-admin-login__actions .fi-btn:hover {
        background: var(--navy-deep);
    }

    .cetsadp-admin-login__safe {
        display: flex;
        gap: .7rem;
        margin-top: 1.5rem;
        padding-top: 1.2rem;
        border-top: 1px solid #e7edf4;
        color: var(--muted);
        font-size: .75rem;
        line-height: 1.5;
    }

    .cetsadp-admin-login__safe strong {
        display: block;
        color: #334155;
    }

    @media (max-width: 900px) {
        .cetsadp-admin-login {
            grid-template-columns: 1fr;
        }

        .cetsadp-admin-login__brand {
            min-height: auto;
            padding: 2rem 1.5rem 2.5rem;
        }

        .cetsadp-admin-login__brand h1 {
            margin-top: 2.5rem;
            font-size: 2.6rem;
        }

        .cetsadp-admin-login__brand-footer {
            margin-top: 2rem;
        }

        .cetsadp-admin-login__form-side {
            padding: 1.5rem;
        }
    }
</style>

<main class="cetsadp-admin-login">
    <section class="cetsadp-admin-login__brand" aria-label="Identidade CETSADP">
        <div class="cetsadp-admin-login__brand-content">
            <div class="cetsadp-admin-login__brand-header">
                <img class="cetsadp-admin-login__logo" src="{{ $logo }}" alt="CETSADP">
                <div>
                    <strong>CETSADP</strong>
                    <div style="color:rgba(255,255,255,.6);font-size:.75rem">Gestão institucional</div>
                </div>
            </div>

            <p class="cetsadp-admin-login__eyebrow">Portal administrativo</p>
            <h1>Administração com <em>clareza</em>, segurança e propósito.</h1>
            <p class="cetsadp-admin-login__brand-copy">
                Um ambiente reservado à equipe administrativa para gerenciar cursos, pessoas,
                conteúdos e operações acadêmicas do CETSADP.
            </p>
            <span class="cetsadp-admin-login__status">
                <span class="cetsadp-admin-login__status-dot" aria-hidden="true"></span>
                Ambiente protegido e disponível
            </span>
        </div>

        <p class="cetsadp-admin-login__brand-footer">
            Centro Educacional, Teológico e Social da Assembleia de Deus em Paracambi
        </p>
    </section>

    <section class="cetsadp-admin-login__form-side" aria-label="Acesso administrativo">
        <div class="cetsadp-admin-login__card">
            <div class="cetsadp-admin-login__card-top">
                <span class="cetsadp-admin-login__tag">Acesso restrito</span>
                <span aria-hidden="true" style="color:#c8993d;font-size:1.5rem">✦</span>
            </div>

            <h2>{!! $this->getHeading() !!}</h2>
            <p class="cetsadp-admin-login__subtitle">
                Use seu usuário ou e-mail institucional e sua senha para continuar.
            </p>

            <x-filament-panels::form id="admin-login-form" wire:submit="authenticate" class="cetsadp-admin-login__form">
                {{ $this->form }}

                <div class="cetsadp-admin-login__actions">
                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="true"
                    />
                </div>
            </x-filament-panels::form>

            @if (filament()->hasPasswordReset())
                <div style="margin-top:1rem;text-align:center;font-size:.8rem">
                    <a href="{{ filament()->getRequestPasswordResetUrl() }}" style="color:#8b661f;text-decoration:underline">
                        Esqueci minha senha
                    </a>
                </div>
            @endif

            <div style="margin-top:1rem;text-align:center;font-size:.8rem">
                <a href="{{ route('admin.token.create') }}" style="color:#8b661f;text-decoration:underline">
                    Entrar com token de 24 palavras
                </a>
            </div>

            <div class="cetsadp-admin-login__safe">
                <span aria-hidden="true" style="color:#2f8f5b;font-size:1.1rem">✓</span>
                <div>
                    <strong>Ambiente seguro</strong>
                    Acesso protegido por sessão, validação de permissões e limite de tentativas.
                </div>
            </div>
        </div>
    </section>
</main>
</div>
