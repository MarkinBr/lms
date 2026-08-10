<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Despesa extends Model
{
    use HasFactory;

    protected $fillable = [
        'descricao',
        'categoria_id',
        'valor',
        'data_vencimento',
        'data_pagamento',
        'fornecedor',
        'status',
        'comprovante',
        'conta_id',
    ];

    // A despesa pertence a uma categoria
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaFinanceira::class, 'categoria_id');
    }

    // A despesa sai de uma conta (Opcional, pois pode ser nulo)
    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }
}