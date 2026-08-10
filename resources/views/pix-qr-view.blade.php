@if (!empty($qr))
    <div style="text-align: center; margin-bottom: 15px;">
        <img src="data:image/png;base64,{{ $qr }}" style="width: 250px; height: 250px;" />
        <p style="font-size: 12px; color: #666;">Escaneie o QR Code acima</p>
    </div>
@else
    <p style="text-align:center; color:#999;">QR Code não disponível</p>
@endif
