<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background: #f8f9fa; padding: 10px; text-align: center; border-bottom: 2px solid #eee; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 10px; font-weight: bold; }
        .btn-green { background-color: #28a745; }
        .btn-blue { background-color: #007bff; }
        .btn-red { background-color: #dc3545; }
        .alert { padding: 10px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 5px; margin: 10px 0; }
        .details { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>CETS - Sistema Acadêmico</h2>
    </div>

    <p>Olá, <strong>{{ $mensalidade->matricula->aluno->nome }}</strong>.</p>

    {{-- CASO 1: MENSALIDADE PAGA --}}
    @if($mensalidade->status === 'Pago')
        
        <p>Recebemos o seu pagamento referente ao vencimento <strong>{{ \Carbon\Carbon::parse($mensalidade->data_vencimento)->format('d/m/Y') }}</strong>.</p>
        
        <div class="details">
            <p><strong>Valor Pago:</strong> R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}</p>
            {{-- CORREÇÃO AQUI: Verifica se existe data, senão usa a data de hoje --}}
            <p><strong>Data do Pagamento:</strong> 
                {{ $mensalidade->data_pagamento ? \Carbon\Carbon::parse($mensalidade->data_pagamento)->format('d/m/Y') : date('d/m/Y') }}
            </p>
        </div>

        <p style="text-align: center; font-size: 18px; color: #28a745;">
            <strong>Deus te abençoe! 🙏</strong>
        </p>

        <center>
            <a href="{{ route('recibo.show', $mensalidade->id) }}" class="btn btn-green">Ver Comprovante / Recibo</a>
        </center>


    {{-- CASO 2: MENSALIDADE ATRASADA --}}
    @elseif($mensalidade->status === 'Atrasado')

        <div class="alert">
            <strong>Atenção:</strong> Consta em nosso sistema uma pendência financeira.
        </div>

        <p>A mensalidade com vencimento em <strong>{{ \Carbon\Carbon::parse($mensalidade->data_vencimento)->format('d/m/Y') }}</strong> encontra-se em aberto.</p>
        
        <div class="details">
            <p><strong>Curso:</strong> {{ $mensalidade->matricula->turma->nome }}</p>
            <p><strong>Valor Atualizado:</strong> R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}</p>
        </div>

        <p>Por favor, regularize sua situação clicando abaixo:</p>
        
        <center>
            @if($mensalidade->link_pagamento)
                <a href="{{ $mensalidade->link_pagamento }}" class="btn btn-red">Pagar Agora (Checkout)</a>
                <br><br>
            @endif

            @if($mensalidade->link_boleto)
                <a href="{{ $mensalidade->link_boleto }}" class="btn btn-blue" target="_blank">📄 Baixar Boleto PDF</a>
            @endif
        </center>

        @if($mensalidade->pix_copia_cola)
            <hr>
            <p><strong>Pix Copia e Cola:</strong></p>
            <div style="background: #eee; padding: 10px; font-size: 12px; word-wrap: break-word;">
                {{ $mensalidade->pix_copia_cola }}
            </div>
        @endif


    {{-- CASO 3: MENSALIDADE PENDENTE --}}
    @else 

        <p>Este é um lembrete da sua mensalidade que vence em breve.</p>

        <div class="details">
            <p><strong>Vencimento:</strong> {{ \Carbon\Carbon::parse($mensalidade->data_vencimento)->format('d/m/Y') }}</p>
            <p><strong>Valor:</strong> R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}</p>
            <p><strong>Curso:</strong> {{ $mensalidade->matricula->turma->nome }}</p>
        </div>

        <p>Utilize os botões abaixo para efetuar o pagamento:</p>

        <center>
            @if($mensalidade->link_pagamento)
                <a href="{{ $mensalidade->link_pagamento }}" class="btn btn-green">Pagar Online</a>
                <br><br>
            @endif
            
            @if($mensalidade->link_boleto)
                <a href="{{ $mensalidade->link_boleto }}" class="btn btn-blue" target="_blank">📄 Imprimir Boleto Bancário</a>
            @endif
        </center>
        
        @if($mensalidade->pix_copia_cola)
            <hr>
            <p><strong>Pix Copia e Cola:</strong></p>
            <div style="background: #eee; padding: 10px; font-size: 12px; word-wrap: break-word;">
                {{ $mensalidade->pix_copia_cola }}
            </div>
        @endif

    @endif

    <hr style="margin-top: 30px;">
    <p style="font-size: 12px; color: #777; text-align: center;">Caso já tenha efetuado o pagamento, desconsidere este e-mail.</p>
</div>

</body>
</html>