<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmação de inscrição</title>
</head>
<body style="margin:0;background:#f5f3ef;color:#1f2937;font-family:Arial,sans-serif;line-height:1.5">
    <main style="max-width:640px;margin:32px auto;background:#ffffff;border:1px solid #e5e7eb">
        <header style="background:#102a43;color:#ffffff;padding:28px 32px">
            <p style="margin:0;font-size:12px;letter-spacing:1.5px">CETSADP</p>
            <h1 style="margin:8px 0 0;font-size:24px">Inscrição recebida</h1>
        </header>

        <section style="padding:32px">
            <p>Olá, {{ $inscricoes->first()->nome_completo }}.</p>
            <p>Recebemos sua inscrição. Guarde o{{ $inscricoes->count() > 1 ? 's' : '' }} protocolo{{ $inscricoes->count() > 1 ? 's' : '' }} abaixo para acompanhar seu processo no site do CETSADP.</p>

            @foreach ($inscricoes as $inscricao)
                <article style="margin:20px 0;padding:18px;border-left:4px solid #b8892d;background:#faf8f3">
                    <strong style="display:block;font-size:16px">{{ $inscricao->curso->titulo }}</strong>
                    <span style="display:block;color:#4b5563">Modalidade: {{ $inscricao->courseModality->nome }}</span>
                    <span style="display:block;margin-top:12px;font-size:18px;font-weight:bold;color:#102a43">{{ $inscricao->protocol }}</span>
                </article>
            @endforeach

            <p>Para acompanhar, acesse o site e informe somente o protocolo correspondente.</p>
            <p style="margin-bottom:0">Atenciosamente,<br><strong>Secretaria CETSADP</strong></p>
        </section>
    </main>
</body>
</html>
