<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declaração de Matrícula - {{ $matricula->numero_matricula }}</title>
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
        
        /* Marca d'água com logo */
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
        
        /* Cabeçalho simplificado */
        .header {
            position: relative;
            margin-bottom: 40px;
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
        
        /* Título da declaração */
        .title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #003D7A;
            margin: 40px 0 30px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        /* Número do documento */
        .document-number {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-bottom: 30px;
        }
        
        /* Corpo do texto */
        .content {
            text-align: justify;
            font-size: 14px;
            line-height: 1.8;
            margin: 5px 0;
        }
        
        .content p {
            margin: 0px 0;
            text-indent: 50px;
        }
        
        /* Caixa de informações do aluno */
        .info-box {
            background: #f8f9fa;
            border: 2px solid #003D7A;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin: 12px 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #003D7A;
            display: table-cell;
            width: 180px;
        }
        
        .info-value {
            display: table-cell;
            color: #333;
        }
        
        /* Assinatura */
        .signature-section {
            margin-top: 40px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            width: 300px;
            margin: 60px auto 10px auto;
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
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        /* Selo de autenticidade com QR Code */
        .authenticity-seal {
            position: absolute;
            bottom: 80px;
            right: 0px;
            width: 140px;
            text-align: center;
        }
        
        .qr-code-container {
            background: white;
            border: 3px solid #003D7A;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .qr-code-container img {
            width: 120px;
            height: 120px;
            display: block;
            margin: 0 auto;
        }
        
        .qr-code-label {
            font-size: 9px;
            color: #003D7A;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.3;
        }
        
        .qr-code-code {
            font-size: 8px;
            color: #666;
            font-family: 'Courier New', monospace;
            margin-top: 3px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <!-- Marca d'água com logo -->
    <div class="watermark">
        @if(file_exists(public_path('assets/images/logo.png')))
            <img src="{{ public_path('assets/images/logo.png') }}" alt="Logo Marca d'água">
        @endif
    </div>
    
    <!-- Cabeçalho -->
    <div class="header">
        @if(file_exists(public_path('assets/images/logo.png')))
            <img src="{{ public_path('assets/images/logo.png') }}" alt="Logo" class="logo">
        @endif
        <div class="institution-name">CETSADP</div>
        <div class="institution-subtitle">Centro Educacional, Teológico e Social da Assembleia de Deus de Paracambi</div>
    </div>
    
    <!-- Número do documento -->
    <div class="document-number">
        Documento Nº: {{ $matricula->numero_matricula }}<br>
        Emitido em: {{ now()->format('d/m/Y') }}
    </div>
    
    <!-- Título -->
    <div class="title">
        Declaração de Matrícula
    </div>
    
    <!-- Conteúdo -->
    <div class="content">
        <p>
            Declaramos, para os devidos fins e a quem possa interessar, que 
            <strong>{{ strtoupper($matricula->user->name) }}</strong>, 
            portador(a) do CPF nº <strong>{{ $matricula->user->cpf }}</strong>, 
            encontra-se regularmente matriculado(a) nesta instituição de ensino.
        </p>
    </div>
    
    <!-- Caixa de informações -->
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Número da Matrícula:</span>
            <span class="info-value">{{ $matricula->numero_matricula }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nome do Aluno(a):</span>
            <span class="info-value">{{ $matricula->user->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">CPF:</span>
            <span class="info-value">{{ $matricula->user->cpf }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Curso:</span>
            <span class="info-value">{{ $matricula->curso->titulo }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Turma:</span>
            <span class="info-value">{{ $matricula->turma->nome ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Ano Letivo:</span>
            <span class="info-value">{{ $matricula->turma->ano_letivo ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Data da Matrícula:</span>
            <span class="info-value">{{ $matricula->data_matricula->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Situação:</span>
            <span class="info-value">
                @if($matricula->status === 'ativo')
                    <strong style="color: #28a745;">ATIVO - REGULAR</strong>
                @elseif($matricula->status === 'trancado')
                    <strong style="color: #ffc107;">TRANCADO</strong>
                @elseif($matricula->status === 'concluido')
                    <strong style="color: #17a2b8;">CONCLUÍDO</strong>
                @else
                    <strong>{{ strtoupper($matricula->status) }}</strong>
                @endif
            </span>
        </div>
    </div>
    
    <div class="content">
        <p>
            A presente declaração é expedida para comprovação de vínculo acadêmico junto a esta instituição, 
            podendo ser utilizada para fins legais e administrativos que se fizerem necessários.
        </p>
    </div>
    
    <!-- Assinatura -->
    <div class="signature-section">
        <div class="signature-line"></div>
        <div class="signature-name">Secretaria Acadêmica</div>
        <div class="signature-title">CETSADP</div>
        <div style="margin-top: 5px; font-size: 11px; color: #666;">
            Paracambi - RJ, {{ now()->translatedFormat('d \d\e F \d\e Y') }}
        </div>
    </div>
    
    <!-- Selo de autenticidade com QR Code -->
    <div class="authenticity-seal">
        <div class="qr-code-container">
            <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code de Validação">
        </div>
        <div class="qr-code-label">
            Validar Documento
        </div>
        <div class="qr-code-code">
            {{ $codigoValidacao }}
        </div>
    </div>
    
    <!-- Rodapé -->
    <div class="footer">
        Rua Afonso Franco, Nº15, Centro - Paracambi - RJ | (21) 1234-5678<br>
        www.cetsadp.com.br | contato@cetsadp.com.br<br>
        <em>Valide este documento em: {{ url('/validar') }}/{{ $codigoValidacao }}</em>
    </div>
</body>
</html>