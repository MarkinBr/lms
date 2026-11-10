<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class TurmaPeriodo extends Model
{
    protected $table = 'turma_periodos';

    protected $fillable = ['turma_id', 'periodo_letivo_id'];

    protected static function booted(): void
    {
        static::saving(function (self $vinculo): void {
            if (! Turma::query()->whereKey($vinculo->turma_id)->exists()
                || ! PeriodoLetivo::query()->whereKey($vinculo->periodo_letivo_id)->exists()) {
                throw ValidationException::withMessages(['periodo_letivo_id' => 'Turma ou período inexistente.']);
            }

            $duplicado = static::query()->where('turma_id', $vinculo->turma_id)
                ->where('periodo_letivo_id', $vinculo->periodo_letivo_id);
            if ($vinculo->exists) {
                $duplicado->whereKeyNot($vinculo->id);
            }
            if ($duplicado->exists()) {
                throw ValidationException::withMessages(['periodo_letivo_id' => 'Esse período já está vinculado à turma.']);
            }
        });
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function periodoLetivo(): BelongsTo
    {
        return $this->belongsTo(PeriodoLetivo::class);
    }
}
