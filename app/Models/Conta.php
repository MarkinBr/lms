<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'saldo_inicial',
        'tipo',
        'ativa',
    ];

    // --- ADICIONADO: Converte os dados automaticamente ---
    protected $casts = [
        'saldo_inicial' => 'decimal:2', // Garante que venha como número (ex: 150.00)
        'ativa' => 'boolean',           // Garante que venha como true/false
    ];

    // Relação antiga (podes manter se ainda tiveres dados na tabela despesas)
    public function despesas(): HasMany
    {
        return $this->hasMany(Despesa::class);
    }

    // --- ADICIONADO: Nova relação para o Módulo Financeiro ---
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoFinanceira::class);
    }
}