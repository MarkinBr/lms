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

        $this->revogarVinculo((int) $usuario->id);
    }

    /**
     * Revoga o vínculo mesmo quando o usuário acabou de perder o nível/admin ativo.
     * Chamado internamente pelo observer User, nunca exposto como endpoint público.
     */
    public function revogarVinculo(int $usuarioId): void
    {
        DB::transaction(function () use ($usuarioId) {
            $vinculo = DB::table('telegram_vinculos')->where('user_id', $usuarioId)
                ->lockForUpdate()->first();
            $pendentes = DB::table('telegram_confirmacoes')
                ->where('user_id', $usuarioId)
                ->where('status', 'pendente')
                ->lockForUpdate()->get(['id', 'acao']);
            foreach ($pendentes as $confirmacao) {
                DB::table('telegram_auditoria_revogacoes')->insert([
                    'user_id' => $usuarioId,
                    'confirmacao_id' => $confirmacao->id,
                    'acao' => $confirmacao->acao,
                    'antes' => 'pendente',
                    'depois' => 'cancelado',
                    'created_at' => now(),
                ]);
            }
            DB::table('telegram_confirmacoes')->whereIn('id', $pendentes->pluck('id'))
                ->where('status', 'pendente')->update([
                    'status' => 'cancelado', 'consumido_em' => now(), 'updated_at' => now(),
                ]);
            DB::table('telegram_publicacoes')->whereIn('confirmacao_id', $pendentes->pluck('id'))->delete();
            if ($vinculo !== null) {
                DB::table('telegram_vinculos')->where('id', $vinculo->id)->delete();
            }
            DB::table('telegram_outbox')
                ->where('user_id', $usuarioId)
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
            $vinculado = $this->vincular($chatId, $m[1]);
            $this->enviar($chatId, $vinculado
                ? 'Conta administrativa vinculada. Envie /ajuda.'
                : 'Código inválido ou expirado. Gere outro no painel administrativo.', null, $vinculado);

            return;
        }

        if (! $this->autorizado($chatId)) {
            $this->enviar($chatId, 'Acesso restrito. Somente o administrador autorizado pode usar este bot.', null, false);

            return;
        }

        $fluxo = app(FluxoCadastroAlunoTelegram::class);
        if ($texto === '/cancelar' && $fluxo->ativo($chatId)) {
            $resposta = $fluxo->acao($chatId, 'cancelar', $this->updateAtual);
            $this->enviar($chatId, $resposta['text'], $resposta['reply_markup'] ?? null);

            return;
        }

        if ($texto === '/novoaluno') {
            $resposta = $fluxo->iniciar($chatId);
            $this->enviar($chatId, $resposta['text'], $resposta['reply_markup'] ?? null);

            return;
        }

        if ($texto !== '' && ! str_starts_with($texto, '/') && $fluxo->ativo($chatId)) {
            $resposta = $fluxo->responder($chatId, $texto);
            if ($resposta !== null) {
                $this->enviar($chatId, $resposta['text'], $resposta['reply_markup'] ?? null);

                return;
            }
        }

        // Um comando novo abandona o rascunho anterior sem criar registros.
        if (str_starts_with($texto, '/') && $fluxo->ativo($chatId)) {
            $fluxo->cancelar($chatId);
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

        if (preg_match('/^\\/(aprovarinscricao|rejeitarinscricao|novoaluno|editaraluno|recuperaracesso|ativaracesso|lancarnota|corrigirnota|novanoticia)(?:\\s+(.+))?$/usD', $texto, $m)) {
            $servico = match ($m[1]) {
                'aprovarinscricao', 'rejeitarinscricao' => InscricoesAdministrativasTelegram::class,
                'novoaluno', 'editaraluno', 'recuperaracesso', 'ativaracesso' => AlunosAdministrativosTelegram::class,
                'lancarnota', 'corrigirnota' => NotasAdministrativasTelegram::class,
                'novanoticia' => NoticiasAdministrativasTelegram::class,
            };
            $resposta = app($servico)->preparar($chatId, $m[1], $m[2] ?? '', $this->updateAtual);
        } elseif (preg_match('/^\\/novoaviso\\s+(.+)$/usD', $texto, $m)) {
            $resposta = app(ConfirmacoesTelegram::class)->prepararAviso($chatId, $m[1], $this->updateAtual);
        } elseif (preg_match('/^\\/novaaula\\s+(.+)$/usD', $texto, $m)) {
            $resposta = app(ConfirmacoesTelegram::class)->prepararAula($chatId, $m[1], $this->updateAtual);
        } elseif (preg_match('/^\\/liberaraula\\s+(.+)$/usD', $texto, $m)) {
            $resposta = app(ConfirmacoesTelegram::class)->prepararLiberacaoAula($chatId, $m[1], $this->updateAtual);
        } elseif (preg_match('/^\\/concluirchamada\\s+(.+)$/usD', $texto, $m)) {
            $resposta = app(ConfirmacoesTelegram::class)->prepararConclusaoChamada($chatId, $m[1], $this->updateAtual);
        } elseif (preg_match('/^\\/novoevento\\s+(.+)$/usD', $texto, $m)) {
            $resposta = app(ConfirmacoesTelegram::class)->prepararEvento($chatId, $m[1], $this->updateAtual);
        } elseif (preg_match('/^\\/novachamada\\s+(.+)$/usD', $texto, $m)) {
            $resposta = app(ConfirmacoesTelegram::class)->prepararChamada($chatId, $m[1], $this->updateAtual);
        } elseif (preg_match('/^\\/registrarpresenca\\s+(.+)$/usD', $texto, $m)) {
            $resposta = app(ConfirmacoesTelegram::class)->prepararPresenca($chatId, $m[1], $this->updateAtual);
        } elseif (preg_match('/^\\/revisarinscricao\\s+([0-9a-fA-F-]{36})$/D', $texto, $m)) {
            $resposta = app(ConfirmacoesTelegram::class)->prepararRevisao($chatId, $m[1], $this->updateAtual);
        } elseif ($texto === '/testeconfirmacao') {
            $resposta = app(ConfirmacoesTelegram::class)->preparar($chatId, $this->updateAtual);
        } elseif (preg_match('/^\\/buscar\\s+(.+)$/us', $texto, $m)) {
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
                '/financeiro' => $consulta->financeiro(),
                '/aulaspendentes' => $consulta->aulasPendentes(),
                '/pendencias' => $consulta->pendencias(),
                '/agenda' => $consulta->agenda(),
                '/cursos' => $consulta->cursos(),
                '/start', '/ajuda', '/menu', '/administrar' => CatalogoComandosTelegram::menuPrincipal(),
                '/consultas' => $consulta->menu(),
                '/buscar' => ['text' => 'Uso: /buscar NOME (digite o nome do aluno após o comando).'],
                '/aluno' => ['text' => 'Uso: /aluno ID (informe o número do aluno).'],
                '/notas' => ['text' => 'Uso: /notas ID (informe o número do aluno).'],
                '/frequencia' => ['text' => 'Uso: /frequencia ID (informe o número do aluno).'],
                '/turma' => ['text' => 'Uso: /turma ID (informe o número da turma).'],
                '/matricula' => ['text' => 'Uso: /matricula ID (informe o número da matrícula).'],
                '/inscricao' => ['text' => 'Uso: /inscricao UUID (informe o identificador completo da inscrição).'],
                '/revisarinscricao' => ['text' => 'Uso: /revisarinscricao UUID (somente pré-análise; não aprova nem matricula).'],
                '/novoaviso' => ['text' => 'Uso: /novoaviso TÍTULO | MENSAGEM (a publicação exige confirmação).'],
                '/novaaula' => ['text' => 'Uso: /novaaula DISCIPLINA_ID | TÍTULO | URL_VIDEO | URL_PDF. Use - quando um dos materiais não existir.'],
                '/liberaraula' => ['text' => 'Uso: /liberaraula AULA_ID.'],
                '/concluirchamada' => ['text' => 'Uso: /concluirchamada CHAMADA_ID. Registre antes a frequência de todos os alunos ativos.'],
                '/novoevento' => ['text' => 'Uso: /novoevento TÍTULO | AAAA-MM-DD HH:MM | DESCRIÇÃO.'],
                '/novachamada' => ['text' => 'Uso: /novachamada TURMA_ID DISCIPLINA_ID AAAA-MM-DD.'],
                '/registrarpresenca' => ['text' => 'Uso: /registrarpresenca CHAMADA_ID MATRICULA_ID presente|falta|falta_justificada.'],
                '/filtro' => ['text' => 'Uso: /filtro status ativo, /filtro curso ID ou /filtro turma ID.'],
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
            $this->enviar($chatId, 'Acesso restrito. Somente o administrador autorizado pode usar este bot.', null, false);

            return;
        }

        $codigo = (string) ($callback['data'] ?? '');
        $consulta = app(ConsultasAcademicasTelegram::class);
        if (preg_match('/^(confirmar|cancelar):([0-9a-f]{32})$/D', $codigo, $m)) {
            $resposta = app(ConfirmacoesTelegram::class)->decidir($chatId, $m[1], $m[2], $this->updateAtual);
        } elseif (preg_match('/^fluxo:(novoaluno|voltar|pular|cancelar|preparar)$/D', $codigo, $m)) {
            $resposta = app(FluxoCadastroAlunoTelegram::class)->acao($chatId, $m[1], $this->updateAtual);
        } elseif (preg_match('/^area:(alunos|cursos|avaliacoes|comunicacao|relatorios|admin)$/D', $codigo, $m)) {
            $resposta = CatalogoComandosTelegram::menuArea($m[1]);
        } elseif ($codigo === 'orientar:buscar') {
            $resposta = ['text' => 'Para localizar um aluno, envie /buscar seguido do nome. Exemplo: /buscar Maria.',
                'reply_markup' => ['inline_keyboard' => [
                    [['text' => 'Voltar a alunos', 'callback_data' => 'area:alunos']],
                ]]];
        } elseif ($codigo === 'menuconsultas') {
            $resposta = $consulta->menu();
        } elseif ($codigo === 'administrar') {
            $resposta = CatalogoComandosTelegram::menuAdministrativo();
        } elseif (preg_match('/^operacao:([a-z]+)$/D', $codigo, $m)) {
            $resposta = CatalogoComandosTelegram::orientar($m[1]) ?? ['text' => 'Operação inválida. Use /administrar.'];
        } elseif ($codigo === 'menu') {
            app(FluxoCadastroAlunoTelegram::class)->cancelar($chatId);
            $resposta = CatalogoComandosTelegram::menuPrincipal();
        } elseif ($codigo === 'alertas') {
            $resposta = app(PreferenciasAlertasTelegram::class)->consultar($chatId);
        } elseif (in_array($codigo, ['filtros', 'hoje', 'relatorios', 'aulaspendentes', 'financeiro'], true)) {
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

        $novosMenus = in_array($codigo, ['hoje', 'relatorios', 'agenda7', 'aulaspendentes', 'filtros', 'alertas', 'financeiro'], true)
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

    private function enviar(string $chatId, string $texto, ?array $teclado = null, bool $protegida = true): void
    {
        if ($this->updateAtual === null) {
            throw new \LogicException('Resposta Telegram fora de update identificado.');
        }

        $vinculo = DB::table('telegram_vinculos')->where('chat_id', $chatId)->first();
        $usuario = $vinculo ? User::find($vinculo->user_id) : null;
        $userId = $this->podeOperar($usuario) ? (int) $usuario->id : null;
        $codigo = (string) ($this->callbackAtual['data'] ?? '');
        $editar = $userId !== null && $this->callbackAtual !== null && (
            in_array($codigo, ['hoje', 'relatorios', 'agenda7', 'aulaspendentes', 'filtros', 'alertas', 'financeiro'], true)
            || preg_match('/^(filtro|ficha|notas|frequencia):/', $codigo) === 1
        );

        app(TelegramOutbox::class)->registrar(
            $this->updateAtual, $chatId, $texto, $teclado, $userId,
            $editar ? $this->callbackAtual : null, $protegida
        );
    }
}
