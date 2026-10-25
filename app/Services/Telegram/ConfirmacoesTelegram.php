<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmacoesTelegram
{
    public function preparar(string $chatId, int $updateId): array
    {
        $usuario = $this->dono($chatId);
        $codigo = bin2hex(random_bytes(16));
        $id = DB::table('telegram_confirmacoes')->insertGetId([
            'user_id' => $usuario->id, 'chat_id' => $chatId,
            'codigo_hash' => hash('sha256', $codigo),
            'acao' => 'simulacao', 'status' => 'pendente',
            'expira_em' => now()->addMinutes(10),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('telegram_auditoria')->insert([
            'user_id' => $usuario->id, 'confirmacao_id' => $id,
            'update_id' => $updateId, 'acao' => 'simulacao',
            'antes' => 'inexistente', 'depois' => 'pendente',
            'created_at' => now(),
        ]);

        return [
            'text' => 'Simulação administrativa'."\n".'Esta confirmação é apenas um teste: NÃO aprova inscrições nem altera matrículas.'."\n"
                ."Válida por 10 minutos. Confirme ou cancele.",
            'reply_markup' => ['inline_keyboard' => [[
                ['text' => 'Confirmar', 'callback_data' => 'confirmar:'.$codigo],
                ['text' => 'Cancelar', 'callback_data' => 'cancelar:'.$codigo],
            ]]],
        ];
    }

    public function decidir(string $chatId, string $acao, string $codigo, int $updateId): array
    {
        $usuario = $this->dono($chatId);
        if (! in_array($acao, ['confirmar', 'cancelar'], true) || preg_match('/^[0-9a-f]{32}$/D', $codigo) !== 1) {
            return ['text' => 'Confirmação inválida.'];
        }

        $resultado = DB::transaction(function () use ($chatId, $acao, $codigo, $updateId, $usuario) {
            $linha = DB::table('telegram_confirmacoes')
                ->where('codigo_hash', hash('sha256', $codigo))
                ->lockForUpdate()->first();
            if ($linha === null || (int) $linha->user_id !== (int) $usuario->id || $linha->chat_id !== $chatId) {
                return 'invalido';
            }
            if ($linha->status !== 'pendente') {
                return $linha->status;
            }

            $status = now()->greaterThan($linha->expira_em) ? 'expirado'
                : ($acao === 'confirmar' ? 'confirmado' : 'cancelado');
            DB::table('telegram_confirmacoes')->where('id', $linha->id)->update([
                'status' => $status, 'consumido_em' => now(), 'updated_at' => now(),
            ]);
            DB::table('telegram_auditoria')->insert([
                'user_id' => $usuario->id, 'confirmacao_id' => $linha->id,
                'update_id' => $updateId, 'acao' => $linha->acao,
                'antes' => 'pendente', 'depois' => $status,
                'created_at' => now(),
            ]);

            return $status;
        });

        return ['text' => match ($resultado) {
            'confirmado' => 'Simulação confirmada. Nenhum dado acadêmico foi alterado.',
            'cancelado' => 'Simulação cancelada. Nenhum dado acadêmico foi alterado.',
            'expirado' => 'Confirmação expirada. Inicie outra simulação.',
            default => 'Confirmação inválida ou sem autorização.',
        }];
    }

    private function dono(string $chatId): User
    {
        $id = DB::table('telegram_vinculos')->where('chat_id', $chatId)->value('user_id');
        $usuario = $id ? User::find($id) : null;
        abort_unless(app(TelegramAdministrativo::class)->podeOperar($usuario), 403);

        return $usuario;
    }
}
