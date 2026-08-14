@php
    $ehMatricula = $tipo === \App\Mail\InscricaoDecisaoMail::TIPO_MATRICULA;
    $corDestaque = $ehMatricula ? '#2f7a4d' : '#a3392f';
    $titulo = $ehMatricula ? 'Matrícula confirmada' : 'Resultado da sua inscrição';
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
</head>
<body style="margin:0;background:#f4f1ea;color:#24364b;font-family:Arial,sans-serif;line-height:1.5">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f1ea;padding:24px 12px">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#fff;border:1px solid #e4ddd0;border-radius:12px;overflow:hidden">
                    <tr>
                        <td style="background:#102a43;color:#fff;padding:30px">
                            <div style="color:#d5af62;font-size:12px;font-weight:bold;letter-spacing:2px">CETSADP</div>
                            <h1 style="margin:8px 0 0;font-family:Georgia,serif;font-size:28px">{{ $titulo }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px">
                            <p>Olá, {{ $nomeCandidato }}.</p>

                            @if ($ehMatricula)
                                <p>Sua inscrição foi aprovada pela Secretaria e a matrícula no CETSADP foi confirmada.</p>
                            @else
                                <p>Analisamos sua inscrição e, neste momento, ela não foi aprovada pela Secretaria do CETSADP.</p>
                            @endif

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:22px 0;border:1px solid #e7dfd0;border-left:4px solid {{ $corDestaque }};background:#fcfaf6;border-radius:8px">
                                <tr>
                                    <td style="padding:20px">
                                        <strong style="display:block;font-size:17px">{{ $curso }}</strong>
                                        @if ($modalidade !== '')
                                            <span style="display:block;margin-top:4px;color:#5e6977">Modalidade: {{ $modalidade }}</span>
                                        @endif
                                        <span style="display:block;margin-top:16px;color:#6b7280;font-size:11px;font-weight:bold;letter-spacing:.7px">PROTOCOLO</span>
                                        <span style="display:block;color:#102a43;font-family:monospace;font-size:18px;font-weight:bold">{{ $protocolo }}</span>
                                        @if ($ehMatricula && $numeroMatricula)
                                            <span style="display:block;margin-top:14px;color:#6b7280;font-size:11px;font-weight:bold;letter-spacing:.7px">NÚMERO DE MATRÍCULA</span>
                                            <span style="display:block;color:#102a43;font-family:monospace;font-size:18px;font-weight:bold">{{ $numeroMatricula }}</span>
                                        @endif
                                        <span style="display:block;margin-top:14px;color:#6b7280;font-size:11px;font-weight:bold;letter-spacing:.7px">SITUAÇÃO</span>
                                        <span style="display:block;color:#102a43;font-weight:bold">{{ $situacaoLabel }}</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 10px;color:#5e6977;font-size:14px">Acompanhe sua inscrição diretamente pelo link seguro abaixo:</p>
                            <p style="margin:0 0 24px"><a href="{{ $linkAcompanhamento }}" style="display:inline-block;padding:12px 18px;background:#102a43;border-radius:5px;color:#fff;font-size:13px;font-weight:bold;letter-spacing:.4px;text-decoration:none">ACOMPANHAR INSCRIÇÃO</a></p>

                            @if (! $ehMatricula)
                                <div style="margin:20px 0;padding:16px 18px;border:1px solid #e7d1cd;border-radius:8px;background:#fdf5f4">
                                    <strong style="display:block;color:#6b7280;font-size:11px;letter-spacing:.7px">JUSTIFICATIVA DA SECRETARIA</strong>
                                    <p style="margin:8px 0 0;color:#3b1f1c;white-space:pre-line">{{ $motivoRejeicao }}</p>
                                </div>
                            @endif

                            <p style="color:#5e6977;font-size:14px">O link é pessoal e válido por 30 dias. Não o compartilhe com terceiros.</p>
                            <p style="margin-bottom:0">Atenciosamente,<br><strong>Secretaria CETSADP</strong></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
