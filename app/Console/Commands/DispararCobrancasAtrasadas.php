<?php

namespace App\Console\Commands;

use App\Jobs\EnviarCobrancaAtraso;
use App\Models\Mensalidade;
use Illuminate\Console\Command;

class DispararCobrancasAtrasadas extends Command
{
    protected $signature = 'cobrancas:disparar-atrasadas {--dry-run}';
    protected $description = 'Dispara cobranças escalonadas para mensalidades atrasadas (MF-04)';

    /** Escalonamento: dias após vencimento → tipo */
    private const ESCALONAMENTO = [
        1  => 'cobranca_1dia',
        7  => 'cobranca_7dias',
        15 => 'cobranca_15dias',
        30 => 'cobranca_30dias',
    ];

    public function handle(): int
    {
        $totalDespachados = 0;

        foreach (self::ESCALONAMENTO as $dias => $tipo) {
            $dataAlvo = now()->startOfDay()->subDays($dias);

            $base = Mensalidade::query()
                ->where('status', 'Atrasado')
                ->whereDate('data_vencimento', $dataAlvo);

            $qtd = (clone $base)->count();

            $this->line("🔁 {$tipo}: {$qtd} mensalidades vencidas há {$dias} dia(s).");

            if ($qtd === 0 || $this->option('dry-run')) continue;

            $base->select('id')->chunkById(100, function ($lote) use ($tipo, &$totalDespachados) {
                foreach ($lote as $m) {
                    EnviarCobrancaAtraso::dispatch($m->id, $tipo)->onQueue('cobrancas');
                    $totalDespachados++;
                }
            });
        }

        $this->info("✅ Total de {$totalDespachados} jobs despachados.");
        return self::SUCCESS;
    }
}