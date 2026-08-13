<?php

namespace App\Services;

use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VincularMatriculaTurmaService
{
    public function executar(
        Matricula $matricula,
        Turma $turma,
        string $status = MatriculaTurma::STATUS_ATIVO,
        ?string $observacao = null,
    ): MatriculaTurma {
        return DB::transaction(function () use ($matricula, $turma, $status, $observacao) {
            $matricula = Matricula::query()
                ->whereKey($matricula->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $turma = Turma::query()
                ->whereKey($turma->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($status, MatriculaTurma::STATUS_PERMITIDOS, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status inválido para o vínculo matrícula/turma.',
                ]);
            }

            if (! $matricula->curso_id || ! $turma->curso_id) {
                throw ValidationException::withMessages([
                    'curso_id' => 'Matrícula e turma precisam ter curso definido para vínculo.',
                ]);
            }

            if ((int) $matricula->curso_id !== (int) $turma->curso_id) {
                throw ValidationException::withMessages([
                    'turma_id' => 'A turma não pertence ao mesmo curso da matrícula.',
                ]);
            }

            $vinculoExistente = MatriculaTurma::query()
                ->where('matricula_id', $matricula->id)
                ->where('turma_id', $turma->id)
                ->exists();

            if ($vinculoExistente) {
                throw ValidationException::withMessages([
                    'matricula_id' => 'Esta matrícula já está vinculada a esta turma.',
                ]);
            }

            return MatriculaTurma::query()->create([
                'matricula_id' => $matricula->id,
                'turma_id' => $turma->id,
                'status' => $status,
                'observacao' => $observacao,
            ]);
        });
    }
}
