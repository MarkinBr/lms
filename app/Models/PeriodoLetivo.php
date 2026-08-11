<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class PeriodoLetivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'data_inicio',
        'data_fim',
        'ativo',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'ativo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (PeriodoLetivo $periodo) {
            if ($periodo->data_inicio && $periodo->data_fim && $periodo->data_fim->lt($periodo->data_inicio)) {
                throw ValidationException::withMessages([
                    'data_fim' => 'A data final deve ser posterior ou igual à data inicial.',
                ]);
            }

            if ($periodo->ativo) {
                $query = static::query()->where('ativo', true);

                if ($periodo->exists) {
                    $query->whereKeyNot($periodo->getKey());
                }

                if ($query->exists()) {
                    throw ValidationException::withMessages([
                        'ativo' => 'Já existe um período letivo ativo.',
                    ]);
                }
            }
        });

        static::deleting(function (PeriodoLetivo $periodo) {
            if ($periodo->turmas()->exists()) {
                throw ValidationException::withMessages([
                    'id' => 'Não é possível excluir um período letivo com turmas vinculadas.',
                ]);
            }
        });
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeInativos(Builder $query): Builder
    {
        return $query->where('ativo', false);
    }

    public function podeSerExcluido(): bool
    {
        return ! $this->turmas()->exists();
    }
}
