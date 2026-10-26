<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\AtivarAcessoAluno;
use App\Services\Telegram\TransporteEmailTelegram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use RuntimeException;

class AtivarAlunoTelegram implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public int $alunoId, public string $emailHash) {}

    public function backoff(): int
    {
        $broker = config('auth.defaults.passwords', 'users');

        return max(1, (int) config('auth.passwords.'.$broker.'.throttle', 60)) + 1;
    }

    public function handle(): void
    {
        if (DB::transactionLevel() > 0) {
            throw new RuntimeException('A ativação deve ser enviada fora da transação administrativa.');
        }
        $aluno = User::find($this->alunoId);
        if (! $aluno || ! $this->elegivel($aluno)) {
            return;
        }
        if (! app(TransporteEmailTelegram::class)->disponivel()) {
            throw new RuntimeException('Configure um serviço de envio de e-mail real antes de processar a ativação.');
        }
        $status = Password::broker()->sendResetLink([
            'id' => $aluno->id, 'email' => $aluno->email, 'nivel' => 'aluno',
            'ativo' => true, 'primeiro_acesso_pendente' => true, 'email_verified_at' => null,
        ], function (User $destinatario, string $token): void {
            // O broker consulta a conta novamente; valida também alterações antes da notificação.
            $atual = $destinatario->fresh();
            if ($atual && $this->elegivel($atual)) {
                $atual->notify(new AtivarAcessoAluno($token));
            }
        });
        if ($status !== Password::RESET_LINK_SENT) {
            throw new RuntimeException('Não foi possível enviar a ativação individual.');
        }
    }

    private function elegivel(User $aluno): bool
    {
        return $aluno->nivel === 'aluno' && $aluno->ativo && $aluno->primeiro_acesso_pendente
            && $aluno->email_verified_at === null && is_string($aluno->email)
            && filter_var($aluno->email, FILTER_VALIDATE_EMAIL) !== false
            && hash_equals($this->emailHash, hash('sha256', mb_strtolower(trim($aluno->email))))
            && User::whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($aluno->email))])->count() === 1;
    }
}
