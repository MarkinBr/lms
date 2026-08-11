<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definir Nova Senha | CETSADP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --burgundy: #1a4d7a;
            --deep-burgundy: #0d2847;
            --gold: #d4af37;
            --champagne: #f7e7ce;
            --ivory: #f0f8ff;
            --warm-white: #f5f9fc;
            --stone: #7a8a99;
            --charcoal: #3a3a3a;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--warm-white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(212, 175, 55, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(26, 77, 122, 0.03) 0%, transparent 50%);
        }
        .card {
            width: 100%;
            max-width: 560px;
            background: white;
            padding: 60px 50px;
            box-shadow:
                0 0 0 1px rgba(212, 175, 55, 0.1),
                0 20px 60px rgba(26, 77, 122, 0.15);
            position: relative;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg,
                var(--burgundy) 0%, var(--gold) 25%,
                var(--burgundy) 50%, var(--gold) 75%,
                var(--burgundy) 100%);
        }
        .brand {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--burgundy);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 8px;
            opacity: 0.8;
            text-align: center;
        }
        h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--deep-burgundy);
            line-height: 1.2;
            margin-bottom: 14px;
            text-align: center;
        }
        .subtitle {
            font-size: 14px;
            color: var(--stone);
            line-height: 1.6;
            margin-bottom: 32px;
            text-align: center;
        }
        .field-group { margin-bottom: 22px; position: relative; }
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
            outline: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .field-input:focus {
            border-bottom-color: var(--gold);
            background: rgba(212, 175, 55, 0.02);
        }
        .field-input[readonly] {
            color: var(--stone);
            cursor: not-allowed;
        }
        .btn {
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
            margin-top: 12px;
        }
        .btn:hover {
            background: var(--deep-burgundy);
            border-color: var(--gold);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26, 77, 122, 0.3);
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
        .error-alert ul { list-style: none; }
        .error-alert li + li { margin-top: 4px; }
        .rules {
            background: rgba(212, 175, 55, 0.06);
            border-left: 3px solid var(--gold);
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 12.5px;
            color: var(--charcoal);
            border-radius: 0 4px 4px 0;
            line-height: 1.7;
        }
        .rules b {
            display: block;
            margin-bottom: 6px;
            color: var(--deep-burgundy);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: var(--burgundy);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover { color: var(--deep-burgundy); }
        .footer-info {
            margin-top: 40px;
            font-size: 11px;
            color: var(--stone);
            line-height: 1.8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">CETSADP</div>
        <h1>Definir Nova Senha</h1>
        <p class="subtitle">
            Escolha uma senha forte para acessar o Portal do Aluno com segurança.
        </p>

        @if ($errors->any())
            <div class="error-alert">
                <ul>
                    @foreach ($errors->all() as $erro)
                        <li>• {{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rules">
            <b>Requisitos da senha</b>
            • Mínimo de 8 caracteres<br>
            • Pelo menos uma letra maiúscula e uma minúscula<br>
            • Pelo menos um número<br>
            • Confirmação deve ser idêntica
        </div>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field-group">
                <label for="email" class="field-label">E-mail</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="field-input"
                    value="{{ old('email', $email) }}"
                    readonly
                    required
                    autocomplete="email"
                >
            </div>

            <div class="field-group">
                <label for="password" class="field-label">Nova Senha</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="field-input"
                    placeholder="••••••••••••"
                    required
                    autocomplete="new-password"
                    autofocus
                >
            </div>

            <div class="field-group">
                <label for="password_confirmation" class="field-label">Confirmar Nova Senha</label>
                <input
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    class="field-input"
                    placeholder="••••••••••••"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn">Redefinir Senha</button>
        </form>

        <a href="{{ route('aluno.login') }}" class="back-link">← Voltar para o login</a>

        <div class="footer-info">
            &copy; {{ date('Y') }} Centro Educacional, Teológico e Social<br>
            Assembleia de Deus de Paracambi
        </div>
    </div>
</body>
</html>
