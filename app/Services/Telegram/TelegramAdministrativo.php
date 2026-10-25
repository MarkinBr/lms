<?php

namespace App\Services\Telegram;

use App\Models\Application;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TelegramAdministrativo
{
    private ?int $updateAtual = null;

    private ?array $callbackAtual = null;

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
        DB::transaction(function () use ($usuario) {
            DB::table('telegram_vinculos')->where('user_id', $usuario->id)->delete();
            DB::table('telegram_outbox')
                ->where('user_id', $usuario->id)
                ->whereIn('status', ['pendente', 'enviando'])
                ->update(['status' => 'descartado', 'payload' => null, 'lease_ate' => null,
                    'proxima_tentativa_em' => null, 'updated_at' => now()]);
        });
    }

    public function receber(array $update): void
    {
        $id = $update['update_id'] ?? null;
        if (! is_int($id) || $id < 0) {
            return;
        }

        $this->updateAtual = $id;
        $this->callbackAtual = isset($update['callback_query']) && is_array($update['callback_query'])
            ? $update['callback_query'] : null;

        try {
            DB::transaction(function () use ($update, $id) {
                if (DB::table('telegram_updates')->insertOrIgnore([
                    'update_id' => $id, 'created_at' => now(),
                ]) === 0) {
                    return;
                }

                if (isset($update['callback_query'])) {
                    $this->processarCallback($update['callback_query']);
                } else {
                    $this->processarMensagem($update['message'] ?? null);
                }
            });
        } finally {
            $this->updateAtual = null;
            $this->callbackAtual = null;
        }

        // Nenhuma chamada HTTP acontece enquanto a transação acadêmica está aberta.
        // Só tenta o registro deste update; mensagens pendentes têm reentrega separada.
        $saida = DB::table('telegram_outbox')->where('update_id', $id)->value('id');
        if ($saida !== null) {
            app(TelegramOutbox::class)->entregar((int) $saida);
        }
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
        $preferencias = app(PreferenciasAlertasTelegram::class);
        if ($texto === '/alertas' || $texto === '/alertas sim' || $texto === '/alertas nao') {
            $resposta = $texto === '/alertas'
                ? $preferencias->consultar($chatId)
                : $preferencias->definir($chatId, $texto === '/alertas sim');
            $this->enviar($chatId, $resposta['text']);

            return;
        }

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
                '/filtros' => $consulta->filtros(),
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
        } elseif ($codigo === 'alertas') {
            $resposta = app(PreferenciasAlertasTelegram::class)->consultar($chatId);
        } elseif (in_array($codigo, ['filtros', 'hoje', 'relatorios', 'aulaspendentes'], true)) {
            $resposta = $consulta->{$codigo}();
        } elseif ($codigo === 'agenda7') {
            $resposta = $consulta->agendaSemanal();
        } elseif (preg_match('/^filtro:(curso|turma|status):([a-z0-9]{1,12}):([1-9][0-9]?)$/', $codigo, $m)) {
            $resposta = $consulta->filtrarAlunos($m[1], $m[2], (int) $m[3]);
        } elseif (preg_match('/^(notas|frequencia):([1-9][0-9]{0,11})$/', $codigo, $m)) {
            $resposta = $consulta->{$m[1]}((int) $m[2]);
        } elseif (preg_match('/^ficha:([1-9][0-9]{0,11})$/', $codigo, $m)) {
            $resposta = $consulta->aluno((int) $m[1]);
        } elseif (in_array($codigo, ['pendencias', 'agenda', 'cursos'], true)) {
            $resposta = $consulta->{$codigo}();
        } elseif (preg_match('/^lista:(alunos|inscricoes|matriculas|turmas|aulas):([0-9]{1,2})$/', $codigo, $m)) {
            $resposta = $consulta->listar($m[1], (int) $m[2]);
        } else {
            $resposta = ['text' => 'Opção inválida. Use /menu.'];
        }

        $novosMenus = in_array($codigo, ['hoje', 'relatorios', 'agenda7', 'aulaspendentes', 'filtros', 'alertas'], true)
            || preg_match('/^(filtro|ficha|notas|frequencia):/', $codigo) === 1;
        if ($novosMenus) {
            $this->atualizarCallback($chatId, $callback, $resposta);
        } else {
            $this->enviar($chatId, $resposta['text'], $resposta['reply_markup'] ?? null);
        }
    }

    private function atualizarCallback(string $chatId, array $callback, array $resposta): void
    {
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
        if ($this->updateAtual === null) {
            throw new \LogicException('Resposta Telegram fora de update identificado.');
        }

        $vinculo = DB::table('telegram_vinculos')->where('chat_id', $chatId)->first();
        $usuario = $vinculo ? User::find($vinculo->user_id) : null;
        $userId = $this->podeOperar($usuario) ? (int) $usuario->id : null;
        $codigo = (string) ($this->callbackAtual['data'] ?? '');
        $editar = $userId !== null && $this->callbackAtual !== null && (
            in_array($codigo, ['hoje', 'relatorios', 'agenda7', 'aulaspendentes', 'filtros', 'alertas'], true)
            || preg_match('/^(filtro|ficha|notas|frequencia):/', $codigo) === 1
        );

        app(TelegramOutbox::class)->registrar(
            $this->updateAtual, $chatId, $texto, $teclado, $userId,
            $editar ? $this->callbackAtual : null
        );
    }
}
