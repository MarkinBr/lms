<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0;padding:0;background-color:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#243b53;">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:680px;margin:0 auto;background-color:#ffffff;border:1px solid #d9e2ec;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:0;background-color:#102a43;">
                        <div style="height:6px;background-color:#c69a42;font-size:0;line-height:0;">&nbsp;</div>
                        <div style="padding:28px 32px 24px 32px;">
                            <p style="margin:0 0 6px 0;font-size:12px;line-height:18px;letter-spacing:1.2px;text-transform:uppercase;color:#f0b429;font-weight:bold;">Novo contato recebido</p>
                            <h1 style="margin:0;font-size:26px;line-height:34px;color:#ffffff;font-weight:bold;">CETSADP</h1>
                            <p style="margin:8px 0 0 0;font-size:15px;line-height:24px;color:#d9e2ec;">Centro Educacional, Teológico e Social</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 24px 0;font-size:16px;line-height:26px;color:#334e68;">Você recebeu uma nova mensagem pelo formulário <strong>Fale Conosco</strong>.</p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#f8fbff;border:1px solid #d9e2ec;border-radius:10px;">
                            <tr>
                                <td colspan="2" style="padding:18px 22px;border-bottom:1px solid #d9e2ec;background-color:#eef4f8;">
                                    <p style="margin:0;font-size:14px;line-height:20px;font-weight:bold;color:#102a43;">Dados do visitante</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="width:140px;padding:16px 22px;border-bottom:1px solid #d9e2ec;font-size:14px;line-height:20px;font-weight:bold;color:#486581;vertical-align:top;">Nome</td>
                                <td style="padding:16px 22px;border-bottom:1px solid #d9e2ec;font-size:15px;line-height:22px;color:#102a43;vertical-align:top;">{{ $dados['nome'] }}</td>
                            </tr>
                            <tr>
                                <td style="width:140px;padding:16px 22px;border-bottom:1px solid #d9e2ec;font-size:14px;line-height:20px;font-weight:bold;color:#486581;vertical-align:top;">E-mail</td>
                                <td style="padding:16px 22px;border-bottom:1px solid #d9e2ec;font-size:15px;line-height:22px;color:#102a43;vertical-align:top;">{{ $dados['email'] }}</td>
                            </tr>
                            <tr>
                                <td style="width:140px;padding:16px 22px;border-bottom:1px solid #d9e2ec;font-size:14px;line-height:20px;font-weight:bold;color:#486581;vertical-align:top;">Telefone</td>
                                <td style="padding:16px 22px;border-bottom:1px solid #d9e2ec;font-size:15px;line-height:22px;color:#102a43;vertical-align:top;">{{ $dados['telefone'] ?: 'Não informado' }}</td>
                            </tr>
                            <tr>
                                <td style="width:140px;padding:16px 22px;font-size:14px;line-height:20px;font-weight:bold;color:#486581;vertical-align:top;">Assunto</td>
                                <td style="padding:16px 22px;font-size:15px;line-height:22px;color:#102a43;vertical-align:top;">{{ $dados['assunto'] }}</td>
                            </tr>
                        </table>

                        <div style="margin-top:24px;padding:22px;border-left:4px solid #c69a42;background-color:#fffaf1;border-radius:8px;">
                            <p style="margin:0 0 12px 0;font-size:14px;line-height:20px;font-weight:bold;color:#8d6e1f;">Mensagem</p>
                            <div style="margin:0;font-size:15px;line-height:24px;color:#243b53;word-break:break-word;">{!! nl2br(e($dados['mensagem'])) !!}</div>
                        </div>

                        <div style="margin-top:28px;">
                            <a href="mailto:{{ $dados['email'] }}?subject={{ rawurlencode('Re: '.$dados['assunto']) }}" style="display:inline-block;padding:14px 22px;background-color:#102a43;border-radius:8px;color:#ffffff;font-size:14px;line-height:20px;font-weight:bold;text-decoration:none;">Responder ao visitante</a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 32px 28px 32px;background-color:#f8fbff;border-top:1px solid #d9e2ec;">
                        <p style="margin:0;font-size:12px;line-height:20px;color:#7b8794;">Este e-mail foi gerado automaticamente pelo formulário de contato do site CETSADP.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
