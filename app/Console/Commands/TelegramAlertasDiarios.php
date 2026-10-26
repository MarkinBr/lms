<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Aula;
use App\Models\Mensalidade;
use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TelegramAlertasDiarios extends Command
{
    protected $signature = 'telegram:alertas-diarios';

    protected $description = 'Enfileira resumo acadêmico e financeiro somente para administrador que optou por alertas';

    public function handle(): int
    {
        if (! filled(config('services.telegram.bot_token'))) {
            $this->warn('Bot Telegram não configurado.');

            return self::SUCCESS;
        }

        $dia = now('America/Sao_Paulo')->toDateString();
        $pendencias = Application::query()->emAnalise()->count();
        $aulas = Aula::query()->where('liberada', false)->count();
        $mensalidades = Mensalidade::query()->whereIn('status', ['Pendente', 'Atrasado'])->count();

        $texto = "CETSADP • Resumo diário ({$dia})\n"
            ."Inscrições em análise: {$pendencias}\n"
            ."Aulas não liberadas: {$aulas}\n"
            ."Mensalidades pendentes/atrasadas: {$mensalidades}\n"
            .'Consulte /pendencias ou /financeiro para acompanhar.';

        $preferencias = DB::table('telegram_alerta_preferencias')->where('ativo', true)->get(['user_id']);
        foreach ($preferencias as $preferencia) {
            DB::transaction(function () use ($preferencia, $dia, $texto) {
                $vinculo = DB::table('telegram_vinculos')->where('user_id', $preferencia->user_id)
                    ->lockForUpdate()->first();
                $usuario = User::find($preferencia->user_id);
                if (! $vinculo || ! $vinculo->chat_id || ! app(TelegramAdministrativo::class)->podeOperar($usuario)
                    || ! DB::table('telegram_alerta_preferencias')->where('user_id', $usuario->id)->where('ativo', true)->exists()) {
                    return;
                }

                DB::table('telegram_outbox')->insertOrIgnore([
                    'update_id' => null,
                    'evento_chave' => 'resumo_diario:'.$dia.':'.$usuario->id,
                    'chat_id' => $vinculo->chat_id,
                    'user_id' => $usuario->id,
                    'requer_autorizacao' => true,
                    'status' => 'pendente',
                    'payload' => json_encode(['text' => $texto, 'reply_markup' => null], JSON_THROW_ON_ERROR),
                    'expira_em' => now()->addHours(4),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }

        $this->info('Alertas diários elegíveis enfileirados.');

        return self::SUCCESS;
    }
}
