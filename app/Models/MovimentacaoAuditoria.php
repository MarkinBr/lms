<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoAuditoria extends Model
{
    protected $table = 'movimentacoes_auditoria';

    protected $fillable = [
        'movimentacao_id',
        'usuario_id',
        'acao',
        'dados_anteriores',
        'dados_novos',
        'motivo',
    ];

    protected $casts = [
        'dados_anteriores' => 'array',
        'dados_novos' => 'array',
    ];

    public function movimentacao(): BelongsTo
    {
        return $this->belongsTo(MovimentacaoFinanceira::class, 'movimentacao_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}