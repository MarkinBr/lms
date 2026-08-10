<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #00BFA5; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; text-align: center; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
        .qrcode { background: white; padding: 20px; border-radius: 10px; display: inline-block; margin: 20px 0; }
        .pix-code { background: #e0f2f1; padding: 15px; border-radius: 5px; word-break: break-all; font-size: 11px; margin: 20px 0; text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📱 PIX Gerado com Sucesso!</h2>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $aluno }}</strong>!</p>
            
            <p>Seu código PIX foi gerado com sucesso!</p>
            
            <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <p><strong>💰 Valor:</strong> <span style="font-size: 24px; color: #00BFA5;">{{ $mensalidade->valor_formatado }}</span></p>
                <p><strong>📅 Vencimento:</strong> {{ $mensalidade->data_vencimento->format('d/m/Y') }}</p>
            </div>
            
            @if($mensalidade->pix_qr_base64)
                <div class="qrcode">
                    <p><strong>Escaneie o QR Code:</strong></p>
                    <p><em>(QR Code anexado no email)</em></p>
                </div>
            @endif
            
            <p><strong>Ou copie o código PIX:</strong></p>
            <div class="pix-code">
                {{ $mensalidade->pix_copia_cola }}
            </div>
            
            <p style="font-size: 12px; color: #666;">
                ✅ O pagamento é confirmado automaticamente após a compensação!
            </p>
        </div>
        
        <div class="footer">
            <p>Em caso de dúvidas, entre em contato conosco.</p>
            <p>&copy; {{ date('Y') }} - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>