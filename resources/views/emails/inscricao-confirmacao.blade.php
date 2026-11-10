<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmação de inscrição — CETSADP</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f1ea;font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f1ea;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border:1px solid #e5e1d8;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#0f2a43;padding:28px 32px;">
                            <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#d9c79a;">CETSADP</p>
                            <h1 style="margin:8px 0 0;font-size:22px;font-weight:bold;color:#ffffff;">Inscrição recebida</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;">Olá, <strong>{{ $nomeCandidato }}</strong>.</p>

                            @if (count($inscricoes) === 1)
                                <p style="margin:0 0 24px;">
                                    Confirmamos o recebimento da sua inscrição no CETSADP.
                                    Ela está com a situação <strong>Em análise</strong> pela nossa Secretaria.
                                    Guarde o protocolo abaixo.
                                </p>
                            @else
                                <p style="margin:0 0 24px;">
                                    Confirmamos o recebimento das suas inscrições no CETSADP.
                                    Todas estão com a situação <strong>Em análise</strong> pela nossa Secretaria.
                                    Guarde os protocolos abaixo.
                                </p>
                            @endif

                            @foreach ($inscricoes as $inscricao)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;background-color:#faf8f3;border-left:4px solid #b8892d;border-radius:4px;">
                                    <tr>
                                        <td style="padding:16px 20px;">
                                            <p style="margin:0;font-size:16px;font-weight:bold;color:#0f2a43;">{{ $inscricao['curso'] }}</p>
                                            <p style="margin:4px 0 0;font-size:14px;color:#4b5563;">Modalidade: {{ $inscricao['modalidade'] }}</p>
                                            <p style="margin:12px 0 0;font-size:13px;color:#6b7280;">
                                                Protocolo:
                                                <span style="display:inline-block;font-size:17px;font-weight:bold;letter-spacing:0.5px;color:#0f2a43;">{{ $inscricao['protocol'] }}</span>
                                            </p>
                                            <p style="margin:8px 0 0;font-size:13px;color:#6b7280;">
                                                Situação: <strong style="color:#92610f;">Em análise</strong>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach

                            <p style="margin:24px 0 8px;">
                                Para acompanhar a sua inscrição, acesse o site do CETSADP, abra a página
                                <strong>“Acompanhar inscrição”</strong> e informe o protocolo acima junto com
                                <strong>o mesmo e-mail utilizado neste cadastro</strong>.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:16px 0 24px;">
                                <tr>
                                    <td align="center" style="background-color:#0f2a43;border-radius:6px;">
                                        <a href="{{ url('/') }}" style="display:inline-block;padding:12px 28px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;">
                                            Acessar o site do CETSADP
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;color:#4b5563;">
                                Este é um e-mail automático de confirmação. Em caso de dúvidas,
                                responda a esta mensagem ou procure a Secretaria CETSADP.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f4f1ea;padding:20px 32px;border-top:1px solid #e5e1d8;">
                            <p style="margin:0;font-size:13px;color:#6b7280;">
                                Atenciosamente,<br>
                                <strong>Secretaria CETSADP</strong><br>
                                Centro Educacional, Teológico e Social da Igreja Evangélica Assembleia de Deus em Paracambi
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
