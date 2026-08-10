<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f4f4f4; }
        .recibo { background: #fff; padding: 30px; border: 1px solid #ddd; max-width: 600px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .valor { font-size: 24px; font-weight: bold; color: #28a745; }
        .footer { margin-top: 30px; font-size: 12px; color: #777; text-align: center; }
        .btn-print { display: block; width: 100%; padding: 10px; background: #007bff; color: white; text-align: center; text-decoration: none; margin-top: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="recibo">
        <div class="header">
            <h1>RECIBO DE PAGAMENTO</h1>
            <p>CETS - Sistema Acadêmico</p>
        </div>
        <p>Recebemos de <strong>{{ $mensalidade->matricula->aluno->nome }}</strong></p>
        <p>A quantia de <span class="valor">R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}</span></p>
        <p>Referente à mensalidade com vencimento em <strong>{{ \Carbon\Carbon::parse($mensalidade->data_vencimento)->format('d/m/Y') }}</strong>.</p>
        <p>Curso: {{ $mensalidade->matricula->turma->nome }}</p>
        <br>
        <p>Data do Pagamento: {{ \Carbon\Carbon::parse($mensalidade->data_pagamento)->format('d/m/Y') }}</p>
        
        <div class="footer">
            <p>Deus te abençoe!</p>
            <p>Este comprovante foi gerado eletronicamente.</p>
        </div>
        
        <a href="#" onclick="window.print();return false;" class="btn-print">Imprimir / Salvar PDF</a>
    </div>
</body>
</html>