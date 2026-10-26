<?php

namespace App\Services\Telegram;

use App\Jobs\NotificarDecisaoTelegram;
use App\Models\Application;
use App\Models\User;
use App\Services\AprovarInscricaoException;
use App\Services\AprovarInscricaoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class InscricoesAdministrativasTelegram
{
    public function preparar(string $chatId, string $comando, string $conteudo, int $updateId): array
    {
        $partes = array_map('trim', explode('|', $conteudo));
        $rejeitar = $comando === 'rejeitarinscricao';
        if (! in_array($comando, ['aprovarinscricao', 'rejeitarinscricao'], true)
            || ! Str::isUuid($partes[0] ?? '') || count($partes) !== ($rejeitar ? 2 : 1)
            || ($rejeitar && (mb_strlen($partes[1]) < 5 || mb_strlen($partes[1]) > 500
                || preg_match('/[\x00-\x1F\x7F]/u', $partes[1])))) {
            return ['text' => 'Uso: /aprovarinscricao UUID ou /rejeitarinscricao UUID | MOTIVO (5 a 500 caracteres).'];
        }

        return DB::transaction(function () use ($chatId, $updateId, $partes, $rejeitar) {
            $inscricao = Application::find($partes[0]);
            if (! $this->elegivel($inscricao)) {
                return ['text' => 'Inscrição indisponível para decisão. Consulte o painel.'];
            }
            $previa = ($rejeitar ? 'Rejeitar inscrição' : 'Aprovar inscrição e matricular').' #'.$inscricao->protocol
                .' • '.mb_strimwidth($inscricao->nome_completo, 0, 70, '…')
                .' • curso #'.$inscricao->curso_id;
            if ($rejeitar) {
                $previa .= "\nMotivo: ".$partes[1];
            }

            return app(ConfirmacoesTelegram::class)->prepararOperacao($chatId, $updateId,
                $rejeitar ? 'rejeitar_inscricao' : 'aprovar_inscricao', [
                    'inscricao_id' => $inscricao->id, 'estado_hash' => $this->snapshot($inscricao),
                    'motivo' => $rejeitar ? $partes[1] : null,
                ], $previa."\nA decisão altera o cadastro; a comunicação individual será processada em fila.",
                $rejeitar ? 'Rejeitar inscrição' : 'Aprovar e matricular');
        });
    }

    public function executar(string $acao, array $dados, User $usuario): ?Model
    {
        $inscricao = Application::whereKey($dados['inscricao_id'])->lockForUpdate()->first();
        if (! $this->elegivel($inscricao)
            || ! hash_equals($dados['estado_hash'] ?? '', $this->snapshot($inscricao))) {
            return null;
        }
        if ($acao === 'aprovar_inscricao') {
            $identidades = User::where(function ($query) use ($inscricao) {
                $query->where('cpf', $inscricao->cpf)
                    ->orWhereRaw('LOWER(email) = ?', [mb_strtolower(trim($inscricao->email))]);
            })->orderBy('id')->lockForUpdate()->get();
            if ($identidades->contains(fn (User $user) => $user->nivel !== 'aluno' || ! $user->ativo)) {
                return null;
            }
        }

        try {
            // Savepoint: conflitos de identidade não deixam efeitos parciais na confirmação.
            return DB::transaction(function () use ($acao, $dados, $usuario, $inscricao) {
                $servico = app(AprovarInscricaoService::class);
                if ($acao === 'aprovar_inscricao') {
                    $resultado = $servico->executar($inscricao, $usuario, notificar: false);
                    $recurso = $resultado->matricula;
                    if ($resultado->user->primeiro_acesso_pendente && ! $resultado->user->email_verified_at) {
                        app(AlunosAdministrativosTelegram::class)->enfileirarAtivacao($resultado->user);
                    }
                } else {
                    $recurso = $servico->rejeitar($inscricao, $usuario, $dados['motivo'], notificar: false);
                }
                $atualizada = $inscricao->fresh();
                // A fila database compartilha a mesma transação: worker só vê o job após commit.
                Queue::connection('database')->push(new NotificarDecisaoTelegram(
                    $inscricao->id, $atualizada->status,
                    hash('sha256', mb_strtolower(trim($atualizada->email))),
                ));

                return $recurso;
            });
        } catch (AprovarInscricaoException) {
            return null;
        }
    }

    private function elegivel(?Application $inscricao): bool
    {
        return $inscricao !== null
            && in_array($inscricao->status, [Application::STATUS_EM_ANALISE, Application::STATUS_APROVADO], true)
            && $inscricao->curso?->ativo && $inscricao->courseModality?->ativo
            && (int) $inscricao->courseModality->curso_id === (int) $inscricao->curso_id;
    }

    private function snapshot(Application $inscricao): string
    {
        return hash('sha256', json_encode([
            $inscricao->getAttributes(),
            $inscricao->documents()->orderBy('id')->get()->map->getAttributes()->all(),
            $inscricao->curso?->getAttributes(), $inscricao->courseModality?->getAttributes(),
        ], JSON_THROW_ON_ERROR));
    }
}
