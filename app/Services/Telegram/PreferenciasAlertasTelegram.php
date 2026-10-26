<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PreferenciasAlertasTelegram
{
    public function consultar(string $chatId): array
    {
        $userId = $this->donoAutorizado($chatId);
        $ativo = (bool) DB::table('telegram_alerta_preferencias')
            ->where('user_id', $userId)->value('ativo');

        return ['text' => 'Alertas administrativos '.($ativo ? 'solicitados.' : 'desativados.')
            ."\nAtivação/desativação da preferência: /alertas sim ou /alertas nao."
            ."\nResumo diário às 08:00 (horário de Brasília), sujeito à execução do agendador."];
    }

    public function definir(string $chatId, bool $ativo): array
    {
        $userId = $this->donoAutorizado($chatId);
        DB::table('telegram_alerta_preferencias')->updateOrInsert(
            ['user_id' => $userId],
            ['ativo' => $ativo, 'created_at' => now(), 'updated_at' => now()]
        );
        if (! $ativo) {
            DB::table('telegram_outbox')->where('user_id', $userId)
                ->where('evento_chave', 'like', 'resumo_diario:%')
                ->whereIn('status', ['pendente', 'enviando'])
                ->update(['status' => 'descartado', 'payload' => null,
                    'lease_ate' => null, 'proxima_tentativa_em' => null, 'updated_at' => now()]);
        }

        return $this->consultar($chatId);
    }

    private function donoAutorizado(string $chatId): int
    {
        $vinculo = DB::table('telegram_vinculos')->where('chat_id', $chatId)->first();
        $usuario = $vinculo ? User::find($vinculo->user_id) : null;
        abort_unless(app(TelegramAdministrativo::class)->podeOperar($usuario), 403);

        return (int) $usuario->id;
    }
}
