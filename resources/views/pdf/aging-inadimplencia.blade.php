<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><title>Inadimplência por Aging</title>
<style>
  body{font-family:DejaVu Sans,sans-serif;font-size:10px;padding:20px;color:#1e293b}
  .header{text-align:center;border-bottom:2px solid #003D7A;padding-bottom:15px;margin-bottom:20px}
  .header h1{color:#003D7A;font-size:18px;margin:0}
  .header p{margin:2px 0;font-size:10px;color:#64748b}
  .resumo{display:flex;gap:8px;margin:15px 0}
  .bucket{flex:1;padding:12px;border-radius:6px;text-align:center;color:#fff}
  .b1{background:#facc15;color:#713f12}
  .b2{background:#fb923c}
  .b3{background:#ef4444}
  .b4{background:#7f1d1d}
  .bucket .label{font-size:9px;text-transform:uppercase;letter-spacing:1px}
  .bucket .qtd{font-size:18px;font-weight:bold;margin:4px 0}
  .bucket .val{font-size:11px}
  .total-geral{background:#f1f5f9;padding:14px;text-align:right;border-radius:6px;margin:10px 0;font-weight:bold;color:#dc2626;font-size:14px}
  h2{color:#003D7A;font-size:13px;margin:20px 0 8px;padding-bottom:4px;border-bottom:1px solid #e2e8f0}
  table{width:100%;border-collapse:collapse;margin:6px 0;font-size:9px}
  th{background:#f1f5f9;padding:6px;text-align:left;font-size:9px;color:#475569}
  td{padding:5px 6px;border-bottom:1px solid #f1f5f9}
  .num{text-align:right}
  .footer{margin-top:30px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:8px;color:#94a3b8;text-align:center}
</style></head>
<body>
<div class="header">
  <h1>RELATÓRIO DE INADIMPLÊNCIA — AGING</h1>
  <p>{{ $instituicao::nomeCompleto() ?? 'CETSADP' }}</p>
  <p>Emitido em: {{ $gerado_em->format('d/m/Y H:i') }} · Por: {{ auth()->user()->name ?? 'Sistema' }}</p>
</div>
<div class="resumo">
  @php $cls = ['b1','b2','b3','b4']; $i = 0; @endphp
  @foreach($buckets as $key => $b)
    <div class="bucket {{ $cls[$i++] ?? 'b1' }}">
      <div class="label">{{ $b['label'] }}</div>
      <div class="qtd">{{ $b['qtd'] }}</div>
      <div class="val">R$ {{ number_format($b['valor_total'], 2, ',', '.') }}</div>
    </div>
  @endforeach
</div>
<div class="total-geral">
  TOTAL EM ABERTO: R$ {{ number_format($totais['valor_total'], 2, ',', '.') }}
  ({{ $totais['qtd_total'] }} mensalidades · {{ collect($buckets)->flatMap(fn($b)=>$b['agrupado_por_aluno'])->unique('aluno')->count() }} alunos)
</div>
@foreach($buckets as $key => $b)
  @if($b['qtd'] > 0)
    <h2>{{ $b['label'] }} — {{ $b['qtd'] }} mensalidade(s) · R$ {{ number_format($b['valor_total'], 2, ',', '.') }}</h2>
    <table>
      <thead>
        <tr><th>Aluno</th><th>Curso</th><th class="num">Qtd</th><th class="num">Valor (c/ multa)</th><th>Mais antiga</th></tr>
      </thead>
      <tbody>
        @foreach($b['agrupado_por_aluno'] as $linha)
          <tr>
            <td>{{ $linha['aluno'] }}</td>
            <td>{{ $linha['curso'] }}</td>
            <td class="num">{{ $linha['qtd'] }}</td>
            <td class="num">R$ {{ number_format($linha['valor'], 2, ',', '.') }}</td>
            <td>{{ \Carbon\Carbon::parse($linha['mais_antiga'])->format('d/m/Y') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
@endforeach
<div class="footer">
  Documento gerado automaticamente · Confidencial — uso interno
</div>
</body></html>