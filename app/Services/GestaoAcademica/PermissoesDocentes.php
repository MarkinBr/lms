<?php

namespace App\Services\GestaoAcademica;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\InstrumentoAvaliacao;
use App\Models\ProfessorAtribuicao;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
        // Os IDs enviados precisam descrever o mesmo caminho persistido.
        // Nunca use um módulo/aula alegado para autorizar outra avaliação.
        if ($instrumentoId) {
            $instrumento = InstrumentoAvaliacao::query()->find($instrumentoId);
            if (! $instrumento || ! $disciplinaId || (int) $instrumento->disciplina_id !== $disciplinaId
                || ($aulaId && (int) $instrumento->aula_id !== $aulaId)
                || ($moduloId && ! $instrumento->aula_id)) {
                return false;
            }
            $aulaId = $instrumento->aula_id ? (int) $instrumento->aula_id : null;
        }
        if ($aulaId) {
            $aula = Aula::query()->find($aulaId);
            if (! $aula || ! $disciplinaId || (int) $aula->disciplina_id !== $disciplinaId
                || ($moduloId && (int) $aula->modulo_id !== $moduloId)) {
                return false;
            }
            $moduloId = (int) $aula->modulo_id;
        }
        if ($disciplinaId && ! Disciplina::query()->whereKey($disciplinaId)
            ->where('curso_id', $turma->curso_id)->exists()) {
            return false;
        }
        if ($moduloId && (! $disciplinaId || ! ($disciplina = Disciplina::query()->find($disciplinaId))
            || ! $this->moduloValido($disciplina, $moduloId))) {
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
            ->contains(fn (ProfessorAtribuicao $atribuicao) => $this->atribuicaoValida($atribuicao) && $atribuicao->permite(
                $permissao, $disciplinaId, $moduloId, $aulaId, $instrumentoId
            ));
    }

    public function podeEditarAula(int $professorId, Aula $aula): bool
    {
        // Autoriza a origem persistida, nunca relações/atributos alterados em memória.
        $aula = $aula->exists ? Aula::query()->find($aula->getKey()) : null;
        $disciplina = $aula?->disciplina;
        if (! $disciplina || ! $this->moduloValido($disciplina, (int) $aula->modulo_id)) {
            return false;
        }
        if (Curso::query()->whereKey($disciplina->curso_id)
            ->where('instrutor_id', $professorId)->exists()) {
            return true; // Preserva o responsável geral pelo curso.
        }

        $turmas = $this->turmasDaDisciplina($disciplina);
        // Atribuições locais não concedem edição da biblioteca compartilhada.
        // Até existir versionamento por oferta, só o responsável do curso a altera.
        if ($turmas->count() !== 1) {
            return false;
        }
        foreach ($turmas as $turmaId) {
            if ($this->permite($professorId, $turmaId, 'editar_conteudo',
                $disciplina->id, $aula->modulo_id, $aula->id)) {
                return true;
            }
        }

        return false;
    }

    public function podeCriarAula(int $professorId, Disciplina $disciplina, int $moduloId): bool
    {
        $disciplina = Disciplina::query()->find($disciplina->getKey());
        if (! $disciplina || ! $this->moduloValido($disciplina, $moduloId)) {
            return false;
        }
        if (Curso::query()->whereKey($disciplina->curso_id)
            ->where('instrutor_id', $professorId)->exists()) {
            return true;
        }

        $turmas = $this->turmasDaDisciplina($disciplina);
        if ($turmas->count() !== 1) {
            return false;
        }
        foreach ($turmas as $turmaId) {
            $atribuicoes = ProfessorAtribuicao::query()
                ->where('turma_id', $turmaId)->where('professor_id', $professorId)
                ->where('ativa', true)->whereIn('escopo', ['turma', 'disciplina', 'modulo'])->get();

            if ($atribuicoes->contains(fn (ProfessorAtribuicao $a) => $a->permite(
                'editar_conteudo', $disciplina->id, $moduloId
            )) && $this->permite($professorId, $turmaId, 'editar_conteudo', $disciplina->id, $moduloId)) {
                return true;
            }
        }

        return false;
    }

    public function podeCriarEmAlgumLugar(int $professorId): bool
    {
        return $this->disciplinasParaCriacao($professorId) !== [];
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

        array_push($ids, ...Disciplina::query()->whereHas('curso', fn ($q) => $q
            ->where('instrutor_id', $professorId))->pluck('id')->all());

        return Disciplina::query()->whereIn('id', array_unique($ids))->get()
            ->filter(fn (Disciplina $d) => $d->modulos()->pluck('modulos.id')
                ->contains(fn ($id) => $this->podeCriarAula($professorId, $d, (int) $id)))
            ->pluck('id')->all();
    }

    private function moduloValido(Disciplina $disciplina, int $moduloId): bool
    {
        return $disciplina->modulos()->whereKey($moduloId)
            ->where('modulos.curso_id', $disciplina->curso_id)->exists();
    }

    private function turmasDaDisciplina(Disciplina $disciplina): Collection
    {
        // Inclui referências legadas: omiti-las tornaria conteúdo reutilizado editável.
        return Turma::query()->where('disciplina_id', $disciplina->id)
            ->orWhereHas('disciplinas', fn ($q) => $q->whereKey($disciplina->id))
            ->pluck('id');
    }

    private function atribuicaoValida(ProfessorAtribuicao $atribuicao): bool
    {
        $turma = $atribuicao->turma;
        if (! $turma) {
            return false;
        }
        if ($atribuicao->escopo === 'turma') {
            return true;
        }
        $disciplina = Disciplina::query()->find($atribuicao->disciplina_id);
        if (! $disciplina || (int) $disciplina->curso_id !== (int) $turma->curso_id
            || ((int) $turma->disciplina_id !== (int) $disciplina->id
                && ! $turma->disciplinas()->whereKey($disciplina->id)->exists())) {
            return false;
        }
        if (in_array($atribuicao->escopo, ['modulo', 'aula'], true)
            && ! $this->moduloValido($disciplina, (int) $atribuicao->modulo_id)) {
            return false;
        }
        if ($atribuicao->escopo === 'aula') {
            return Aula::query()->whereKey($atribuicao->aula_id)
                ->where('disciplina_id', $disciplina->id)->where('modulo_id', $atribuicao->modulo_id)->exists();
        }
        if ($atribuicao->escopo === 'instrumento') {
            return InstrumentoAvaliacao::query()->whereKey($atribuicao->instrumento_id)
                ->where('disciplina_id', $disciplina->id)->exists();
        }

        return in_array($atribuicao->escopo, ['disciplina', 'modulo'], true);
    }

    public function filtrarAulasVisiveis(Builder $query, int $professorId): Builder
    {
        $atribuicoes = ProfessorAtribuicao::query()->where('professor_id', $professorId)
            ->where('ativa', true)->whereJsonContains('permissoes', 'visualizar')
            ->with('turma')->get();

        return $query->where(function (Builder $query) use ($professorId, $atribuicoes) {
            $query->whereHas('modulo.curso', fn ($curso) => $curso->where('instrutor_id', $professorId));
            foreach ($atribuicoes as $a) {
                if (! $this->atribuicaoValida($a)) {
                    continue;
                }
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
