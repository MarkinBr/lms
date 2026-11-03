<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceiroEnvio extends Model
{
    protected $table = 'financeiro_envios';

    protected $fillable = [
        'mensalidade_id', 'criado_por', 'canal', 'modelo', 'status',
        'destinatario', 'agendado_para', 'enviado_em', 'erro',
    ];

    protected $casts = [
        'agendado_para' => 'datetime',
        'enviado_em' => 'datetime',
    ];

    public function mensalidade(): BelongsTo
    {
        return $this->belongsTo(Mensalidade::class);
    }
}
