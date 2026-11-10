<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>@yield('titulo') — CETSADP</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f5f9;color:#19283b;font:17px/1.6 system-ui,sans-serif}main{width:min(100% - 32px,480px);margin:8vh auto;background:#fff;padding:clamp(24px,5vw,40px);border-radius:16px;border-top:5px solid #b38b38;box-shadow:0 10px 40px #13223812}h1{font-size:1.7rem;line-height:1.25;color:#102945}p{color:#526176}.marca{color:#102945;font-weight:800;letter-spacing:.08em}label{display:block;font-weight:600;margin-top:20px}input,button{width:100%;font:inherit;border-radius:8px;padding:12px;margin-top:6px}input{border:1px solid #8592a4;min-width:0}button{border:0;background:#102945;color:white;font-weight:700;margin-top:24px;cursor:pointer;min-height:48px}button:disabled{opacity:.5;cursor:default}a{color:#173e65}input:focus-visible,button:focus-visible,a:focus-visible{outline:3px solid #b38b38;outline-offset:3px}.erro{background:#fff1f0;border-left:4px solid #a72a2a;padding:12px;color:#851b1b}small{display:block;color:#526176;margin-top:8px}@media(prefers-reduced-motion:no-preference){button{transition:background .15s}button:hover{background:#25496e}}
    </style>
</head>
<body>
<main>
    <div class="marca">CETSADP</div>
    <h1>@yield('titulo')</h1>
    @if($errors->any())
        <div class="erro" role="alert">{{ $errors->first() }}</div>
    @endif
    @yield('conteudo')
</main>
</body>
</html>
