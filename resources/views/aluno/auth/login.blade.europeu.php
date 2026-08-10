<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Acadêmico | CETSADP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --burgundy: #5a1a1a;
            --deep-burgundy: #3d0e0e;
            --gold: #d4af37;
            --champagne: #f7e7ce;
            --ivory: #fffff0;
            --warm-white: #faf8f3;
            --stone: #8b7e74;
            --charcoal: #3a3a3a;
            --sage: #9caf88;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--warm-white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(212, 175, 55, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(90, 26, 26, 0.03) 0%, transparent 50%);
        }

        /* Ornamental Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23d4af37' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 1;
            pointer-events: none;
        }

        .main-container {
            width: 100%;
            max-width: 1400px;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            background: white;
            box-shadow: 
                0 0 0 1px rgba(212, 175, 55, 0.1),
                0 20px 60px rgba(90, 26, 26, 0.15),
                0 40px 100px rgba(90, 26, 26, 0.1);
            border-radius: 0;
            overflow: hidden;
            position: relative;
        }

        /* Borda decorativa */
        .main-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, 
                var(--burgundy) 0%, 
                var(--gold) 25%, 
                var(--burgundy) 50%, 
                var(--gold) 75%, 
                var(--burgundy) 100%);
        }

        /* Painel de Login */
        .login-section {
            padding: 80px 70px;
            background: linear-gradient(135deg, var(--ivory) 0%, var(--warm-white) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .login-section::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            width: 1px;
            height: 300px;
            background: linear-gradient(180deg, transparent, var(--gold), transparent);
            transform: translateY(-50%);
        }

        .header-logo {
            width: 90px;
            height: 90px;
            border: 3px solid var(--gold);
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(212, 175, 55, 0.2);
            position: relative;
        }

        .header-logo::before {
            content: '';
            position: absolute;
            inset: -8px;
            border: 1px solid var(--gold);
            border-radius: 50%;
            opacity: 0.3;
        }

        .logo-text {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            font-weight: 700;
            color: var(--burgundy);
            letter-spacing: -1px;
        }

        .institution-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--burgundy);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 8px;
            opacity: 0.8;
        }

        .page-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            font-weight: 700;
            color: var(--deep-burgundy);
            line-height: 1.2;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            font-size: 15px;
            color: var(--stone);
            line-height: 1.6;
            margin-bottom: 40px;
            font-weight: 400;
        }

        .form-wrapper {
            max-width: 100%;
        }

        .field-group {
            margin-bottom: 26px;
            position: relative;
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--charcoal);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .field-input {
            width: 100%;
            padding: 16px 18px;
            border: none;
            border-bottom: 2px solid #e8e3da;
            background: transparent;
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            color: var(--deep-burgundy);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .field-input::placeholder {
            color: #bdb5a8;
        }

        .field-input:focus {
            border-bottom-color: var(--gold);
            background: rgba(212, 175, 55, 0.02);
        }

        .field-input:focus + .field-line {
            transform: scaleX(1);
        }

        .field-line {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--gold);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-extras {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 35px;
            font-size: 13px;
        }

        .remember-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #d8d0c0;
            cursor: pointer;
            accent-color: var(--burgundy);
        }

        .remember-label {
            color: var(--charcoal);
            cursor: pointer;
            user-select: none;
            font-weight: 500;
        }

        .password-link {
            color: var(--burgundy);
            text-decoration: none;
            font-weight: 600;
            position: relative;
            padding-bottom: 2px;
        }

        .password-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--gold);
            transition: width 0.3s ease;
        }

        .password-link:hover::after {
            width: 100%;
        }

        .login-button {
            width: 100%;
            padding: 18px 32px;
            background: var(--burgundy);
            color: white;
            border: 2px solid var(--burgundy);
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .login-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .login-button:hover {
            background: var(--deep-burgundy);
            border-color: var(--gold);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(90, 26, 26, 0.3);
        }

        .button-text {
            position: relative;
            z-index: 1;
        }

        .separator {
            display: flex;
            align-items: center;
            margin: 35px 0;
            gap: 20px;
        }

        .separator-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .separator-text {
            font-size: 11px;
            color: var(--stone);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }

        .footer-info {
            margin-top: 30px;
            font-size: 11px;
            color: var(--stone);
            line-height: 1.8;
            text-align: center;
        }

        /* Painel Informativo */
        .info-section {
            background: linear-gradient(135deg, var(--burgundy) 0%, var(--deep-burgundy) 100%);
            padding: 80px 70px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .info-section::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 50%;
        }

        .info-section::after {
            content: '';
            position: absolute;
            bottom: -150px;
            left: -150px;
            width: 500px;
            height: 500px;
            border: 1px solid rgba(212, 175, 55, 0.08);
            border-radius: 50%;
        }

        .info-content {
            position: relative;
            z-index: 1;
        }

        .ornamental-quote {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--champagne);
            line-height: 1.5;
            margin-bottom: 20px;
            font-style: italic;
            position: relative;
            padding-left: 30px;
        }

        .ornamental-quote::before {
            content: '"';
            position: absolute;
            left: 0;
            top: -10px;
            font-size: 80px;
            color: var(--gold);
            opacity: 0.3;
            font-style: normal;
        }

        .quote-author {
            font-size: 13px;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 50px;
            font-weight: 600;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 50px;
        }

        .feature-item {
            opacity: 0;
            animation: fadeInUp 0.8s forwards;
        }

        .feature-item:nth-child(1) { animation-delay: 0.1s; }
        .feature-item:nth-child(2) { animation-delay: 0.2s; }
        .feature-item:nth-child(3) { animation-delay: 0.3s; }
        .feature-item:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            border: 2px solid var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            background: rgba(212, 175, 55, 0.05);
        }

        .feature-icon svg {
            width: 24px;
            height: 24px;
            stroke: var(--gold);
            fill: none;
            stroke-width: 2;
        }

        .feature-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--champagne);
            margin-bottom: 8px;
        }

        .feature-description {
            font-size: 13px;
            color: rgba(247, 231, 206, 0.8);
            line-height: 1.6;
        }

        .institutional-footer {
            position: relative;
            z-index: 1;
            padding-top: 40px;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
        }

        .footer-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .footer-address {
            font-size: 13px;
            color: rgba(247, 231, 206, 0.7);
            line-height: 1.8;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }

            .login-section,
            .info-section {
                padding: 60px 40px;
            }

            .login-section::after {
                display: none;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .login-section,
            .info-section {
                padding: 40px 24px;
            }

            .page-title {
                font-size: 32px;
            }

            .ornamental-quote {
                font-size: 22px;
            }

            .features-grid {
                gap: 20px;
            }
        }

        .error-alert {
            background: rgba(220, 38, 38, 0.1);
            border-left: 3px solid #dc2626;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #991b1b;
            border-radius: 0 4px 4px 0;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Painel de Login -->
        <div class="login-section">
            <div class="header-logo">
                <div class="logo-text">C</div>
            </div>

            <div class="institution-name">CETSADP</div>
            <h1 class="page-title">Portal Acadêmico</h1>
            <p class="page-subtitle">Acesse sua jornada de formação teológica e desenvolvimento espiritual.</p>

            <form method="POST" action="{{ route('aluno.login.submit') }}" class="form-wrapper">
                @csrf

                @if ($errors->any())
                    <div class="error-alert">
                        <strong>Autenticação não autorizada.</strong> Verifique suas credenciais.
                    </div>
                @endif

                <div class="field-group">
                    <label for="email" class="field-label">Endereço de E-mail</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="field-input"
                        placeholder="aluno@cetsadp.edu.br"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                    <div class="field-line"></div>
                </div>

                <div class="field-group">
                    <label for="password" class="field-label">Senha de Acesso</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="field-input"
                        placeholder="••••••••••••"
                        required
                    >
                    <div class="field-line"></div>
                </div>

                <div class="form-extras">
                    <div class="remember-section">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember" 
                            class="checkbox-custom"
                        >
                        <label for="remember" class="remember-label">Permanecer conectado</label>
                    </div>
                    <a href="#" class="password-link">Recuperar acesso</a>
                </div>

                <button type="submit" class="login-button">
                    <span class="button-text">Acessar Portal</span>
                </button>

                <div class="separator">
                    <div class="separator-line"></div>
                    <span class="separator-text">Est. 2010</span>
                    <div class="separator-line"></div>
                </div>

                <div class="footer-info">
                    &copy; 2025 Centro Educacional, Teológico e Social<br>
                    Assembleia de Deus de Paracambi - Todos os direitos reservados
                </div>
            </form>
        </div>

        <!-- Painel Informativo -->
        <div class="info-section">
            <div class="info-content">
                <div class="ornamental-quote">
                    Formação que transforma vidas, conhecimento que ilumina caminhos.
                </div>
                <div class="quote-author">Nossa Missão</div>

                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                            </svg>
                        </div>
                        <div class="feature-title">Excelência Acadêmica</div>
                        <div class="feature-description">Corpo docente qualificado e metodologia comprovada</div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </div>
                        <div class="feature-title">Vocação Espiritual</div>
                        <div class="feature-description">Formação integral com foco no desenvolvimento cristão</div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                        </div>
                        <div class="feature-title">Comunidade Ativa</div>
                        <div class="feature-description">Rede de apoio e crescimento mútuo entre alunos</div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/>
                            </svg>
                        </div>
                        <div class="feature-title">Impacto Social</div>
                        <div class="feature-description">Preparação para liderança comunitária transformadora</div>
                    </div>
                </div>
            </div>

            <div class="institutional-footer">
                <div class="footer-title">Assembleia de Deus de Paracambi</div>
                <div class="footer-address">
                    Rua Afonso Franco, Nº15 Centro<br>
                    Paracambi, Rio de Janeiro - RJ<br>
                    Brasil
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animação nos inputs
        const inputs = document.querySelectorAll('.field-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.field-line').style.transform = 'scaleX(1)';
            });

            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.querySelector('.field-line').style.transform = 'scaleX(0)';
                }
            });
        });

        // Efeito ripple no botão
        const loginBtn = document.querySelector('.login-button');
        loginBtn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');

            this.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        });

        // Form validation visual feedback
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const btn = this.querySelector('.login-button');
            if (this.checkValidity()) {
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none';
            }
        });
    </script>
</body>
</html>