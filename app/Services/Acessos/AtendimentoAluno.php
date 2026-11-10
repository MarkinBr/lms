<?php

namespace App\Services\Acessos;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AtendimentoAluno
{
    public static function autorizar(User $ator, ?User $aluno = null): void
    {
        $atual = $ator->fresh();
        abort_unless($atual && $atual->ativo && in_array($atual->nivel, ['admin', 'secretario'], true), 403);
        if ($aluno !== null) {
            abort_unless($aluno->fresh()?->nivel === 'aluno', 403);
        }
    }

    public static function estado(User $aluno): string
    {
        $dados = $aluno->fresh()->getAttributes();
        ksort($dados);

        return hash('sha256', json_encode($dados, JSON_THROW_ON_ERROR));
    }

    public static function conferir(User $aluno, string $estado): void
    {
        if (! hash_equals(self::estado($aluno), $estado)) {
            throw ValidationException::withMessages(['estado' => 'O cadastro mudou durante o atendimento. Reabra a ficha e confira os dados.']);
        }
    }

    /**
     * Compartilha a reserva entre e-mail atual e pendente, até o commit/rollback.
     * Deve ser chamada dentro da transação, antes de consultar disponibilidade.
     */
    public static function reservarEmail(?string $email): void
    {
        $email = mb_strtolower(trim($email ?? ''));
        if ($email === '' || DB::getDriverName() !== 'pgsql') {
            return;
        }
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('A reserva de e-mail exige uma transação ativa.');
        }

        // Chave estável de 64 bits, sem transmitir o endereço em texto ao lock.
        $hash = substr(hash('sha256', 'cetsadp:cadastro:email:'.$email), 0, 16);
        $partes = unpack('N2', hex2bin($hash));
        $chaves = array_map(
            fn (int $parte): int => $parte > 2147483647 ? $parte - 4294967296 : $parte,
            array_values($partes),
        );
        DB::select('SELECT pg_advisory_xact_lock(?, ?)', $chaves);
    }

    public static function registrar(User $ator, User $aluno, string $acao, string $motivo, array $detalhes = []): void
    {
        $motivo = trim($motivo);
        if ($motivo === '' || mb_strlen($motivo) > 500) {
            throw ValidationException::withMessages(['motivo' => 'Informe o motivo do atendimento, em até 500 caracteres.']);
        }
        DB::table('acesso_auditorias')->insert([
            'ator_id' => $ator->id, 'aluno_id' => $aluno->id, 'acao' => $acao,
            'motivo' => $motivo, 'detalhes' => json_encode(array_intersect_key($detalhes, array_flip(['campos', 'canal', 'tipo', 'entrega_id'])), JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public static function revogar(User $aluno): void
    {
        $aluno->forceFill([
            'acesso_versao' => (int) $aluno->acesso_versao + 1,
            'remember_token' => Str::random(60),
            'email_confirmacao_token' => null, 'email_confirmacao_expira_em' => null,
        ])->save();
        if ($aluno->email) {
            DB::table('password_reset_tokens')->where('email', $aluno->email)->delete();
        }
    }
}
