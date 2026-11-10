<!doctype html>
<html lang="pt-BR">
<body style="margin:0;background:#f3f6fa;color:#16283f;font-family:Arial,sans-serif;line-height:1.6">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden">
                <tr><td style="padding:28px;background:#102a43;color:#fff">
                    <div style="color:#e5bd68;font-size:11px;font-weight:bold;letter-spacing:2px">CETSADP</div>
                    <h1 style="margin:8px 0 0;font-family:Georgia,serif;font-size:26px">Acesso temporário</h1>
                </td></tr>
                <tr><td style="padding:30px">
                    <p>Olá, {{ $nome }}.</p>
                    <p>Recebemos uma solicitação de acesso temporário ao painel administrativo. Use a combinação abaixo dentro de <strong>10 minutos</strong>:</p>
                    <div style="margin:24px 0;padding:20px;border:1px solid #e5c67e;border-left:4px solid #c8993d;border-radius:8px;background:#fffaf0;color:#102a43;font-size:18px;font-weight:bold;letter-spacing:.04em;word-spacing:.25em">{{ $frase }}</div>
                    <p style="color:#526173;font-size:13px">A combinação é de uso único. Se você não solicitou esse acesso, ignore esta mensagem e altere sua senha pelo painel.</p>
                    <p style="margin-bottom:0">Atenciosamente,<br><strong>Secretaria CETSADP</strong></p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
