<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Modulo extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (Modulo $modulo): void {
            if ($modulo->isDirty('curso_id') && ($modulo->disciplinas()->exists() || $modulo->aulas()->exists())) {
                throw ValidationException::withMessages([
                    'curso_id' => 'Não é possível mover um módulo que possui disciplinas ou aulas.',
                ]);
            }
        });

        static::deleting(function (Modulo $modulo): void {
            if (! $modulo->podeSerExcluido()) {
                throw ValidationException::withMessages([
                    'id' => 'Não é possível excluir um módulo que possui disciplinas ou aulas.',
                ]);
            }
        });
    }

    protected $fillable = [
        'curso_id',
        'titulo',
        'descricao',
        'ordem',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class)->orderBy('ordem');
    }

    /**
     * Disciplinas vinculadas a este módulo curricular.
     *
     * Ordenadas pela ordem global da disciplina no curso para refletir
     * a sequência da matriz curricular.
     */
    public function disciplinas(): HasMany
    {
        return $this->hasMany(Disciplina::class)->orderBy('ordem');
    }

    public function podeSerExcluido(): bool
    {
        return ! $this->disciplinas()->exists() && ! $this->aulas()->exists();
    }
}
