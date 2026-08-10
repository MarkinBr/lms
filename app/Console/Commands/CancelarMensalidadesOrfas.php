<?php

namespace App\Console\Commands;

use App\Models\Matricula;
use App\Models\Mensalidade;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelarMensalidadesOrfas extends Command
{
    protected $signature = 'mensalidades:cancelar-orfas {--dry-run : Lista as futuras que seriam canceladas}';

    protected $description = 'Cancela mensalidades futuras de matrículas canceladas/inativas';

    public function handle(): int
    {
        $hoje = now(config('app.timezone', 'America/Sao_Paulo'))->startOfDay();

        $query = Mensalidade::query()
            ->whereIn('status', ['Pendente', 'Atrasado'])
            ->whereDate('data_vencimento', '>=', $hoje)
            ->whereHas('matricula', function ($q) {
                $q->whereRaw("LOWER(COALESCE(status, '')) IN ('cancelado','cancelada','inativo','inativa')");
            })
            ->with('matricula.user')
            ->orderBy('id');

        $qtd = (clone $query)->count();

        if ($qtd === 0) {
            $this->info('✅ Nenhuma mensalidade futura órfã encontrada.');
            return self::SUCCESS;
        }

        $this->warn("⚠️ {$qtd} mensalidades futuras serão canceladas.");

        if ($this->option('dry-run')) {
            (clone $query)->limit(20)->get()->each(function ($m) {
                $this->line(sprintf(
                    '  • #%d — %s — venc %s',
                    $m->id,
                    $m->matricula?->user?->name ?? 'N/A',
                    $m->data_vencimento->format('d/m/Y')
                ));
            });

            return self::SUCCESS;
        }

        $canceladas = 0;

        DB::transaction(function () use ($query, &$canceladas) {
            $query->chunkById(100, function ($mensalidades) use (&$canceladas) {
                foreach ($mensalidades as $mensalidade) {
                    $m = Mensalidade::query()->whereKey($mensalidade->id)->lockForUpdate()->first();

                    if (!$m) {
                        continue;
                    }

                    if (!in_array((string) $m->status, ['Pendente', 'Atrasado'], true)) {
                        continue;
                    }

                    $obsNova = trim((string) ($m->observacao ?? ''));
                    $sufixo = '[Auto-cancelado: matrícula cancelada]';
                    if (!str_contains($obsNova, $sufixo)) {
                        $obsNova = trim($obsNova . ' ' . $sufixo);
                    }

                    $m->status = 'Cancelado';
                    $m->observacao = $obsNova;
                    $m->updated_at = now();
                    $m->save();
                    $canceladas++;
                }
            });
        });

        Log::info('Scheduler: mensalidades órfãs canceladas', ['canceladas' => $canceladas]);
        $this->info("✅ {$canceladas} mensalidades futuras canceladas.");

        return self::SUCCESS;
    }
}
