<?php

namespace App\Services\Telegram;

use App\Models\Application;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TelegramAdministrativo
{
    public function podeOperar(?User $usuario): bool
    {
        $configurado = config('services.telegram.admin_user_id');
        if (! is_int($configurado) && ! is_string($configurado)) {
            return false;
        }

        $donoId = filter_var($configurado, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $donoId !== false && $usuario !== null
            && (int) $usuario->id === $donoId
            && $usuario->ativo && $usuario->nivel === 'admin';
    }

    public function criarCodigo(User $usuario): string
    {
        abort_unless($this->podeOperar($usuario), 403);

        $codigo = bin2hex(random_bytes(16));

        DB::table('telegram_vinculos')->updateOrInsert(
            ['user_id' => $usuario->id],
            [
                'chat_id' => null,
                'codigo_hash' => hash('sha256', $codigo),
                'codigo_expira_em' => now()->addMinutes(10),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $codigo;
    }

    public function revogar(User $usuario): void
    {
        abort_unless($this->podeOperar($usuario), 403);
        DB::table('telegram_vinculos')->where('user_id', $usuario->id)->delete();
    }

    public function receber(array $update): void
    {
        $id = $update['update_id'] ?? null;
        if (! is_int($id) || $id < 0) {
            return;
        }

        DB::transaction(function () use ($update, $id) {
            // A chave impede concorrência; falha de envio reverte também o pareamento.
            if (! DB::table('telegram_updates')->insertOrIgnore(['update_id' => $id, 'created_at' => now()])) {
                return;
            }

            $this->processarMensagem($update['message'] ?? null);
        });
    }

    private function processarMensagem(mixed $mensagem): void
    {
        if (! is_array($mensagem)) {
            return;
        }
        $chat = $mensagem['chat'] ?? [];
        $remetente = $mensagem['from'] ?? [];
        if (($chat['type'] ?? null) !== 'private'
            || ! isset($chat['id'], $remetente['id'])
            || (string) $chat['id'] !== (string) $remetente['id']) {
            return;
        }

        $chatId = (string) $chat['id'];
        $texto = trim((string) ($mensagem['text'] ?? ''));
        if (preg_match('/^\/vincular ([0-9a-f]{32})$/i', $texto, $m)) {
            $this->enviar($chatId, $this->vincular($chatId, $m[1])
                ? 'Conta administrativa vinculada. Envie /ajuda.'
                : 'Código inválido ou expirado. Gere outro no painel administrativo.');

            return;
        }

        $vinculo = DB::table('telegram_vinculos')->where('chat_id', $chatId)->first();
        $usuario = $vinculo ? User::find($vinculo->user_id) : null;
        if (! $this->podeOperar($usuario)) {
            $this->enviar($chatId, 'Acesso restrito. Somente o administrador autorizado pode usar este bot.');

            return;
        }

        $resposta = match ($texto) {
            '/start', '/ajuda' => "CETSADP • Consultas administrativas\n/ajuda — comandos\n/status — situação do bot\n/resumo — indicadores acadêmicos",
            '/status' => 'Bot CETSADP disponível. Sua conta administrativa está vinculada.',
            '/resumo' => $this->resumo(),
            default => 'Comando desconhecido. Digite /ajuda.',
        };
        $this->enviar($chatId, $resposta);
    }

    private function vincular(string $chatId, string $codigo): bool
    {
        return DB::transaction(function () use ($chatId, $codigo) {
            $vinculo = DB::table('telegram_vinculos')
                ->where('codigo_hash', hash('sha256', strtolower($codigo)))
                ->lockForUpdate()->first();
            if (! $vinculo || ! $vinculo->codigo_expira_em
                || now()->greaterThan($vinculo->codigo_expira_em)) {
                return false;
            }
            $usuario = User::find($vinculo->user_id);
            if (! $this->podeOperar($usuario)) {
                return false;
            }

            // Um código válido do dono atual pode substituir o vínculo de um dono anterior.
            DB::table('telegram_vinculos')->where('chat_id', $chatId)
                ->where('user_id', '!=', $usuario->id)->delete();
            DB::table('telegram_vinculos')->where('id', $vinculo->id)->update([
                'chat_id' => $chatId,
                'codigo_hash' => null,
                'codigo_expira_em' => null,
                'updated_at' => now(),
            ]);

            return true;
        });
    }

    private function resumo(): string
    {
        return "CETSADP • Resumo acadêmico\n"
            .'Alunos cadastrados: '.User::where('nivel', 'aluno')->count()."\n"
            .'Matrículas ativas: '.Matricula::query()->ativas()->count()."\n"
            .'Inscrições em análise: '.Application::where('status', Application::STATUS_EM_ANALISE)->count();
    }

    private function enviar(string $chatId, string $texto): void
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            throw new TelegramFalhaEnvio;
        }

        try {
            $resposta = Http::timeout(5)->post('https://api.telegram.org/bot'.$token.'/sendMessage', [
                'chat_id' => $chatId,
                'text' => $texto,
            ]);
        } catch (ConnectionException) {
            // A exceção original contém a URL com token; não a propagar nem registrar.
            throw new TelegramFalhaEnvio;
        }

        if (! $resposta->successful() || $resposta->json('ok') !== true) {
            throw new TelegramFalhaEnvio;
        }
    }
}
