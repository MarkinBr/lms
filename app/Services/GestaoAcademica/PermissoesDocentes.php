<?php

namespace App\Services\GestaoAcademica;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\InstrumentoAvaliacao;
use App\Models\ProfessorAtribuicao;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Builder;

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
        $turma = Turma::query()->find($turmaId);
        if (! $turma || ! array_key_exists($permissao, ProfessorAtribuicao::PERMISSOES)) {
            return false;
        }

        // Atribuição para uma turma jamais pode alcançar outra turma ou curso.
        if ($disciplinaId && (int) $turma->disciplina_id !== $disciplinaId
            && ! $turma->disciplinas()->whereKey($disciplinaId)->exists()) {
            return false;
        }
        if ($moduloId && (! $disciplinaId || ! Disciplina::query()
            ->whereKey($disciplinaId)->whereHas('modulos', fn ($query) => $query->whereKey($moduloId))
            ->exists())) {
            return false;
        }
        if ($aulaId && (! $disciplinaId || ! Aula::query()
            ->whereKey($aulaId)->where('disciplina_id', $disciplinaId)
            ->when($moduloId, fn ($query) => $query->where('modulo_id', $moduloId))
            ->exists())) {
            return false;
        }
        if ($instrumentoId && (! $disciplinaId || ! InstrumentoAvaliacao::query()
            ->whereKey($instrumentoId)->where('disciplina_id', $disciplinaId)->exists())) {
            return false;
        }

        return ProfessorAtribuicao::query()
            ->where('professor_id', $professorId)
            ->where('turma_id', $turmaId)
            ->where('ativa', true)
            ->get()
            ->contains(fn (ProfessorAtribuicao $atribuicao) => $atribuicao->permite(
                $permissao, $disciplinaId, $moduloId, $aulaId, $instrumentoId
            ));
    }

    public function podeEditarAula(int $professorId, Aula $aula): bool
    {
        $disciplina = $aula->disciplina;
        if (! $disciplina) {
            return false;
        }
        if (Curso::query()->whereKey($disciplina->curso_id)
            ->where('instrutor_id', $professorId)->exists()) {
            return true; // Preserva o responsável geral pelo curso.
        }

        foreach ($disciplina->turmasVinculadas()->pluck('turmas.id') as $turmaId) {
            if ($this->permite($professorId, $turmaId, 'editar_conteudo',
                $disciplina->id, $aula->modulo_id, $aula->id)) {
                return true;
            }
        }

        return false;
    }

    public function podeCriarAula(int $professorId, Disciplina $disciplina, int $moduloId): bool
    {
        if (! $disciplina->modulos()->whereKey($moduloId)->exists()) {
            return false;
        }
        if (Curso::query()->whereKey($disciplina->curso_id)
            ->where('instrutor_id', $professorId)->exists()) {
            return true;
        }

        foreach ($disciplina->turmasVinculadas()->pluck('turmas.id') as $turmaId) {
            $atribuicoes = ProfessorAtribuicao::query()
                ->where('turma_id', $turmaId)->where('professor_id', $professorId)
                ->where('ativa', true)->whereIn('escopo', ['turma', 'disciplina', 'modulo'])->get();

            if ($atribuicoes->contains(fn (ProfessorAtribuicao $a) => $a->permite(
                'editar_conteudo', $disciplina->id, $moduloId
            ))) {
                return true;
            }
        }

        return false;
    }

    public function podeCriarEmAlgumLugar(int $professorId): bool
    {
        return Curso::query()->where('instrutor_id', $professorId)->exists()
            || ProfessorAtribuicao::query()->where('professor_id', $professorId)
                ->where('ativa', true)->whereIn('escopo', ['turma', 'disciplina', 'modulo'])
                ->whereJsonContains('permissoes', 'editar_conteudo')->exists();
    }

    public function disciplinasParaCriacao(int $professorId): array
    {
        $ids = [];
        $atribuicoes = ProfessorAtribuicao::query()->where('professor_id', $professorId)
            ->where('ativa', true)->whereIn('escopo', ['turma', 'disciplina', 'modulo'])
            ->whereJsonContains('permissoes', 'editar_conteudo')->with('turma')->get();

        foreach ($atribuicoes as $a) {
            if ($a->escopo === 'turma') {
                array_push($ids, ...$a->turma->disciplinas()->pluck('disciplinas.id')->all());
            } elseif ($a->disciplina_id) {
                $ids[] = $a->disciplina_id;
            }
        }

        return array_values(array_unique($ids));
    }

    public function filtrarAulasVisiveis(Builder $query, int $professorId): Builder
    {
        $atribuicoes = ProfessorAtribuicao::query()->where('professor_id', $professorId)
            ->where('ativa', true)->whereJsonContains('permissoes', 'visualizar')
            ->with('turma')->get();

        return $query->where(function (Builder $query) use ($professorId, $atribuicoes) {
            $query->whereHas('modulo.curso', fn ($curso) => $curso->where('instrutor_id', $professorId));
            foreach ($atribuicoes as $a) {
                if ($a->escopo === 'turma') {
                    $ids = $a->turma->disciplinas()->pluck('disciplinas.id')->all();
                    $query->orWhereIn('disciplina_id', $ids);
                } elseif ($a->escopo === 'disciplina') {
                    $query->orWhere('disciplina_id', $a->disciplina_id);
                } elseif ($a->escopo === 'modulo') {
                    $query->orWhere(fn (Builder $q) => $q->where('disciplina_id', $a->disciplina_id)
                        ->where('modulo_id', $a->modulo_id));
                } elseif ($a->escopo === 'aula') {
                    $query->orWhere('aulas.id', $a->aula_id);
                }
            }
        });
    }
}
