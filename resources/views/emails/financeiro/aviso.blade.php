<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $assunto }}</title></head>
<body style="margin:0;padding:0;background:#f2f5f8;font-family:Arial,sans-serif;color:#233d56">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2f5f8"><tr><td align="center" style="padding:32px 12px">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:white;border:1px solid #dce5eb;border-radius:10px">
<tr><td style="background:#0b2d55;border-bottom:5px solid #bc8b41;padding:27px 30px"><strong style="font-size:24px;color:#e9ca8b;letter-spacing:1px">CETSADP</strong>
<div style="font-size:12px;color:#bcd1e2;margin-top:5px">Centro Educacional, Teológico e Social · Secretaria Acadêmica</div></td></tr>
<tr><td style="padding:30px"><h1 style="font-size:23px;color:#0e3157;margin:0 0 16px">{{ $titulo }}</h1>
<p style="line-height:1.7">Olá, {{ $aluno }}!</p>
<p style="line-height:1.7">{{ $mensagem }}</p>
<table role="presentation" width="100%" style="background:#f5f8fb;border-left:4px solid #bc8b41;margin:21px 0" cellspacing="0" cellpadding="10">
<tr><td style="font-size:13px">Curso<br><strong>{{ $curso }}</strong></td></tr>
<tr><td style="font-size:13px">Matrícula acadêmica<br><strong>{{ $matricula }}</strong></td></tr>
<tr><td style="font-size:13px">Vencimento: <strong>{{ $vencimento }}</strong> · Valor de referência: <strong>R$ {{ number_format($valor,2,',','.') }}</strong></td></tr>
</table>
@if($link)
<p style="text-align:center;margin:28px 0"><a href="{{ $link }}" style="background:#0d355e;color:#fff;text-decoration:none;padding:13px 22px;border-radius:7px;font-weight:bold">{{ $cta }}</a></p>
@endif
<p style="line-height:1.7">Caso já tenha efetuado o pagamento, desconsidere a cobrança e entre em contato para conferência.</p>
<p style="line-height:1.7">Atenciosamente,<br><strong>Secretaria Acadêmica — CETSADP</strong></p>
</td></tr><tr><td style="padding:17px 29px;background:#f9fbfd;font-size:11px;color:#718597;line-height:1.6">
Mensagem individual do CETSADP. Não responda com CPF completo, senhas ou dados bancários. Consulte a Secretaria pelos canais oficiais.</td></tr>
</table></td></tr></table></body></html>
