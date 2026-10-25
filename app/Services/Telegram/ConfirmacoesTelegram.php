<?php

namespace App\Services\Telegram;

use App\Models\Application;
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
                .'Válida por 10 minutos. Confirme ou cancele.',
            'reply_markup' => ['inline_keyboard' => [[
                ['text' => 'Confirmar', 'callback_data' => 'confirmar:'.$codigo],
                ['text' => 'Cancelar', 'callback_data' => 'cancelar:'.$codigo],
            ]]],
        ];
    }

    public function prepararRevisao(string $chatId, string $inscricaoId, int $updateId): array
    {
        $usuario = $this->dono($chatId);
        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/D', $inscricaoId) !== 1) {
            return ['text' => 'Identificador de inscrição inválido.'];
        }

        $inscricao = Application::query()->with(['curso:id,titulo', 'courseModality:id,nome'])->find($inscricaoId);
        if ($inscricao === null) {
            return ['text' => 'Inscrição não encontrada.'];
        }
        if (in_array($inscricao->status, [Application::STATUS_EM_ANALISE, Application::STATUS_APROVADO], true) === false) {
            return ['text' => 'Pré-análise não disponível para o estado atual. Consulte o painel.'];
        }

        $codigo = bin2hex(random_bytes(16));
        $id = DB::table('telegram_confirmacoes')->insertGetId([
            'user_id' => $usuario->id,
            'chat_id' => $chatId,
            'codigo_hash' => hash('sha256', $codigo),
            'acao' => 'revisao_inscricao',
            'application_id' => $inscricao->id,
            'estado_hash' => $this->estadoHash($inscricao),
            'status' => 'pendente',
            'expira_em' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('telegram_auditoria')->insert([
            'user_id' => $usuario->id,
            'confirmacao_id' => $id,
            'update_id' => $updateId,
            'acao' => 'revisao_inscricao',
            'antes' => 'inexistente',
            'depois' => 'pendente',
            'created_at' => now(),
        ]);

        $nome = mb_strimwidth(preg_replace('/[\r\n]+/u', ' ', $inscricao->nome_completo) ?? '', 0, 38, '…');

        return [
            'text' => 'Pré-análise de inscrição #'.$inscricao->id."\n"
                .'Candidato: '.$nome."\n"
                .'Curso: '.mb_strimwidth($inscricao->curso?->titulo ?? 'Não informado', 0, 40, '…')."\n"
                .'Modalidade: '.mb_strimwidth($inscricao->courseModality?->nome ?? 'Não informada', 0, 25, '…')."\n"
                .'Estado: '.$inscricao->status."\n"
                .'Documentos cadastrados: '.$inscricao->documents()->count()."\n"
                .'Registrar revisão NÃO aprova, rejeita ou matricula. Validade: 10 minutos.',
            'reply_markup' => ['inline_keyboard' => [[
                ['text' => 'Registrar revisão', 'callback_data' => 'confirmar:'.$codigo],
                ['text' => 'Cancelar', 'callback_data' => 'cancelar:'.$codigo],
            ]]],
        ];
    }

    private function estadoHash(Application $inscricao): string
    {
        return hash('sha256', implode('|', [
            $inscricao->getKey(),
            $inscricao->status,
            $inscricao->updated_at?->format('Y-m-d H:i:s.u'),
            $inscricao->curso_id,
            $inscricao->course_modality_id,
        ]));
    }

    public function decidir(string $chatId, string $acao, string $codigo, int $updateId): array
    {
        $usuario = $this->dono($chatId);
        if (in_array($acao, ['confirmar', 'cancelar'], true) === false || preg_match('/^[0-9a-f]{32}$/D', $codigo) !== 1) {
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
            if ($status === 'confirmado' && $linha->acao === 'revisao_inscricao') {
                $inscricao = Application::query()->whereKey($linha->application_id)->lockForUpdate()->first();
                if ($inscricao === null || $this->estadoHash($inscricao) !== $linha->estado_hash
                    || in_array($inscricao->status, [Application::STATUS_EM_ANALISE, Application::STATUS_APROVADO], true) === false) {
                    $status = 'obsoleto';
                }
            }
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
            'confirmado' => 'Revisão registrada (ou simulação confirmada). Nenhum dado acadêmico foi alterado.',
            'cancelado' => 'Simulação cancelada. Nenhum dado acadêmico foi alterado.',
            'expirado' => 'Confirmação expirada. Prepare uma nova revisão.',
            'obsoleto' => 'Os dados da inscrição mudaram. Prepare uma nova revisão antes de decidir.',
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
