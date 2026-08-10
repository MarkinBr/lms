<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Turma extends Model
{
    use HasFactory;

    protected $fillable = [
        'curso_id', // <--- Importante: Adicionado para permitir salvar
        'nome',
        'ano_letivo',
        'turno',
        'sala',
        'valor_total_curso',
        'qtd_parcelas',
        'data_inicio',
        'data_fim',
        'ativa',
    ];

    // --- RELACIONAMENTO NOVO ---
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }
    // ---------------------------

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }
}