<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BancoQuestao extends Model
{
    protected $table = 'banco_questoes';

    protected $fillable = [
        'disciplina_id', 'aula_id', 'criada_por', 'tipo', 'dificuldade', 'enunciado',
        'alternativas', 'gabarito', 'explicacao', 'origem', 'referencia', 'status',
    ];

    protected $casts = ['alternativas' => 'array', 'gabarito' => 'array'];

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    public function instrumentos(): BelongsToMany
    {
        return $this->belongsToMany(InstrumentoAvaliacao::class, 'instrumento_questoes', 'questao_id', 'instrumento_id')
            ->withPivot(['ordem', 'pontos']);
    }
}
