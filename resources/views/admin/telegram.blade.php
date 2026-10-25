<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Telegram administrativo • CETSADP</title>
    <style>body{margin:0;background:#f5f6fa;color:#1a2744;font:16px/1.6 system-ui,sans-serif}main{max-width:650px;margin:6vh auto;padding:28px;background:white;border-radius:16px;box-shadow:0 8px 30px #1a27441a}h1{font-size:1.6rem}button,a.botao{display:inline-block;padding:12px 20px;background:#1a2744;color:white;border:0;border-radius:8px;cursor:pointer;min-height:44px}code{display:block;overflow-wrap:anywhere;background:#f6f2e6;padding:12px;border-left:4px solid #c9a84c}form{margin:18px 0}</style>
</head>
<body><main>
    <p>CETSADP · Administração</p>
    <h1>Vincular Telegram</h1>
    <p>Este bot é exclusivo da sua conta administrativa autorizada. O código vence em 10 minutos e funciona uma única vez.</p>
    @if($vinculado)<p><strong>Status:</strong> vinculado.</p>@else<p><strong>Status:</strong> não vinculado.</p>@endif
    @if(session('telegram_aviso'))<p role="status">{{ session('telegram_aviso') }}</p>@endif
    @if(session('telegram_codigo'))
        <p>Envie a mensagem abaixo em uma conversa <strong>privada</strong> com seu bot:</p>
        <code>/vincular {{ session('telegram_codigo') }}</code>
        <p>Não compartilhe o código. Ele não é salvo em texto puro.</p>
    @endif
    <form method="post" action="{{ route('admin.telegram.gerar') }}">@csrf<button type="submit">Gerar novo código</button></form>
    <form method="post" action="{{ route('admin.telegram.revogar') }}">@csrf<button type="submit">Revogar acesso do Telegram</button></form>
    <p><a href="/admin">Voltar ao painel</a></p>
</main></body></html>
