<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Selecionadas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; padding: 20px; }
        
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #003D7A; padding-bottom: 15px; }
        .header h1 { color: #003D7A; font-size: 20px; }
        .header p { font-size: 10px; color: #666; }
        
        .totais-box { display: flex; justify-content: space-around; margin: 20px 0; gap: 10px; }
        .total-card { flex: 1; padding: 15px; text-align: center; border-radius: 5px; }
        .total-card.success { background-color: #e8f5e9; border: 2px solid #4caf50; }
        .total-card.warning { background-color: #fff3e0; border: 2px solid #ff9800; }
        .total-card.danger { background-color: #ffebee; border: 2px solid #f44336; }
        .total-card.info { background-color: #e3f2fd; border: 2px solid #2196f3; }
        .total-card h3 { font-size: 12px; margin-bottom: 5px; }
        .total-card .valor { font-size: 16px; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background-color: #003D7A; color: white; padding: 8px; text-align: left; font-size: 9px; }
        table td { padding: 6px; border-bottom: 1px solid #ddd; font-size: 9px; }
        table tr:nth-child(even) { background-color: #f9f9f9; }
        
        .status-badge { padding: 3px 8px; border-radius: 3px; font-size: 8px; font-weight: bold; display: inline-block; }
        .status-pago { background-color: #4caf50; color: white; }
        .status-pendente { background-color: #ff9800; color: white; }
        .status-atrasado { background-color: #f44336; color: white; }
        .status-cancelado { background-color: #9e9e9e; color: white; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RELATÓRIO DE ITENS SELECIONADOS</h1>
        <p>Listagem personalizada de mensalidades</p>
        <p>{{ config('app.name', 'CETSADP') }} | Emitido em: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
    
    <div class="totais-box">
        <div class="total-card success">
            <h3>💰 Recebido</h3>
            <div class="valor">R$ {{ number_format($totais['pago'], 2, ',', '.') }}</div>
        </div>
        <div class="total-card warning">
            <h3>⏳ Pendente</h3>
            <div class="valor">R$ {{ number_format($totais['pendente'], 2, ',', '.') }}</div>
        </div>
        <div class="total-card danger">
            <h3>🔴 Atrasado</h3>
            <div class="valor">R$ {{ number_format($totais['atrasado'], 2, ',', '.') }}</div>
        </div>
        <div class="total-card info">
            <h3>📊 Total Selecionado</h3>
            <div class="valor">R$ {{ number_format($totais['total'], 2, ',', '.') }}</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Vencimento</th>
                <th>Matrícula</th>
                <th>Aluno</th>
                <th>Curso</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Pagamento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mensalidades as $m)
            <tr>
                <td>{{ $m->data_vencimento->format('d/m/Y') }}</td>
                <td>{{ $m->matricula->numero_matricula }}</td>
                <td>{{ $m->matricula->user->name }}</td>
                <td>{{ Str::limit($m->matricula->curso->titulo, 25) }}</td>
                <td>R$ {{ number_format($m->valor, 2, ',', '.') }}</td>
                <td><span class="status-badge status-{{ strtolower($m->status) }}">{{ $m->status }}</span></td>
                <td>{{ $m->data_pagamento ? $m->data_pagamento->format('d/m/Y') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Relatório gerado a partir de seleção manual no sistema.</p>
    </div>
</body>
</html>