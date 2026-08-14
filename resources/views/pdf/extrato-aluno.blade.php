<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Extrato acadêmico - {{ $aluno->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #24364b; font-size: 12px; }
        h1 { color: #003d7a; font-size: 20px; margin-bottom: 4px; }
        h2 { color: #003d7a; font-size: 14px; border-bottom: 1px solid #d5af62; padding-bottom: 4px; }
        .muted { color: #667085; }
        .header { border-bottom: 3px solid #003d7a; margin-bottom: 20px; padding-bottom: 12px; }
        .summary { width: 100%; border-collapse: collapse; margin: 14px 0 20px; }
        .summary td { border: 1px solid #d9e0e8; padding: 8px; width: 25%; }
        .label { display: block; color: #667085; font-size: 9px; text-transform: uppercase; }
        .value { display: block; font-size: 15px; font-weight: bold; margin-top: 3px; }
        .course { margin: 8px 0; padding: 8px 10px; background: #f5f7fa; border-left: 3px solid #d5af62; }
        .footer { margin-top: 28px; border-top: 1px solid #d9e0e8; padding-top: 8px; font-size: 10px; }
    </style>
</head>
<body>
    <header class="header">
        <h1>CETSADP</h1>
        <div class="muted">Extrato acadêmico individual</div>
        <div style="margin-top: 10px"><strong>{{ $aluno->name }}</strong></div>
        <div class="muted">Emitido em {{ now()->format('d/m/Y H:i') }}</div>
    </header>

    <h2>Resumo financeiro</h2>
    <table class="summary">
        <tr>
            <td><span class="label">Mensalidades</span><span class="value">{{ $stats['total_mensalidades'] }}</span></td>
            <td><span class="label">Pagas</span><span class="value">{{ $stats['qtd_pagas'] }}</span></td>
            <td><span class="label">Em atraso</span><span class="value">{{ $stats['qtd_atrasadas'] }}</span></td>
            <td><span class="label">Situação</span><span class="value">{{ $stats['situacao'] }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Total pago</span><span class="value">R$ {{ number_format($stats['total_pago'], 2, ',', '.') }}</span></td>
            <td colspan="2"><span class="label">Débito atual</span><span class="value">R$ {{ number_format($stats['debito_atual'], 2, ',', '.') }}</span></td>
        </tr>
    </table>

    <h2>Formações vinculadas</h2>
    @forelse ($aluno->matriculas as $matricula)
        <div class="course">
            <strong>{{ $matricula->curso?->titulo ?? 'Curso não informado' }}</strong><br>
            <span class="muted">Matrícula: {{ $matricula->numero_matricula }} · Status: {{ $matricula->status }}</span>
        </div>
    @empty
        <p class="muted">Nenhuma matrícula encontrada.</p>
    @endforelse

    <footer class="footer">
        Documento emitido pelo CETSADP. Este extrato é destinado ao titular ou à secretaria autorizada.
    </footer>
</body>
</html>
