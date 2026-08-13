<?php

namespace App\Policies;

use App\Models\Frequencia;
use App\Models\User;

class FrequenciaPolicy
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

    public function view(User $user, Frequencia $frequencia): bool
    {
        if (! $user->ativo) {
            return false;
        }

        if ($this->administrar($user)) {
            return true;
        }

        // Aluno só enxerga a própria frequência.
        if ($user->nivel === 'aluno') {
            return (int) $frequencia->matricula?->user_id === (int) $user->id;
        }

        // Professor só enxerga frequências de chamadas das próprias turmas.
        if ($user->nivel === 'professor') {
            return (int) $frequencia->chamada?->turma?->professor_id === (int) $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->administrar($user);
    }

    public function update(User $user, Frequencia $frequencia): bool
    {
        return $this->administrar($user);
    }

    public function delete(User $user, Frequencia $frequencia): bool
    {
        return $this->administrar($user);
    }
}
