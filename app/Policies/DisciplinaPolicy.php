<?php

namespace App\Policies;

use App\Models\Disciplina;
use App\Models\User;

class DisciplinaPolicy
{
    private function administrar(User $user): bool
    {
        return (bool) $user->ativo && in_array($user->nivel, ['admin', 'secretario'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->administrar($user);
    }

    public function view(User $user, Disciplina $disciplina): bool
    {
        return $this->administrar($user);
    }

    public function create(User $user): bool
    {
        return $this->administrar($user);
    }

    public function update(User $user, Disciplina $disciplina): bool
    {
        return $this->administrar($user);
    }

    public function delete(User $user, Disciplina $disciplina): bool
    {
        return $this->administrar($user) && ! $disciplina->turmas()->exists();
    }
}
