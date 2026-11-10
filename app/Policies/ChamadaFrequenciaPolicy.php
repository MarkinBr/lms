<?php

namespace App\Policies;

use App\Models\ChamadaFrequencia;
use App\Models\User;

class ChamadaFrequenciaPolicy
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

        return $this->administrar($user) || $user->nivel === 'professor';
    }

    public function view(User $user, ChamadaFrequencia $chamada): bool
    {
        if (! $user->ativo) {
            return false;
        }

        if ($this->administrar($user)) {
            return true;
        }

        if ($user->nivel === 'professor') {
            return (int) $chamada->turma?->professor_id === (int) $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->administrar($user);
    }

    public function update(User $user, ChamadaFrequencia $chamada): bool
    {
        return $this->administrar($user);
    }

    public function delete(User $user, ChamadaFrequencia $chamada): bool
    {
        return $this->administrar($user);
    }
}
