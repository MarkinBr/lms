<?php

namespace App\Services;

use App\Models\Mensalidade;
use App\Mail\MensalidadeCobrancaMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailMensalidadeService
{
    public function enviar(Mensalidade $mensalidade)
    {
        // Verifica se tem email
        $email = $mensalidade->matricula->user->email ?? $mensalidade->matricula->aluno->email ?? null;

        if ($email) {
            try {
                // Envia usando sua classe de Email existente
                Mail::to($email)->send(new MensalidadeCobrancaMail($mensalidade));
            } catch (\Exception $e) {
                Log::error("Erro envio email: " . $e->getMessage());
            }
        }
    }

    // Métodos de atalho para o Resource usar
    public function enviarPix(Mensalidade $m) { return $this->enviar($m); }
    public function enviarBoleto(Mensalidade $m) { return $this->enviar($m); }
    public function enviarLembrete(Mensalidade $m) { return $this->enviar($m); }
    public function enviarCobranca(Mensalidade $m) { return $this->enviar($m); }
    public function enviarConfirmacaoPagamento(Mensalidade $m) { return $this->enviar($m); }
}