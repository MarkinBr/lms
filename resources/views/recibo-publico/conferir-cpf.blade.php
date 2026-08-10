<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Validar Recibo — CETSADP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,sans-serif;background:#f3f4f6;display:grid;place-items:center;min-height:100vh;margin:0;padding:20px}
    .card{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:420px;width:100%}
    h1{margin:0 0 8px;color:#003D7A;font-size:22px}
    p{color:#64748b;margin:0 0 24px;font-size:14px}
    label{display:block;margin-bottom:6px;font-weight:600;color:#334155;font-size:13px}
    input{width:100%;padding:14px;border:2px solid #e2e8f0;border-radius:8px;font-size:18px;text-align:center;letter-spacing:6px;font-weight:600}
    input:focus{outline:none;border-color:#003D7A}
    button{width:100%;background:#003D7A;color:#fff;padding:14px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:16px}
    button:hover{background:#002b56}
    .error{background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:13px}
    .lgpd{font-size:11px;color:#94a3b8;margin-top:24px;line-height:1.5;text-align:center}
  </style>
</head>
<body>
  <div class="card">
    <h1>🔒 Validar Recibo</h1>
    <p>Para sua segurança, informe os <strong>3 últimos dígitos do CPF</strong> do titular para visualizar o recibo.</p>
    @if($errors->any())
      <div class="error">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('recibo.publico.show', $codigo) }}">
      @csrf
      <label for="cpf3">Últimos 3 dígitos do CPF</label>
      <input type="text" id="cpf3" name="cpf3" maxlength="3" pattern="\d{3}" required
             inputmode="numeric" autocomplete="off" autofocus>
      <button type="submit">Conferir e Exibir Recibo</button>
    </form>
    <p class="lgpd">
      Esta verificação garante que somente o titular ou pessoa autorizada
      acessa os dados pessoais. Conforme LGPD (Lei 13.709/2018).
      Tentativas inválidas são registradas.
    </p>
  </div>
</body>
</html>