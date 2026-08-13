<?php

namespace App\Policies;

use App\Models\PeriodoLetivo;
use App\Models\User;

class PeriodoLetivoPolicy
{
    private function administrar(User $user): bool
    {
        return (bool) $user->ativo && in_array($user->nivel, ['admin', 'secretario'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->administrar($user);
    }

    public function view(User $user, PeriodoLetivo $periodoLetivo): bool
    {
        return $this->administrar($user);
    }

    public function create(User $user): bool
    {
        return $this->administrar($user);
    }

    public function update(User $user, PeriodoLetivo $periodoLetivo): bool
    {
        return $this->administrar($user);
    }

    public function delete(User $user, PeriodoLetivo $periodoLetivo): bool
    {
        return $this->administrar($user) && ! $periodoLetivo->turmas()->exists();
    }
}
