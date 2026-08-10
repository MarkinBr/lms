<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Escolar - {{ $matricula->numero_matricula ?? $matricula->id }}</title>
    <style>
        @page {
            margin: 0cm 2cm;
            background: #ffffff;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            color: #2c3e50;
            line-height: 1.6;
            position: relative;
            padding: 20px 40px;
            margin: 0;
        }
        
        /* Marca d'água */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-360deg);
            opacity: 0.05;
            z-index: -1;
            pointer-events: none;
        }
        
        .watermark img {
            width: 1100px;
            height: auto;
        }
        
        /* Cabeçalho */
        .header {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #003D7A;
            min-height: 80px;
        }
        
        .logo {
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: auto;
        }
        
        .institution-name {
            font-size: 20px;
            font-weight: bold;
            color: #003D7A;
            margin: 10px 0;
            text-transform: uppercase;
            padding-right: 80px;
        }
        
        .institution-subtitle {
            font-size: 14px;
            color: #666;
            font-style: italic;
            padding-right: 80px;
        }
        
        /* Título */
        .title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #003D7A;
            margin: 20px 0 20px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        /* Informações do Documento */
        .document-number {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Caixa de informações do aluno */
        .info-box {
            background: #f8f9fa;
            border: 2px solid #003D7A;
            border-radius: 8px;
            padding: 15px 25px;
            margin: 20px 0;
            font-size: 13px;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin: 8px 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #003D7A;
            display: table-cell;
            width: 160px;
        }
        
        .info-value {
            display: table-cell;
            color: #333;
        }

        /* Estilos da Tabela de Notas (Novo) */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .grades-table th {
            background-color: #003D7A;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px;
            text-align: left;
            border: 1px solid #003D7A;
        }

        .grades-table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            color: #333;
        }

        .grades-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .grades-table .col-center {
            text-align: center;
        }

        .grades-table .col-right {
            text-align: right;
        }

        .status-aprovado { color: #28a745; font-weight: bold; }
        .status-reprovado { color: #dc3545; font-weight: bold; }
        .status-cursando { color: #007bff; font-weight: bold; }

        /* Rodapé da tabela (Totais) */
        .table-footer {
            background-color: #003D7A;
            color: white;
            font-weight: bold;
        }
        
        /* Assinatura */
        .signature-section {
            margin-top: 30px;
            text-align: center;
            page-break-inside: avoid; /* Evita quebrar a assinatura no meio */
        }
        
        .signature-line {
            border-top: 2px solid #333;
            width: 300px;
            margin: 40px auto 10px auto;
        }
        
        .signature-name {
            font-weight: bold;
            margin-top: 5px;
        }
        
        .signature-title {
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        
        /* Rodapé */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            background: white;
            margin: 0 40px; /* Alinha com o padding do body */
        }
        
        /* Selo de autenticidade (QR Code) */
        .authenticity-seal {
            position: absolute;
            bottom: 60px;
            right: 0px;
            width: 120px;
            text-align: center;
        }
        
        .qr-code-container {
            background: white;
            border: 3px solid #003D7A;
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .qr-code-container img {
            width: 90px;
            height: 90px;
            display: block;
            margin: 0 auto;
        }
        
        .qr-code-label {
            font-size: 8px;
            color: #003D7A;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .qr-code-code {
            font-size: 7px;
            color: #666;
            font-family: 'Courier New', monospace;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="watermark">
        @if(file_exists(public_path('assets/images/logo.png')))
            <img src="{{ public_path('assets/images/logo.png') }}" alt="Logo Marca d'água">
        @endif
    </div>
    
    <div class="header">
        @if(file_exists(public_path('assets/images/logo.png')))
            <img src="{{ public_path('assets/images/logo.png') }}" alt="Logo" class="logo">
        @endif
        <div class="institution-name">CETSADP</div>
        <div class="institution-subtitle">Centro Educacional, Teológico e Social da Assembleia de Deus de Paracambi</div>
    </div>
    
    <div class="document-number">
        Registro Acadêmico: {{ $matricula->numero_matricula ?? $matricula->id }}<br>
        Emitido em: {{ now()->format('d/m/Y H:i') }}
    </div>
    
    <div class="title">
        Histórico Escolar
    </div>
    
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Nome do Aluno(a):</span>
            <span class="info-value">{{ $matricula->user->name ?? $aluno->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">CPF:</span>
            <span class="info-value">{{ $matricula->user->cpf ?? $aluno->cpf }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Curso:</span>
            <span class="info-value">{{ $matricula->turma->curso->nome ?? $matricula->curso->titulo }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Turma / Período:</span>
            <span class="info-value">{{ $matricula->turma->nome ?? 'N/A' }} ({{ $matricula->turma->ano_letivo ?? date('Y') }})</span>
        </div>
        <div class="info-row">
            <span class="info-label">Data de Início:</span>
            <span class="info-value">
                {{ $matricula->data_matricula ? \Carbon\Carbon::parse($matricula->data_matricula)->format('d/m/Y') : 'N/A' }}
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Situação Final:</span>
            <span class="info-value" style="text-transform: uppercase; font-weight: bold;">
                {{ $matricula->status }}
            </span>
        </div>
    </div>
    
    <table class="grades-table">
        <thead>
            <tr>
                <th style="width: 50%">Componente Curricular / Módulo</th>
                <th style="width: 15%" class="col-center">Carga H.</th>
                <th style="width: 15%" class="col-center">Média</th>
                <th style="width: 20%" class="col-center">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @php $totalHoras = 0; @endphp
            @forelse($matricula->turma->curso->modulos as $modulo)
            <tr>
                <td>{{ $modulo->titulo ?? 'Módulo' }}</td>
                <td class="col-center">
                    {{-- Cálculo fictício de horas baseada em aulas, ajuste se tiver campo específico --}}
                    @php 
                        $horasModulo = ($modulo->aulas->count() * 2) ?: 10; 
                        $totalHoras += $horasModulo;
                    @endphp
                    {{ $horasModulo }}h
                </td>
                <td class="col-center">
                    {{-- Aqui você pode integrar com a lógica real de notas se tiver --}}
                    -
                </td>
                <td class="col-center">
                    <span class="status-aprovado">APROVADO</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="col-center">Nenhum módulo registrado para este curso.</td>
            </tr>
            @endforelse
            
            <tr class="table-footer">
                <td style="text-align: right; padding-right: 20px;">CARGA HORÁRIA TOTAL</td>
                <td class="col-center">{{ $totalHoras }}h</td>
                <td colspan="2" style="background-color: #f8f9fa;"></td>
            </tr>
        </tbody>
    </table>
    
    <div style="font-size: 11px; text-align: justify; margin-top: 20px; color: #555;">
        <p><strong>Observações:</strong></p>
        <p>
            O sistema de avaliação considera a escala de 0 a 10,0 pontos. A média mínima para aprovação é 7,0.
            Este documento reflete a situação acadêmica do aluno na data de sua emissão e não substitui o Diploma de Conclusão de Curso para fins de registro profissional, quando aplicável.
        </p>
    </div>
    
    <div class="signature-section">
        <div class="signature-line"></div>
        <div class="signature-name">Secretaria Acadêmica</div>
        <div class="signature-title">CETSADP</div>
        <div style="margin-top: 5px; font-size: 11px; color: #666;">
            Paracambi - RJ, {{ now()->translatedFormat('d \d\e F \d\e Y') }}
        </div>
    </div>
    
    @if(isset($qrCode) && isset($codigoValidacao))
    <div class="authenticity-seal">
        <div class="qr-code-container">
            <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code">
        </div>
        <div class="qr-code-label">Validar</div>
        <div class="qr-code-code">{{ $codigoValidacao }}</div>
    </div>
    @endif
    
    <div class="footer">
        Rua Afonso Franco, Nº15, Centro - Paracambi - RJ | (21) 1234-5678<br>
        www.cetsadp.com.br | contato@cetsadp.com.br
    </div>
</body>
</html>