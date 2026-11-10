<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novo contato pelo site</title>
</head>
<body style="margin:0;background:#f4f1ea;color:#24364b;font-family:Arial,sans-serif;line-height:1.5">
    <main style="max-width:640px;margin:32px auto;background:#fff;border:1px solid #e4ddd0;border-radius:12px;overflow:hidden;box-shadow:0 10px 28px rgba(16,42,67,.08)">
        <header style="background:#102a43;color:#fff;padding:28px 32px">
            <p style="margin:0;color:#d5af62;font-size:12px;font-weight:bold;letter-spacing:2px">CETSADP</p>
            <h1 style="margin:8px 0 0;font-family:Georgia,serif;font-size:27px">Novo contato pelo site</h1>
        </header>
        <section style="padding:32px">
            <p style="margin-top:0">Uma nova mensagem foi enviada pelo formulário de contato institucional.</p>
            <table role="presentation" style="width:100%;border-collapse:collapse;margin:22px 0">
                <tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#6b7280;font-weight:bold;width:35%">Nome</td><td style="padding:10px 0;border-bottom:1px solid #eee">{{ $nome }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#6b7280;font-weight:bold">E-mail</td><td style="padding:10px 0;border-bottom:1px solid #eee">{{ $email }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#6b7280;font-weight:bold">Telefone</td><td style="padding:10px 0;border-bottom:1px solid #eee">{{ $telefone ?: 'Não informado' }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#6b7280;font-weight:bold">Assunto</td><td style="padding:10px 0;border-bottom:1px solid #eee">{{ $assunto }}</td></tr>
            </table>
            <div style="padding:18px 20px;border-left:4px solid #d5af62;background:#fcfaf6;white-space:pre-line">{{ $mensagem }}</div>
            <p style="margin-bottom:0;color:#5e6977;font-size:13px">Para responder, use o botão “Responder” do seu cliente de e-mail. O endereço do visitante está configurado como Reply-To.</p>
        </section>
        <footer style="padding:18px 32px;background:#f8f6f1;color:#6b7280;font-size:12px">Secretaria CETSADP · Centro Educacional, Teológico e Social da Igreja Evangélica Assembleia de Deus em Paracambi</footer>
    </main>
</body>
</html>
