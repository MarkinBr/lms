@php
    $ehMatricula = $tipo === \App\Mail\InscricaoDecisaoMail::TIPO_MATRICULA;
    $titulo = $ehMatricula ? 'Matrícula confirmada' : 'Resultado da sua inscrição';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $titulo }}</title></head>
<body style="margin:0;background:#f4f1ea;color:#24364b;font-family:Arial,sans-serif;line-height:1.5">
<main style="max-width:640px;margin:32px auto;background:#fff;border:1px solid #e4ddd0;border-radius:12px;overflow:hidden">
    <header style="background:#102a43;color:#fff;padding:32px"><p style="margin:0;color:#d5af62;font-size:12px;font-weight:bold;letter-spacing:2px">CENTRO EDUCACIONAL, TEOLÓGICO E SOCIAL</p><h1 style="margin:8px 0 0;font-family:Georgia,serif;font-size:29px">{{ $titulo }}</h1></header>
    <section style="padding:32px">
        <p>Olá, {{ $inscricao->nome_completo }}.</p>
        @if ($ehMatricula)
            <p>Temos boas notícias: sua inscrição foi aprovada e sua <strong>matrícula foi confirmada</strong> pela Secretaria do CETSADP.</p>
        @else
            <p>Analisamos sua inscrição e, neste momento, ela <strong>não foi aprovada</strong>.</p>
        @endif
        <article style="margin:20px 0;padding:20px;border:1px solid #e7dfd0;border-left:4px solid {{ $ehMatricula ? '#2f7a4d' : '#a3392f' }};border-radius:8px;background:#fcfaf6">
            <strong style="display:block;font-size:17px">{{ $inscricao->curso->titulo }}</strong>
            @if ($inscricao->courseModality)<span style="display:block;margin-top:3px;color:#5e6977">Modalidade: {{ $inscricao->courseModality->nome }}</span>@endif
            <span style="display:block;margin-top:14px;color:#6b7280;font-size:12px;font-weight:bold">PROTOCOLO</span><span style="display:block;color:#102a43;font-family:monospace;font-size:18px;font-weight:bold">{{ $inscricao->protocol }}</span>
            @if ($ehMatricula)<span style="display:block;margin-top:14px;color:#6b7280;font-size:12px;font-weight:bold">NÚMERO DE MATRÍCULA</span><span style="display:block;color:#102a43;font-family:monospace;font-size:18px;font-weight:bold">{{ $numeroMatricula }}</span>@endif
            <a href="{{ $linkAcompanhamento }}" style="display:inline-block;margin-top:18px;padding:12px 18px;background:#102a43;border-radius:5px;color:#fff;font-size:13px;font-weight:bold;text-decoration:none">ACOMPANHAR INSCRIÇÃO</a>
        </article>
        @if (! $ehMatricula)<div style="padding:18px 20px;border:1px solid #e7d1cd;border-radius:8px;background:#fdf5f4"><strong style="font-size:12px;color:#6b7280">JUSTIFICATIVA DA SECRETARIA</strong><p style="margin:8px 0 0;white-space:pre-line">{{ $motivoRejeicao }}</p></div>@endif
        <p style="color:#5e6977;font-size:14px">O link é pessoal e válido por 30 dias. Guarde também o protocolo.</p><p style="margin-bottom:0">Atenciosamente,<br><strong>Secretaria CETSADP</strong></p>
    </section>
</main>
</body>
</html>
