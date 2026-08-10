<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Acadêmico | CETSADP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Libre+Baskerville:wght@400;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --navy-deep: #0a1628;
            --navy-primary: #1a2942;
            --gold-accent: #c9a050;
            --gold-light: #e4d4a8;
            --cream: #fdfbf7;
            --off-white: #f8f6f2;
            --charcoal: #2d3748;
            --slate: #4a5568;
            --border-subtle: #e2dfd8;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            min-height: 100vh;
            display: flex;
            position: relative;
            overflow-x: hidden;
        }

        /* Background Pattern - Sutil e Sofisticado */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                repeating-linear-gradient(90deg, transparent, transparent 2px, rgba(201, 160, 80, 0.02) 2px, rgba(201, 160, 80, 0.02) 4px),
                repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(201, 160, 80, 0.02) 2px, rgba(201, 160, 80, 0.02) 4px);
            pointer-events: none;
        }

        /* Layout Split Screen */
        .container {
            display: flex;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            box-shadow: 0 0 60px rgba(0, 0, 0, 0.08);
            background: white;
        }

        /* Painel Esquerdo - Branding */
        .brand-panel {
            flex: 1;
            background: linear-gradient(165deg, var(--navy-deep) 0%, var(--navy-primary) 100%);
            padding: 80px 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(201, 160, 80, 0.08), transparent 70%);
            border-radius: 50%;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(201, 160, 80, 0.05), transparent 70%);
            border-radius: 50%;
        }

        .brand-content {
            position: relative;
            z-index: 1;
        }

        .institution-seal {
            width: 120px;
            height: 120px;
            margin: 0 0 40px 0;
            border: 2px solid var(--gold-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            animation: sealGlow 3s ease-in-out infinite;
        }

        @keyframes sealGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(201, 160, 80, 0.2); }
            50% { box-shadow: 0 0 40px rgba(201, 160, 80, 0.4); }
        }

        .seal-inner {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            color: var(--gold-accent);
            letter-spacing: -2px;
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            font-family: 'Libre Baskerville', serif;
            font-size: 16px;
            color: var(--gold-light);
            line-height: 1.8;
            max-width: 420px;
            margin-bottom: 60px;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .brand-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: auto;
            padding-top: 60px;
            border-top: 1px solid rgba(201, 160, 80, 0.2);
        }

        .stat-item {
            opacity: 0;
            animation: fadeInUp 0.8s forwards;
        }

        .stat-item:nth-child(1) { animation-delay: 0.2s; }
        .stat-item:nth-child(2) { animation-delay: 0.4s; }
        .stat-item:nth-child(3) { animation-delay: 0.6s; }
        .stat-item:nth-child(4) { animation-delay: 0.8s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--gold-accent);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        /* Painel Direito - Login */
        .login-panel {
            flex: 1;
            padding: 80px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--off-white);
        }

        .login-header {
            margin-bottom: 50px;
        }

        .login-pretitle {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--gold-accent);
            font-weight: 600;
            margin-bottom: 16px;
        }

        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            color: var(--navy-deep);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .login-description {
            font-size: 15px;
            color: var(--slate);
            line-height: 1.6;
        }

        .form-container {
            max-width: 440px;
        }

        .input-group {
            margin-bottom: 28px;
        }

        .input-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--charcoal);
            margin-bottom: 10px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .input-field {
            width: 100%;
            padding: 16px 20px;
            border: 1.5px solid var(--border-subtle);
            background: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            color: var(--navy-deep);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .input-field::placeholder {
            color: #a0aec0;
        }

        .input-field:focus {
            border-color: var(--gold-accent);
            background: white;
            box-shadow: 0 0 0 3px rgba(201, 160, 80, 0.08);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-input {
            width: 18px;
            height: 18px;
            border: 1.5px solid var(--border-subtle);
            cursor: pointer;
            accent-color: var(--navy-deep);
        }

        .checkbox-label {
            color: var(--charcoal);
            cursor: pointer;
            user-select: none;
        }

        .forgot-password {
            color: var(--navy-deep);
            text-decoration: none;
            font-weight: 500;
            position: relative;
            transition: color 0.3s;
        }

        .forgot-password::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--gold-accent);
            transition: width 0.3s;
        }

        .forgot-password:hover {
            color: var(--gold-accent);
        }

        .forgot-password:hover::after {
            width: 100%;
        }

        .submit-button {
            width: 100%;
            padding: 18px 32px;
            background: var(--navy-deep);
            color: white;
            border: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .submit-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(201, 160, 80, 0.3), transparent);
            transition: left 0.6s;
        }

        .submit-button:hover::before {
            left: 100%;
        }

        .submit-button:hover {
            background: var(--navy-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(10, 22, 40, 0.25);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 40px 0;
            gap: 20px;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: var(--border-subtle);
        }

        .divider-text {
            font-size: 12px;
            color: var(--slate);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer-note {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--border-subtle);
            text-align: center;
            font-size: 12px;
            color: var(--slate);
            line-height: 1.8;
        }

        .footer-note strong {
            color: var(--navy-deep);
            font-weight: 600;
        }

        .error-banner {
            background: #fff5f5;
            border-left: 3px solid #e53e3e;
            padding: 16px 20px;
            margin-bottom: 28px;
            font-size: 14px;
            color: #c53030;
        }

        /* Responsivo */
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
            }

            .brand-panel {
                padding: 60px 40px;
                min-height: 400px;
            }

            .brand-stats {
                grid-template-columns: repeat(4, 1fr);
                padding-top: 40px;
            }

            .login-panel {
                padding: 60px 40px;
            }

            .brand-title {
                font-size: 36px;
            }

            .login-title {
                font-size: 30px;
            }
        }

        @media (max-width: 640px) {
            .brand-panel {
                padding: 40px 24px;
            }

            .login-panel {
                padding: 40px 24px;
            }

            .brand-title {
                font-size: 28px;
            }

            .login-title {
                font-size: 24px;
            }

            .brand-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .institution-seal {
                width: 100px;
                height: 100px;
            }

            .seal-inner {
                font-size: 40px;
            }
        }

        /* Loading State */
        .submit-button.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .submit-button.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Painel de Login -->
        <div class="login-panel">
            <div class="login-header">
                <div class="login-pretitle">Portal Acadêmico</div>
                <h2 class="login-title">Bem-vindo de Volta</h2>
                <p class="login-description">Acesse sua área de estudos e acompanhe seu desenvolvimento acadêmico e espiritual.</p>
            </div>

            <form method="POST" action="{{ route('aluno.login.submit') }}" class="form-container">
                @csrf

                <!-- Mensagem de Erro -->
                @if ($errors->any())
                    <div class="error-banner">
                        <strong>Erro de Autenticação:</strong> Verifique suas credenciais e tente novamente.
                    </div>
                @endif

                <div class="input-group">
                    <label for="email" class="input-label">Endereço de E-mail</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="input-field"
                        placeholder="aluno@cetsadp.edu.br"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                </div>

                <div class="input-group">
                    <label for="password" class="input-label">Senha de Acesso</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="input-field"
                        placeholder="Digite sua senha"
                        required
                    >
                </div>

                <div class="form-options">
                    <div class="checkbox-group">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember" 
                            class="checkbox-input"
                        >
                        <label for="remember" class="checkbox-label">Manter conectado</label>
                    </div>
                    <a href="#" class="forgot-password">Recuperar senha</a>
                </div>

                <button type="submit" class="submit-button" id="submitBtn">
                    Acessar Portal
                </button>

                <div class="divider">
                    <div class="divider-line"></div>
                    <span class="divider-text">CETSADP</span>
                    <div class="divider-line"></div>
                </div>

                <div class="footer-note">
                    <strong>Assembleia de Deus de Paracambi</strong><br>
                    Rua Afonso Franco, Nº15 Centro – Paracambi/RJ<br>
                    &copy; 2025 CETSADP. Todos os direitos reservados.
                </div>
            </form>
        </div>

        <!-- Painel de Branding -->
        <div class="brand-panel">
            <div class="brand-content">
                <div class="institution-seal">
                    <div class="seal-inner">C</div>
                </div>
                
                <h1 class="brand-title">Centro Educacional, Teológico e Social</h1>
                <p class="brand-subtitle">Formando líderes espirituais e agentes de transformação social desde nossa fundação pela Assembleia de Deus de Paracambi.</p>
            </div>

            <div class="brand-stats">
                <div class="stat-item">
                    <div class="stat-number">15+</div>
                    <div class="stat-label">Anos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Alunos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Aprovação</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Docentes</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form submission com loading state
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            if (this.checkValidity()) {
                submitBtn.classList.add('loading');
                submitBtn.textContent = '';
            }
        });

        // Input focus effects
        const inputs = document.querySelectorAll('.input-field');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Parallax effect no painel de branding (desktop only)
        if (window.innerWidth > 1024) {
            const brandPanel = document.querySelector('.brand-panel');
            
            window.addEventListener('mousemove', (e) => {
                const mouseX = e.clientX / window.innerWidth;
                const mouseY = e.clientY / window.innerHeight;
                
                const moveX = (mouseX - 0.5) * 20;
                const moveY = (mouseY - 0.5) * 20;
                
                brandPanel.style.transform = `translate(${moveX}px, ${moveY}px)`;
                brandPanel.style.transition = 'transform 0.3s ease-out';
            });
        }
    </script>
</body>
</html>