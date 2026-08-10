<?php

namespace App\Console\Commands;

use App\Jobs\EnviarLembreteVencimento;
use App\Models\Mensalidade;
use Illuminate\Console\Command;

class DispararLembretesVencimento extends Command
{
    protected $signature = 'lembretes:disparar-vencimento
                            {--dias=3 : Dias antes do vencimento para lembrar}
                            {--dry-run}';

    protected $description = 'Dispara lembretes para mensalidades que vencem em N dias (MF-03)';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $alvo = now()->startOfDay()->addDays($dias);

        $base = Mensalidade::query()
            ->where('status', 'Pendente')
            ->whereDate('data_vencimento', $alvo);

        $qtd = (clone $base)->count();
        $this->info("📨 {$qtd} mensalidades vencem em {$alvo->format('d/m/Y')}.");

        if ($qtd === 0 || $this->option('dry-run')) return self::SUCCESS;

        $base->select('id')->chunkById(100, function ($lote) {
            foreach ($lote as $m) {
                EnviarLembreteVencimento::dispatch($m->id)
                    ->onQueue('cobrancas');
            }
        });

        $this->info("✅ {$qtd} jobs de lembrete despachados para a fila 'cobrancas'.");
        return self::SUCCESS;
    }
}