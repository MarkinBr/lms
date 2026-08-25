<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Disciplina extends Model
{
    use HasFactory;

    protected $fillable = [
        'curso_id',
        'modulo_id',
        'codigo',
        'nome',
        'descricao',
        'carga_horaria',
        'ordem',
        'ativa',
    ];

    protected $casts = [
        'carga_horaria' => 'integer',
        'ordem' => 'integer',
        'ativa' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Disciplina $disciplina) {
            if (blank($disciplina->codigo)) {
                throw ValidationException::withMessages([
                    'codigo' => 'Informe o código da disciplina.',
                ]);
            }

            if (filled($disciplina->codigo) && filled($disciplina->curso_id)) {
                $query = static::query()
                    ->where('curso_id', $disciplina->curso_id)
                    ->where('codigo', $disciplina->codigo);

                if ($disciplina->exists) {
                    $query->whereKeyNot($disciplina->getKey());
                }

                if ($query->exists()) {
                    throw ValidationException::withMessages([
                        'codigo' => 'Já existe uma disciplina com este código neste curso.',
                    ]);
                }
            }

            if ($disciplina->carga_horaria === null || (int) $disciplina->carga_horaria < 1) {
                throw ValidationException::withMessages([
                    'carga_horaria' => 'A carga horária deve ser um inteiro positivo.',
                ]);
            }

            if ($disciplina->ordem === null || (int) $disciplina->ordem < 1) {
                throw ValidationException::withMessages([
                    'ordem' => 'A ordem deve ser um inteiro positivo.',
                ]);
            }

            if ($disciplina->exists && ($disciplina->isDirty('modulo_id') || $disciplina->isDirty('curso_id'))) {
                if ($disciplina->aulas()->exists() || $disciplina->turmas()->exists()) {
                    throw ValidationException::withMessages([
                        'modulo_id' => 'Não é possível mover uma disciplina que possui aulas ou turmas.',
                    ]);
                }
            }

            if (! filled($disciplina->modulo_id)) {
                if (! $disciplina->exists || $disciplina->isDirty('modulo_id')) {
                    throw ValidationException::withMessages([
                        'modulo_id' => 'Informe o módulo da disciplina.',
                    ]);
                }

                return;
            }

            $modulo = Modulo::query()->find($disciplina->modulo_id);

            if (! $modulo || (int) $modulo->curso_id !== (int) $disciplina->curso_id) {
                throw ValidationException::withMessages([
                    'modulo_id' => 'O módulo selecionado não pertence ao curso da disciplina.',
                ]);
            }
        });

        static::deleting(function (Disciplina $disciplina) {
            if ($disciplina->aulas()->exists()) {
                throw ValidationException::withMessages([
                    'id' => 'Não é possível excluir uma disciplina com aulas vinculadas.',
                ]);
            }

            if ($disciplina->turmas()->exists()) {
                throw ValidationException::withMessages([
                    'id' => 'Não é possível excluir uma disciplina com turmas vinculadas.',
                ]);
            }
        });
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class)->orderBy('ordem');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativa', true);
    }

    public function scopePorCurso(Builder $query, int $cursoId): Builder
    {
        return $query->where('curso_id', $cursoId);
    }

    public function scopePorModulo(Builder $query, int $moduloId): Builder
    {
        return $query->where('modulo_id', $moduloId);
    }

    public function podeSerExcluida(): bool
    {
        return ! $this->turmas()->exists() && ! $this->aulas()->exists();
    }
}
