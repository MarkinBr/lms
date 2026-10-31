<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class IdentificadorAluno
{
    public function normalizar(string $valor): string
    {
        return mb_strtolower(trim($valor));
    }

    public function chaveLimite(string $valor): string
    {
        return $this->cpf($valor) ?? $this->normalizar($valor);
    }

    public function resolver(string $valor): ?User
    {
        $identificador = $this->normalizar($valor);
        $cpf = $this->cpf($identificador);
        $contas = User::query()->where(function (Builder $query) use ($identificador, $cpf) {
            $query->where('matricula_login', $identificador)
                ->orWhere(function (Builder $query) use ($identificador) {
                    $query->whereRaw('LOWER(TRIM(email)) = ?', [$identificador])->whereNotNull('email_verified_at');
                });
            if ($cpf !== null) {
                $query->orWhereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [$cpf]);
            }
        })->limit(2)->get();

        // Contar antes de filtrar perfil: uma conta inelegível também torna o acesso ambíguo.
        if ($contas->count() !== 1) {
            return null;
        }
        $aluno = $contas->sole();

        return $aluno->nivel === 'aluno' && $aluno->ativo ? $aluno : null;
    }

    public function porEmailConfirmado(string $email): ?User
    {
        $contas = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$this->normalizar($email)])->limit(2)->get();
        if ($contas->count() !== 1) {
            return null;
        }
        $aluno = $contas->sole();

        return $aluno->nivel === 'aluno' && $aluno->ativo && $aluno->email_verified_at !== null ? $aluno : null;
    }

    /** Busca uma conta única elegível para receber um link de primeiro acesso por e-mail.
     * A conta com credencial assistida pendente continua sob atendimento da Secretaria.
     */
    public function porEmailPrimeiroAcesso(string $email): ?User
    {
        $contas = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$this->normalizar($email)])
            ->limit(2)
            ->get();

        if ($contas->count() !== 1) {
            return null;
        }

        $aluno = $contas->sole();

        return $aluno->nivel === 'aluno'
            && $aluno->ativo
            && $aluno->primeiro_acesso_pendente
            && $aluno->email_verified_at === null
            && ! $aluno->senha_definitiva_pendente
            ? $aluno
            : null;
    }

    private function cpf(string $valor): ?string
    {
        if (! preg_match('/^[0-9.\-\s]+$/', trim($valor))) {
            return null;
        }
        $cpf = preg_replace('/[^0-9]/', '', $valor);

        return strlen($cpf) === 11 ? $cpf : null;
    }
}
