<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Financeiro extends Model
{
    use HasFactory;

    protected $table = 'financeiro';

    protected $fillable = [
        'aluno_id',
        'referencia',
        'descricao',
        'valor',
        'vencimento',
        'data_pagamento',
        'status',
        'forma_pagamento',
        'boleto_url',
        'pix_copia_cola',
        'comprovante',
        'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'vencimento' => 'date',
        'data_pagamento' => 'date',
    ];

    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }
}
