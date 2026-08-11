<?php

namespace App\Policies;

use App\Models\Turma;
use App\Models\User;

class TurmaPolicy
{
    private function administrar(User $user): bool
    {
        return (bool) $user->ativo && in_array($user->nivel, ['admin', 'secretario'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->administrar($user);
    }

    public function view(User $user, Turma $turma): bool
    {
        if (! $user->ativo) {
            return false;
        }

        if ($this->administrar($user)) {
            return true;
        }

        if ($user->nivel === 'professor') {
            return (int) $turma->professor_id === (int) $user->id;
        }

        if ($user->nivel === 'aluno') {
            return $turma->matriculasTurmas()
                ->where('matriculas.user_id', $user->id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->administrar($user);
    }

    public function update(User $user, Turma $turma): bool
    {
        return $this->administrar($user);
    }

    public function delete(User $user, Turma $turma): bool
    {
        return $this->administrar($user);
    }
}
