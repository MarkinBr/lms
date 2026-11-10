<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InstrumentoAvaliacao extends Model
{
    protected $table = 'instrumentos_avaliacao';

    protected $fillable = [
        'disciplina_id', 'aula_id', 'criado_por', 'tipo', 'titulo', 'instrucoes',
        'tempo_limite_minutos', 'tentativas_permitidas', 'embaralhar', 'status',
    ];

    protected $casts = [
        'embaralhar' => 'boolean',
        'tempo_limite_minutos' => 'integer',
        'tentativas_permitidas' => 'integer',
    ];

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    public function questoes(): BelongsToMany
    {
        return $this->belongsToMany(BancoQuestao::class, 'instrumento_questoes', 'instrumento_id', 'questao_id')
            ->withPivot(['ordem', 'pontos'])->orderByPivot('ordem');
    }
}
