<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class TurmaDisciplina extends Model
{
    protected $table = 'turma_disciplinas';

    protected $fillable = ['turma_id', 'disciplina_id', 'periodo_letivo_id', 'ordem', 'ativa'];

    protected $casts = ['ativa' => 'boolean', 'ordem' => 'integer'];

    protected static function booted(): void
    {
        static::saving(function (self $vinculo): void {
            $turma = Turma::query()->find($vinculo->turma_id);
            $disciplina = Disciplina::query()->find($vinculo->disciplina_id);

            if (! $turma || ! $disciplina || (int) $turma->curso_id !== (int) $disciplina->curso_id) {
                throw ValidationException::withMessages(['disciplina_id' => 'A disciplina deve pertencer ao curso da turma.']);
            }

            if ($vinculo->periodo_letivo_id
                && (int) $turma->periodo_letivo_id !== (int) $vinculo->periodo_letivo_id
                && ! $turma->periodos()->whereKey($vinculo->periodo_letivo_id)->exists()) {
                throw ValidationException::withMessages(['periodo_letivo_id' => 'Vincule antes o período letivo à turma.']);
            }

            $duplicado = static::query()->where('turma_id', $vinculo->turma_id)
                ->where('disciplina_id', $vinculo->disciplina_id);
            if ($vinculo->exists) {
                $duplicado->whereKeyNot($vinculo->id);
            }
            if ($duplicado->exists()) {
                throw ValidationException::withMessages(['disciplina_id' => 'Esta disciplina já está vinculada à turma.']);
            }
        });
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function periodoLetivo(): BelongsTo
    {
        return $this->belongsTo(PeriodoLetivo::class);
    }
}
