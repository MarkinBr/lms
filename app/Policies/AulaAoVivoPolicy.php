<?php

namespace App\Policies;

use App\Models\AulaAoVivo;
use App\Models\User;

class AulaAoVivoPolicy
{
    private function administrar(User $user): bool
    {
        return (bool) $user->ativo && in_array($user->nivel, ['admin', 'secretario'], true);
    }

    public function viewAny(User $user): bool
    {
        if (! $user->ativo) {
            return false;
        }

        return $this->administrar($user) || in_array($user->nivel, ['professor', 'aluno'], true);
    }

    public function view(User $user, AulaAoVivo $aulaAoVivo): bool
    {
        if (! $user->ativo) {
            return false;
        }

        if ($this->administrar($user)) {
            return true;
        }

        if ($user->nivel === 'professor') {
            return AulaAoVivo::query()
                ->whereKey($aulaAoVivo->getKey())
                ->paraProfessor((int) $user->id)
                ->exists();
        }

        if ($user->nivel === 'aluno') {
            return AulaAoVivo::query()
                ->whereKey($aulaAoVivo->getKey())
                ->paraAluno($user)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->administrar($user);
    }

    public function update(User $user, AulaAoVivo $aulaAoVivo): bool
    {
        return $this->administrar($user);
    }

    public function delete(User $user, AulaAoVivo $aulaAoVivo): bool
    {
        return $this->administrar($user);
    }
}
