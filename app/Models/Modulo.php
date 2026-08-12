<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'curso_id',
        'titulo',
        'descricao',
        'ordem',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function aulas()
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
}
