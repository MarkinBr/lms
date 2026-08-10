<?php

namespace App\Console\Commands;

use App\Models\Matricula;
use App\Models\Mensalidade;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AtualizarMensalidadesVencidas extends Command
{
    protected $signature = 'mensalidades:atualizar-vencidas {--dry-run : Mostra quantas seriam atualizadas sem gravar}';

    protected $description = 'Marca mensalidades pendentes vencidas como Atrasado';

    public function handle(): int
    {
        $agora = now(config('app.timezone', 'America/Sao_Paulo'));
        $inicioHoje = $agora->copy()->startOfDay();

        $base = Mensalidade::query()
            ->where('status', 'Pendente')
            ->whereDate('data_vencimento', '<', $inicioHoje);

        $qtd = (clone $base)->count();

        if ($qtd === 0) {
            $this->info('✅ Nenhuma mensalidade vencida pendente.');
            return self::SUCCESS;
        }

        $this->line("📊 {$qtd} mensalidades serão marcadas como Atrasado.");

        if ($this->option('dry-run')) {
            (clone $base)->with('matricula.user')->limit(10)->get()->each(function ($m) {
                $this->line(sprintf(
                    '  • #%d — %s — venc %s — R$ %s',
                    $m->id,
                    $m->matricula?->user?->name ?? 'N/A',
                    $m->data_vencimento->format('d/m/Y'),
                    number_format((float) $m->valor, 2, ',', '.')
                ));
            });

            return self::SUCCESS;
        }

        $atualizadas = DB::transaction(function () use ($base, $agora) {
            return $base->update([
                'status' => 'Atrasado',
                'updated_at' => $agora,
            ]);
        });

        Log::info('Scheduler: mensalidades atualizadas para Atrasado', ['qtd' => $atualizadas]);
        $this->info("✅ {$atualizadas} mensalidades atualizadas.");

        return self::SUCCESS;
    }
}
