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

            if (isset($update['callback_query'])) {
                $this->processarCallback($update['callback_query']);
            } else {
                $this->processarMensagem($update['message'] ?? null);
            }
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
        if (preg_match('/^\\/vincular ([0-9a-f]{32})$/i', $texto, $m)) {
            $this->enviar($chatId, $this->vincular($chatId, $m[1])
                ? 'Conta administrativa vinculada. Envie /ajuda.'
                : 'Código inválido ou expirado. Gere outro no painel administrativo.');

            return;
        }

        if (! $this->autorizado($chatId)) {
            $this->enviar($chatId, 'Acesso restrito. Somente o administrador autorizado pode usar este bot.');

            return;
        }

        $consulta = app(ConsultasAcademicasTelegram::class);
        if (preg_match('/^\\/buscar\\s+(.+)$/us', $texto, $m)) {
            $resposta = $consulta->buscarAluno($m[1]);
        } elseif (preg_match('/^\\/(turma|matricula|aluno|notas|frequencia)\\s+([0-9]{1,12})$/', $texto, $m)) {
            $id = (int) $m[2];
            $resposta = $consulta->{$m[1]}($id);
        } elseif (preg_match('/^\\/filtro\\s+(curso|turma|status)\\s+([a-z0-9]{1,12})$/', $texto, $m)) {
            $resposta = $consulta->filtrarAlunos($m[1], $m[2], 1);
        } elseif (preg_match('/^\\/inscricao\\s+([0-9a-fA-F-]{36})$/', $texto, $m)) {
            $resposta = $consulta->inscricao($m[1]);
        } else {
            $resposta = match ($texto) {
                '/hoje' => $consulta->hoje(),
                '/agenda7' => $consulta->agendaSemanal(),
                '/relatorios' => $consulta->relatorios(),
                '/aulaspendentes' => $consulta->aulasPendentes(),
                '/pendencias' => $consulta->pendencias(),
                '/agenda' => $consulta->agenda(),
                '/cursos' => $consulta->cursos(),
                '/start', '/ajuda', '/menu' => $consulta->menu(),
                '/status' => ['text' => 'Bot CETSADP disponível. Sua conta administrativa está vinculada. Use /menu.'],
                '/resumo' => ['text' => $this->resumo()],
                default => ['text' => 'Comando desconhecido. Digite /menu para consultar.'],
            };
        }
        $this->enviar($chatId, $resposta['text'], $resposta['reply_markup'] ?? null);
    }

    private function autorizado(string $chatId): bool
    {
        $vinculo = DB::table('telegram_vinculos')->where('chat_id', $chatId)->first();
        $usuario = $vinculo ? User::find($vinculo->user_id) : null;

        return $this->podeOperar($usuario);
    }

    private function processarCallback(mixed $callback): void
    {
        if (! is_array($callback)) {
            return;
        }
        $chat = $callback['message']['chat'] ?? [];
        $remetente = $callback['from'] ?? [];
        if (($chat['type'] ?? null) !== 'private'
            || ! isset($chat['id'], $remetente['id'])
            || (string) $chat['id'] !== (string) $remetente['id']) {
            return;
        }

        $chatId = (string) $chat['id'];
        if (! $this->autorizado($chatId)) {
            $this->enviar($chatId, 'Acesso restrito. Somente o administrador autorizado pode usar este bot.');

            return;
        }

        $codigo = (string) ($callback['data'] ?? '');
        $consulta = app(ConsultasAcademicasTelegram::class);
        if ($codigo === 'menu') {
            $resposta = $consulta->menu();
        } elseif (in_array($codigo, ['hoje', 'relatorios', 'aulaspendentes'], true)) {
            $resposta = $consulta->{$codigo}();
        } elseif ($codigo === 'agenda7') {
            $resposta = $consulta->agendaSemanal();
        } elseif (preg_match('/^filtro:(curso|turma|status):([a-z0-9]{1,12}):([1-9][0-9]?)$/', $codigo, $m)) {
            $resposta = $consulta->filtrarAlunos($m[1], $m[2], (int) $m[3]);
        } elseif (preg_match('/^ficha:([1-9][0-9]{0,11})$/', $codigo, $m)) {
            $resposta = $consulta->aluno((int) $m[1]);
        } elseif (in_array($codigo, ['pendencias', 'agenda', 'cursos'], true)) {
            $resposta = $consulta->{$codigo}();
        } elseif (preg_match('/^lista:(alunos|inscricoes|matriculas|turmas|aulas):([0-9]{1,2})$/', $codigo, $m)) {
            $resposta = $consulta->listar($m[1], (int) $m[2]);
        } else {
            $resposta = ['text' => 'Opção inválida. Use /menu.'];
        }

        $this->enviar($chatId, $resposta['text'], $resposta['reply_markup'] ?? null);
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

    private function enviar(string $chatId, string $texto, ?array $teclado = null): void
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            throw new TelegramFalhaEnvio;
        }

        try {
            $dados = ['chat_id' => $chatId, 'text' => $texto];
            if ($teclado !== null) {
                $dados['reply_markup'] = $teclado;
            }
            $resposta = Http::timeout(5)->post('https://api.telegram.org/bot'.$token.'/sendMessage', $dados);
        } catch (ConnectionException) {
            // A exceção original contém a URL com token; não a propagar nem registrar.
            throw new TelegramFalhaEnvio;
        }

        if (! $resposta->successful() || $resposta->json('ok') !== true) {
            throw new TelegramFalhaEnvio;
        }
    }
}
