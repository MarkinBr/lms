<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo') — CETSADP</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f3f5f9;color:#19283b;font:17px/1.6 system-ui,sans-serif}
        main{width:min(100% - 32px,480px);background:white;padding:clamp(24px,5vw,40px);border-radius:16px;border-top:5px solid #b38b38;box-shadow:0 10px 40px #13223812}
        .marca{color:#102945;font-weight:800;letter-spacing:.08em}
        h1{color:#102945;font-size:1.7rem;line-height:1.25}
        p{color:#526176}
        a{display:inline-block;padding:11px 18px;border-radius:8px;background:#102945;color:#fff;text-decoration:none;font-weight:700}
        a:focus-visible{outline:3px solid #b38b38;outline-offset:3px}
    </style>
</head>
<body>
<main>
    <div class="marca">CETSADP</div>
    <h1>@yield('titulo')</h1>
    <p>@yield('descricao')</p>
    @if(request()->is('aluno/acesso-assistido*'))
        <a href="{{ route('aluno.assistido.formulario') }}">Voltar ao acesso assistido</a>
    @else
        <a href="{{ url('/') }}">Voltar ao início</a>
    @endif
</main>
</body>
</html>
