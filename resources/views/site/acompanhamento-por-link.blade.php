@php
    /** @var array{nome:string,curso:string,modalidade:string,protocolo:string,situacao:string,matricula:?string} $inscricao */
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acompanhamento de inscrição — CETSADP</title>
</head>
<body style="margin:0;background:#f4f1ea;color:#172b45;font-family:Arial,sans-serif;line-height:1.5">
    <main style="max-width:680px;margin:40px auto;padding:0 20px">
        <section style="overflow:hidden;background:#fff;border:1px solid #e4ddd0;border-radius:12px;box-shadow:0 10px 28px rgba(16,42,67,.08)">
            <header style="padding:30px 34px;background:#102a43;color:#fff">
                <p style="margin:0;color:#d5af62;font-size:12px;font-weight:bold;letter-spacing:2px">CETSADP</p>
                <h1 style="margin:8px 0 0;font-family:Georgia,serif;font-size:29px">Acompanhamento de inscrição</h1>
            </header>

            <div style="padding:34px">
                <p style="margin-top:0">Olá, {{ $inscricao['nome'] }}.</p>
                <p>Confira a situação atual da sua inscrição.</p>

                <div style="margin:24px 0;padding:20px;border:1px solid #e7dfd0;border-radius:8px;background:#fcfaf6">
                    <span style="display:block;color:#6b7280;font-size:12px;font-weight:bold;letter-spacing:.8px;text-transform:uppercase">Situação</span>
                    <strong style="display:block;margin-top:5px;color:#102a43;font-size:21px">{{ $inscricao['situacao'] }}</strong>
                </div>

                <dl style="margin:0">
                    <div style="padding:15px 0;border-bottom:1px solid #eee8dc"><dt style="color:#6b7280;font-size:13px">Curso</dt><dd style="margin:3px 0 0;font-weight:bold">{{ $inscricao['curso'] }}</dd></div>
                    @if ($inscricao['modalidade'] !== '')
                        <div style="padding:15px 0;border-bottom:1px solid #eee8dc"><dt style="color:#6b7280;font-size:13px">Modalidade</dt><dd style="margin:3px 0 0;font-weight:bold">{{ $inscricao['modalidade'] }}</dd></div>
                    @endif
                    <div style="padding:15px 0;border-bottom:1px solid #eee8dc"><dt style="color:#6b7280;font-size:13px">Protocolo</dt><dd style="margin:3px 0 0;font-family:monospace;font-size:16px;font-weight:bold">{{ $inscricao['protocolo'] }}</dd></div>
                    @if ($inscricao['matricula'])
                        <div style="padding:15px 0 0"><dt style="color:#6b7280;font-size:13px">Número de matrícula</dt><dd style="margin:3px 0 0;font-family:monospace;font-size:16px;font-weight:bold">{{ $inscricao['matricula'] }}</dd></div>
                    @endif
                </dl>

                <p style="margin:28px 0 0;color:#6b7280;font-size:13px">Este acesso é pessoal e temporário. Para sua segurança, não compartilhe este link.</p>
                <p style="margin:20px 0 0;color:#6b7280;font-size:13px">Secretaria CETSADP</p>
            </div>
        </section>
    </main>
</body>
</html>
