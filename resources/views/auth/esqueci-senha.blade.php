<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acesso | CETSADP</title>
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
            max-width: 520px;
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
            font-size: 34px;
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
        .field-group { margin-bottom: 26px; position: relative; }
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
        }
        .btn:hover {
            background: var(--deep-burgundy);
            border-color: var(--gold);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26, 77, 122, 0.3);
        }
        .status-alert {
            background: rgba(34, 139, 34, 0.08);
            border-left: 3px solid #2e7d32;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #1b5e20;
            border-radius: 0 4px 4px 0;
            line-height: 1.5;
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
        .back-link {
            display: block;
            text-align: center;
            margin-top: 26px;
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
        <h1>Recuperar Acesso</h1>
        <p class="subtitle">
            Informe o e-mail associado à sua conta. Se o cadastro for encontrado,
            enviaremos um link seguro para você definir uma nova senha.
        </p>

        @if (session('status'))
            <div class="status-alert">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error-alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

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
                    autocomplete="email"
                >
            </div>

            <button type="submit" class="btn">Enviar Instruções</button>
        </form>

        <a href="{{ route('aluno.login') }}" class="back-link">← Voltar para o login</a>

        <div class="footer-info">
            &copy; {{ date('Y') }} Centro Educacional, Teológico e Social<br>
            Assembleia de Deus de Paracambi
        </div>
    </div>
</body>
</html>
