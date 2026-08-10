<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Importante importar

class AulaAoVivo extends Model
{
    use HasFactory;

    protected $table = 'aulas_ao_vivo';

    protected $fillable = [
        'curso_id',
        'turma_id', // ADICIONADO: Necessário para o relacionamento funcionar
        'titulo',
        'descricao',
        'data_hora',
        'link_transmissao',
        'duracao',
        'gravacao_disponivel',
        'gravacao_url',
    ];

    protected $casts = [
        'data_hora' => 'datetime',
        'duracao' => 'integer',
        'gravacao_disponivel' => 'boolean',
    ];

    // Relacionamento com Curso (já existia)
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    // ADICIONADO: O relacionamento que estava faltando
    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }
}