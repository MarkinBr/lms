<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarPixWhatsAppJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public string $telefone,
        public string $base64Image,
        public string $copiaECola
    ) {}

    public function handle(WhatsAppService $whatsapp): void
    {
        $whatsapp->enviarMensagem(
            $this->telefone,
            "📱 *Escaneie o QR Code para pagar via PIX*\n\nOu use o código copia e cola abaixo:"
        );

        $whatsapp->enviarMensagem(
            $this->telefone,
            "💰 *PIX Copia e Cola*\n\n`{$this->copiaECola}`\n\n✅ Pagamento confirmado automaticamente!"
        );
    }
}