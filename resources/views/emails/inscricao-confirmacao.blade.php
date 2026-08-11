<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmação de inscrição</title>
</head>
<body style="margin:0;background:#f4f1ea;color:#24364b;font-family:Arial,sans-serif;line-height:1.5">
    <main style="max-width:640px;margin:32px auto;background:#ffffff;border:1px solid #e4ddd0;border-radius:12px;overflow:hidden;box-shadow:0 10px 28px rgba(16,42,67,.08)">
        <header style="background:#102a43;color:#ffffff;padding:32px">
            <p style="margin:0;color:#d5af62;font-size:12px;font-weight:bold;letter-spacing:2px">CENTRO EDUCACIONAL, TEOLÓGICO E SOCIAL</p>
            <h1 style="margin:8px 0 0;font-family:Georgia,serif;font-size:29px">Inscrição recebida</h1>
        </header>

        <section style="padding:32px">
            <p>Olá, {{ $inscricoes->first()->nome_completo }}.</p>
            <p>Recebemos sua inscrição. Acompanhe o processo pelo botão abaixo, sem precisar preencher dados novamente.</p>

            @foreach ($inscricoes as $inscricao)
                <article style="margin:20px 0;padding:20px;border:1px solid #e7dfd0;border-left:4px solid #c69a42;border-radius:8px;background:#fcfaf6">
                    <strong style="display:block;font-size:17px">{{ $inscricao->curso->titulo }}</strong>
                    <span style="display:block;margin-top:3px;color:#5e6977">Modalidade: {{ $inscricao->courseModality->nome }}</span>
                    <span style="display:block;margin-top:14px;color:#6b7280;font-size:12px;font-weight:bold;letter-spacing:.7px">PROTOCOLO</span>
                    <span style="display:block;color:#102a43;font-family:monospace;font-size:18px;font-weight:bold">{{ $inscricao->protocol }}</span>
                    <a href="{{ $linksAcompanhamento[$inscricao->id] }}" style="display:inline-block;margin-top:18px;padding:12px 18px;background:#102a43;border-radius:5px;color:#fff;font-size:13px;font-weight:bold;letter-spacing:.4px;text-decoration:none">ACOMPANHAR INSCRIÇÃO</a>
                </article>
            @endforeach

            <p style="color:#5e6977;font-size:14px">O link é pessoal e válido por 30 dias. Guarde também o protocolo acima.</p>
            <p style="margin-bottom:0">Atenciosamente,<br><strong>Secretaria CETSADP</strong></p>
        </section>
    </main>
</body>
</html>
