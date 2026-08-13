<?php

namespace App\Policies;

use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;

class MatriculaPolicy
{
    private function administrar(User $user): bool
    {
        return (bool) $user->ativo && in_array($user->nivel, ['admin', 'secretario'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->administrar($user);
    }

    public function view(User $user, Matricula $matricula): bool
    {
        if (! $user->ativo) {
            return false;
        }

        if ($this->administrar($user)) {
            return true;
        }

        if ($user->nivel === 'aluno') {
            return (int) $matricula->user_id === (int) $user->id;
        }

        if ($user->nivel === 'professor') {
            if ($matricula->turma_id) {
                $turmaLegada = Turma::query()
                    ->whereKey($matricula->turma_id)
                    ->where('professor_id', $user->id)
                    ->exists();

                if ($turmaLegada) {
                    return true;
                }
            }

            return MatriculaTurma::query()
                ->where('matricula_id', $matricula->id)
                ->whereHas('turma', function ($query) use ($user) {
                    $query->where('professor_id', $user->id);
                })
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->administrar($user);
    }

    public function update(User $user, Matricula $matricula): bool
    {
        return $this->administrar($user);
    }

    public function delete(User $user, Matricula $matricula): bool
    {
        return $this->administrar($user);
    }
}
