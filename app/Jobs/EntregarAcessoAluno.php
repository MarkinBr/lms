<?php

namespace App\Jobs;

use App\Services\Acessos\EntregasAcesso;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Throwable;

class EntregarAcessoAluno implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $entregaId) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(EntregasAcesso $entregas): void
    {
        try {
            $entregas->enviarEmail($this->entregaId);
        } catch (Throwable) {
            // Mensagens do transporte podem conter destinatários ou dados reservados.
            throw new \RuntimeException('Não foi possível concluir a entrega de e-mail do atendimento.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::table('acesso_entregas')->where('id', $this->entregaId)->where('estado', 'enfileirado')->update(['estado' => 'falhou', 'conteudo' => null, 'updated_at' => now()]);
    }
}
