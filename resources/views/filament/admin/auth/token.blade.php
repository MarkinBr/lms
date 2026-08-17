<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Token de acesso — CETSADP</title>
    <style>
        :root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#16283f;background:#f3f6fa}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 10% 10%,#fff6df,transparent 34%),#f3f6fa}.card{width:min(100%,640px);padding:clamp(24px,5vw,48px);border:1px solid #dce5ef;border-radius:24px;background:rgba(255,255,255,.96);box-shadow:0 24px 64px rgba(15,37,64,.12)}.brand{display:flex;align-items:center;gap:12px;color:#102a43;font-weight:800}.brand img{width:52px;height:52px;padding:5px;border-radius:14px;object-fit:contain;background:#fff;box-shadow:0 5px 20px #102a4320}.eyebrow{margin:32px 0 8px;color:#a67821;font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}h1{margin:0;font-family:Georgia,serif;font-size:clamp(30px,6vw,46px);letter-spacing:-.04em;color:#102a43}h1 em{color:#c8993d;font-style:normal}.lead{color:#64748b;line-height:1.65}label{display:block;margin:20px 0 7px;color:#334155;font-size:13px;font-weight:700}input{width:100%;padding:13px 14px;border:1px solid #d8e0ea;border-radius:10px;font:inherit;color:#16283f;background:#fff}input:focus{outline:0;border-color:#c8993d;box-shadow:0 0 0 3px #c8993d26}.btn{width:100%;margin-top:16px;padding:13px 16px;border:0;border-radius:10px;background:#102a43;color:#fff;font:inherit;font-weight:800;cursor:pointer}.btn:hover{background:#071a31}.notice{padding:13px 15px;border-radius:10px;background:#eef8f1;color:#21633e;font-size:13px}.error{margin-top:7px;color:#a3392f;font-size:13px}.back{display:block;margin-top:22px;text-align:center;color:#8b661f;font-size:13px}.divider{height:1px;margin:30px 0;background:#e6edf4}.hint{color:#64748b;font-size:12px;line-height:1.55}
    </style>
</head>
<body>
    <main class="card">
        <div class="brand"><img src="{{ asset('assets/images/cetsadp.png') }}" alt="CETSADP"><span>CETSADP<br><small style="font-weight:500;color:#64748b">Portal administrativo</small></span></div>
        <p class="eyebrow">Acesso alternativo</p>
        <h1>Token de <em>24 palavras</em></h1>
        <p class="lead">Solicite uma combinação temporária enviada ao e-mail do administrador. Ela expira em 10 minutos e só pode ser utilizada uma vez.</p>

        @if (session('token_requested'))
            <div class="notice">Se o e-mail informado pertencer a uma conta administrativa ativa, as instruções foram enviadas. Verifique também o spam.</div>
        @endif

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.token.request') }}">
            @csrf
            <label for="request-email">E-mail administrativo</label>
            <input id="request-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            <button class="btn" type="submit">Enviar combinação por e-mail</button>
        </form>

        <div class="divider"></div>

        <form method="POST" action="{{ route('admin.token.authenticate') }}">
            @csrf
            <label for="auth-email">E-mail administrativo</label>
            <input id="auth-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            <label for="phrase">Combinação de 24 palavras</label>
            <input id="phrase" name="phrase" type="text" autocomplete="off" spellcheck="false" placeholder="Cole as 24 palavras recebidas" required>
            <p class="hint">Separe as palavras por espaços. Nunca compartilhe esta combinação.</p>
            <button class="btn" type="submit">Validar e entrar no painel</button>
        </form>

        <a class="back" href="{{ url('/admin/login') }}">Voltar ao login com senha</a>
    </main>
</body>
</html>
