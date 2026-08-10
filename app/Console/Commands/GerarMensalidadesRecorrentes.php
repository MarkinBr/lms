<?php

namespace App\Console\Commands;

use App\Models\Matricula;
use App\Models\Mensalidade;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GerarMensalidadesRecorrentes extends Command
{
    protected $signature = 'mensalidades:gerar-recorrentes
                            {--mes= : Mês alvo no formato YYYY-MM. Padrão: próximo mês}
                            {--dry-run : Simula sem gravar}';

    protected $description = 'Gera mensalidades recorrentes das matrículas ativas';

    public function handle(): int
    {
        $tz = config('app.timezone', 'America/Sao_Paulo');
        $mesAlvo = $this->resolverMesAlvo($tz);
        $criadas = 0;
        $puladas = 0;
        $erros = 0;

        $this->info('📅 Gerando mensalidades para ' . $mesAlvo->format('m/Y'));

        $queryAtivas = Matricula::query()
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('ativo','ativa')")
            ->with(['curso']);

        $totalAtivas = (clone $queryAtivas)->count();
        $this->line("🎓 {$totalAtivas} matrículas ativas encontradas.");

        $bar = $this->output->createProgressBar(max($totalAtivas, 1));
        $bar->start();

        $queryAtivas->chunkById(100, function ($matriculas) use ($mesAlvo, &$criadas, &$puladas, &$erros, $bar) {
            foreach ($matriculas as $mat) {
                try {
                    $vencimento = $this->resolverVencimento($mat, $mesAlvo);
                    $valor = $this->resolverValor($mat);

                    if (!$valor || $valor <= 0) {
                        $puladas++;
                        $bar->advance();
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $existe = Mensalidade::query()
                            ->where('matricula_id', $mat->id)
                            ->whereDate('data_vencimento', $vencimento)
                            ->exists();

                        $existe ? $puladas++ : $criadas++;
                        $bar->advance();
                        continue;
                    }

                    $inserido = $this->criarMensalidadeSeAusente($mat, $vencimento, $valor);
                    $inserido ? $criadas++ : $puladas++;
                } catch (\Throwable $e) {
                    $erros++;
                    Log::error('Scheduler: erro ao gerar mensalidade recorrente', [
                        'matricula_id' => $mat->id,
                        'erro' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(['Métrica', 'Quantidade'], [
            ['✅ Criadas', $criadas],
            ['⏭️ Puladas', $puladas],
            ['❌ Erros', $erros],
        ]);

        Log::info('Scheduler: gerar recorrentes finalizado', compact('criadas', 'puladas', 'erros'));

        return $erros > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolverMesAlvo(string $tz): Carbon
    {
        $mes = $this->option('mes');

        if (is_string($mes) && preg_match('/^\d{4}-\d{2}$/', $mes) === 1) {
            return Carbon::createFromFormat('Y-m', $mes, $tz)->startOfMonth();
        }

        return now($tz)->addMonthNoOverflow()->startOfMonth();
    }

    private function resolverVencimento(Matricula $mat, Carbon $mesAlvo): Carbon
    {
        $dia = max(1, (int) ($mat->dia_vencimento ?? 10));
        $diaSeguro = min($dia, $mesAlvo->daysInMonth);

        return $mesAlvo->copy()->day($diaSeguro)->startOfDay();
    }

    private function resolverValor(Matricula $mat): ?float
    {
        if (isset($mat->valor_mensalidade) && (float) $mat->valor_mensalidade > 0) {
            return round((float) $mat->valor_mensalidade, 2);
        }

        if ($mat->curso && isset($mat->curso->valor_mensalidade) && (float) $mat->curso->valor_mensalidade > 0) {
            return round((float) $mat->curso->valor_mensalidade, 2);
        }

        if ($mat->curso && isset($mat->curso->valor) && (float) $mat->curso->valor > 0) {
            return round((float) $mat->curso->valor / 12, 2);
        }

        $ultima = method_exists($mat, 'mensalidades') ? $mat->mensalidades()->latest('data_vencimento')->first() : null;

        return $ultima ? round((float) $ultima->valor, 2) : null;
    }

    private function criarMensalidadeSeAusente(Matricula $mat, Carbon $vencimento, float $valor): bool
    {
        $payload = [
            'matricula_id' => $mat->id,
            'valor' => $valor,
            'data_vencimento' => $vencimento->toDateString(),
            'status' => 'Pendente',
            'observacao' => 'Gerada automaticamente pelo scheduler em ' . now(config('app.timezone', 'America/Sao_Paulo'))->format('d/m/Y H:i'),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        try {
            return DB::table('mensalidades')->insertOrIgnore($payload) === 1;
        } catch (QueryException $e) {
            if ($this->violacaoUnicidade($e)) {
                return false;
            }

            throw $e;
        }
    }

    private function violacaoUnicidade(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        return $sqlState === '23505';
    }
}
