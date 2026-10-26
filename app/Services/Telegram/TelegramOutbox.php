<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TelegramOutbox
{
    public function registrar(int $updateId, string $chatId, string $texto, ?array $teclado = null, ?int $usuarioId = null, ?array $callback = null, bool $protegida = true): void
    {
        $payload = ['text' => $texto, 'reply_markup' => $teclado];
        if ($callback !== null) {
            $payload['callback_id'] = $callback['id'] ?? null;
            $payload['message_id'] = $callback['message']['message_id'] ?? null;
        }

        DB::table('telegram_outbox')->insertOrIgnore([
            'update_id' => $updateId,
            'chat_id' => $chatId,
            'user_id' => $usuarioId,
            'requer_autorizacao' => $protegida,
            'status' => 'pendente',
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'expira_em' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function drenarPendentes(int $limite = 20): void
    {
        foreach (DB::table('telegram_outbox')
            ->whereIn('status', ['pendente', 'enviando'])
            ->where(function ($q) {
                $q->whereNull('proxima_tentativa_em')->orWhere('proxima_tentativa_em', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('lease_ate')->orWhere('lease_ate', '<=', now());
            })
            ->orderBy('id')->limit(min(max($limite, 1), 100))->pluck('id') as $id) {
            $this->entregar((int) $id);
        }
    }

    public function entregar(int $id): void
    {
        $item = DB::transaction(function () use ($id) {
            $linha = DB::table('telegram_outbox')->where('id', $id)->lockForUpdate()->first();
            if (! $linha || ! in_array($linha->status, ['pendente', 'enviando'], true)
                || ($linha->lease_ate !== null && now()->lessThan($linha->lease_ate))
                || ($linha->proxima_tentativa_em !== null && now()->lessThan($linha->proxima_tentativa_em))) {
                return null;
            }

            $atualizar = [
                'status' => 'enviando',
                'tentativas' => $linha->tentativas + 1,
                'lease_ate' => now()->addSeconds(30),
                'updated_at' => now(),
            ];
            DB::table('telegram_outbox')->where('id', $id)->update($atualizar);

            return $linha;
        });
        if (! $item) {
            return;
        }

        if (now()->greaterThan($item->expira_em) || ! $this->autorizado($item)) {
            $this->finalizar($id, 'descartado');

            return;
        }

        $payload = json_decode($item->payload, true);
        if (! is_array($payload) || ! is_string($payload['text'] ?? null)) {
            $this->finalizar($id, 'descartado');

            return;
        }
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            $this->reagendar($id, (int) $item->tentativas + 1);

            return;
        }

        try {
            $dados = ['chat_id' => $item->chat_id, 'text' => $payload['text']];
            if (is_array($payload['reply_markup'] ?? null)) {
                $dados['reply_markup'] = $payload['reply_markup'];
            }
            $tipo = is_int($payload['message_id'] ?? null) ? 'editMessageText' : 'sendMessage';
            if ($tipo === 'editMessageText') {
                $dados['message_id'] = $payload['message_id'];
            }

            $resposta = Http::timeout(5)->post('https://api.telegram.org/bot'.$token.'/'.$tipo, $dados);
            if ($tipo === 'editMessageText' && ! ($resposta->successful() && $resposta->json('ok') === true)) {
                unset($dados['message_id']);
                $resposta = Http::timeout(5)->post('https://api.telegram.org/bot'.$token.'/sendMessage', $dados);
            }
            if (! $resposta->successful() || $resposta->json('ok') !== true) {
                $this->reagendar($id, (int) $item->tentativas + 1);

                return;
            }
            $this->finalizar($id, 'entregue');
        } catch (ConnectionException) {
            $this->reagendar($id, (int) $item->tentativas + 1);

            return;
        }

        if (is_string($payload['callback_id'] ?? null) && $payload['callback_id'] !== '') {
            try {
                Http::timeout(3)->post('https://api.telegram.org/bot'.$token.'/answerCallbackQuery', [
                    'callback_query_id' => $payload['callback_id'],
                ]);
            } catch (ConnectionException) {
                // A confirmação visual é best effort: nunca reabre a saída entregue.
            }
        }
    }

    private function autorizado(object $item): bool
    {
        if ($item->evento_chave !== null && str_starts_with($item->evento_chave, 'resumo_diario:')) {
            if ($item->user_id === null || ! DB::table('telegram_alerta_preferencias')
                ->where('user_id', $item->user_id)->where('ativo', true)->exists()) {
                return false;
            }
        }
        if (! (bool) $item->requer_autorizacao) {
            // Apenas respostas explicitamente classificadas como genéricas.
            return true;
        }

        if ($item->user_id === null) {
            // Excluir o usuário não transforma uma resposta protegida em pública.
            return false;
        }
        $vinculo = DB::table('telegram_vinculos')
            ->where('user_id', $item->user_id)
            ->where('chat_id', $item->chat_id)->exists();

        return $vinculo && app(TelegramAdministrativo::class)->podeOperar(User::find($item->user_id));
    }

    private function reagendar(int $id, int $tentativa): void
    {
        DB::table('telegram_outbox')->where('id', $id)->update([
            'status' => 'pendente',
            'lease_ate' => null,
            'proxima_tentativa_em' => now()->addSeconds(min(300, 5 * (2 ** min($tentativa, 6)))),
            'updated_at' => now(),
        ]);
    }

    private function finalizar(int $id, string $estado): void
    {
        DB::table('telegram_outbox')->where('id', $id)->update([
            'status' => $estado, 'payload' => null, 'lease_ate' => null,
            'proxima_tentativa_em' => null, 'updated_at' => now(),
        ]);
    }
}
