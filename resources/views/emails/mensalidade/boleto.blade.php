<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #FF9800; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
        .button { background: #FF9800; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px; }
        .codigo { background: #fff3e0; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 11px; word-break: break-all; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📄 Boleto Bancário Gerado</h2>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $aluno }}</strong>!</p>
            
            <p>Seu boleto bancário foi gerado com sucesso!</p>
            
            <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <p><strong>💰 Valor:</strong> <span style="font-size: 24px; color: #FF9800;">{{ $mensalidade->valor_formatado }}</span></p>
                <p><strong>📅 Vencimento:</strong> {{ $mensalidade->data_vencimento->format('d/m/Y') }}</p>
            </div>
            
            @if($mensalidade->link_boleto)
                <p style="text-align: center;">
                    <a href="{{ $mensalidade->link_boleto }}" class="button">📄 Baixar Boleto PDF</a>
                </p>
            @endif
            
            @if($mensalidade->linha_digitavel)
                <p><strong>Linha Digitável:</strong></p>
                <div class="codigo">
                    {{ $mensalidade->linha_digitavel }}
                </div>
            @endif
            
            <p style="font-size: 12px; color: #666;">
                💡 Dica: Você pode pagar o boleto pelo aplicativo do seu banco!
            </p>
        </div>
        
        <div class="footer">
            <p>Em caso de dúvidas, entre em contato conosco.</p>
            <p>&copy; {{ date('Y') }} - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>