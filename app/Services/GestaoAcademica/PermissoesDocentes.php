<?php

namespace App\Services\GestaoAcademica;

use App\Models\ProfessorAtribuicao;

class PermissoesDocentes
{
    public function permite(
        int $professorId,
        int $turmaId,
        string $permissao,
        ?int $disciplinaId = null,
        ?int $moduloId = null,
        ?int $aulaId = null,
        ?int $instrumentoId = null,
    ): bool {
        return ProfessorAtribuicao::query()
            ->where('professor_id', $professorId)
            ->where('turma_id', $turmaId)
            ->where('ativa', true)
            ->get()
            ->contains(fn (ProfessorAtribuicao $atribuicao) => $atribuicao->permite(
                $permissao, $disciplinaId, $moduloId, $aulaId, $instrumentoId
            ));
    }
}
