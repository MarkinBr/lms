{{-- resources/views/relatorios/financeiro-mensal.blade.php --}}

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro Mensal</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
            font-size: 11px; 
            line-height: 1.6; 
            color: #1a1a1a;
            background: #ffffff;
            -webkit-font-smoothing: antialiased;
        }
        
        .header { 
            margin-bottom: 50px; 
            padding: 40px 0 30px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        
        .logo-section h1 { 
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        
        .logo-section p {
            font-size: 9px;
            color: #666;
            font-weight: 400;
            letter-spacing: 0.5px;
        }
        
        .report-meta {
            text-align: right;
            font-size: 9px;
            color: #666;
            line-height: 1.8;
        }
        
        .report-meta strong {
            color: #1a1a1a;
            font-weight: 500;
        }
        
        .report-title {
            margin-top: 25px;
        }
        
        .report-title h2 {
            font-size: 28px;
            font-weight: 300;
            color: #1a1a1a;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }
        
        .report-title .subtitle {
            font-size: 13px;
            color: #666;
            font-weight: 400;
        }
        
        .executive-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e8e8e8;
            padding: 35px;
            margin-bottom: 45px;
            border-radius: 2px;
        }
        
        .executive-summary h3 {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 25px;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
        
        .metric-card {
            background: white;
            border: 1px solid #e8e8e8;
            padding: 25px;
            border-radius: 2px;
            position: relative;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
        }
        
        .metric-card.positive::before { background: #059669; }
        .metric-card.negative::before { background: #dc2626; }
        .metric-card.neutral::before { background: #2563eb; }
        
        .metric-label {
            font-size: 9px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 12px;
        }
        
        .metric-value {
            font-size: 26px;
            font-weight: 300;
            letter-spacing: -0.5px;
            color: #1a1a1a;
        }
        
        .metric-card.positive .metric-value { color: #059669; }
        .metric-card.negative .metric-value { color: #dc2626; }
        .metric-card.neutral .metric-value { color: #2563eb; }
        
        .section {
            margin-bottom: 50px;
        }
        
        .section-header {
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e8e8e8;
        }
        
        .section-header h3 {
            font-size: 16px;
            font-weight: 500;
            color: #1a1a1a;
            letter-spacing: -0.2px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 10px;
        }
        
        table thead {
            background: #fafafa;
            border-top: 1px solid #e8e8e8;
            border-bottom: 1px solid #e8e8e8;
        }
        
        table th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #666;
        }
        
        table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f0f0;
            color: #1a1a1a;
        }
        
        table tbody tr:hover {
            background: #fafafa;
        }
        
        table th:last-child,
        table td:last-child {
            text-align: right;
        }
        
        .total-row {
            background: #fafafa !important;
            border-top: 2px solid #e0e0e0 !important;
            border-bottom: 2px solid #e0e0e0 !important;
        }
        
        .total-row td {
            font-weight: 600;
            padding: 16px;
        }
        
        .value-positive { color: #059669; }
        .value-negative { color: #dc2626; }
        
        .tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .tag.entrada {
            background: #d1fae5;
            color: #065f46;
        }
        
        .tag.saida {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .footer {
            margin-top: 80px;
            padding-top: 30px;
            border-top: 1px solid #e8e8e8;
            text-align: center;
            font-size: 8px;
            color: #999;
            line-height: 2;
        }
        
        .footer strong {
            color: #666;
            font-weight: 500;
        }
        
        .page-break { 
            page-break-after: always; 
        }
        
        @media print {
            body { background: white; }
            .metric-card,
            .executive-summary {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="logo-section">
                <h1>CETSADP</h1>
                <p>Centro Educacional Teológico e Social</p>
            </div>
            <div class="report-meta">
                <div><strong>Documento:</strong> RF-{{ $ano }}-{{ $mes }}</div>
                <div><strong>Gerado:</strong> {{ now()->format('d.m.Y') }} às {{ now()->format('H:i') }}</div>
                <div><strong>Movimentações:</strong> {{ $movimentacoes->count() }}</div>
            </div>
        </div>
        <div class="report-title">
            <h2>Relatório Financeiro Mensal</h2>
            <p class="subtitle">{{ ['01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'][$mes] }} de {{ $ano }}</p>
        </div>
    </div>

    <div class="executive-summary">
        <h3>Resumo Executivo</h3>
        <div class="metrics-grid">
            <div class="metric-card positive">
                <div class="metric-label">Entradas do Período</div>
                <div class="metric-value">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</div>
            </div>
            <div class="metric-card negative">
                <div class="metric-label">Saídas do Período</div>
                <div class="metric-value">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</div>
            </div>
            <div class="metric-card neutral">
                <div class="metric-label">Resultado do Mês</div>
                <div class="metric-value">R$ {{ number_format($saldo, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    @if($entradasPorCategoria->isNotEmpty())
        <div class="section">
            <div class="section-header">
                <h3>Composição de Entradas</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entradasPorCategoria as $categoria => $valor)
                        <tr>
                            <td>{{ $categoria }}</td>
                            <td style="text-align: right;">R$ {{ number_format($valor, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total</td>
                        <td style="text-align: right;">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    @if($saidasPorCategoria->isNotEmpty())
        <div class="section">
            <div class="section-header">
                <h3>Composição de Saídas</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($saidasPorCategoria as $categoria => $valor)
                        <tr>
                            <td>{{ $categoria }}</td>
                            <td style="text-align: right;">R$ {{ number_format($valor, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total</td>
                        <td style="text-align: right;">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <div class="page-break"></div>

    <div class="section">
        <div class="section-header">
            <h3>Posição por Conta Bancária</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Conta</th>
                    <th style="text-align: right;">Saldo Inicial</th>
                    <th style="text-align: right;">Entradas</th>
                    <th style="text-align: right;">Saídas</th>
                    <th style="text-align: right;">Saldo Final</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contas as $conta)
                    <tr>
                        <td>{{ $conta['nome'] }}</td>
                        <td style="text-align: right;">R$ {{ number_format($conta['saldo_inicial'], 2, ',', '.') }}</td>
                        <td style="text-align: right;">R$ {{ number_format($conta['entradas'], 2, ',', '.') }}</td>
                        <td style="text-align: right;">R$ {{ number_format($conta['saidas'], 2, ',', '.') }}</td>
                        <td style="text-align: right; font-weight: 600;">R$ {{ number_format($conta['saldo_final'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-header">
            <h3>Detalhamento de Transações</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Data</th>
                    <th style="width: 8%;">Tipo</th>
                    <th style="width: 34%;">Descrição</th>
                    <th style="width: 18%;">Categoria</th>
                    <th style="width: 15%;">Conta</th>
                    <th style="width: 17%;">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimentacoes as $mov)
                    <tr>
                        <td>{{ $mov->data_movimentacao->format('d.m.Y') }}</td>
                        <td>
                            <span class="tag {{ $mov->tipo }}">
                                {{ $mov->tipo === 'entrada' ? 'Entrada' : 'Saída' }}
                            </span>
                        </td>
                        <td>{{ $mov->descricao }}</td>
                        <td>{{ $mov->categoria->nome ?? '—' }}</td>
                        <td>{{ $mov->conta->nome }}</td>
                        <td style="text-align: right; font-weight: 500;" class="{{ $mov->tipo === 'entrada' ? 'value-positive' : 'value-negative' }}">
                            R$ {{ number_format($mov->valor, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p><strong>CETSADP</strong> — Centro Educacional Teológico e Social</p>
        <p>Documento confidencial gerado automaticamente pelo sistema de gestão financeira</p>
        <p>© {{ now()->year }} Todos os direitos reservados</p>
    </div>
</body>
</html>