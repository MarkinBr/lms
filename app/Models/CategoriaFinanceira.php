<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaFinanceira extends Model
{
    use HasFactory;

    // Define explicitamente o nome da tabela (opcional, mas bom para garantir)
    protected $table = 'categoria_financeiras';

    protected $fillable = [
        'nome',
        'tipo', // 'Despesa' ou 'Receita'
        'ativa',
    ];

    // --- ADICIONADO: Converte o campo 'ativa' para boolean automaticamente ---
    protected $casts = [
        'ativa' => 'boolean',
    ];

    // Relação antiga (mantemos para não quebrar nada antigo)
    public function despesas(): HasMany
    {
        return $this->hasMany(Despesa::class, 'categoria_id');
    }

    // --- ADICIONADO: Nova relação para o Módulo Financeiro ---
    // Isso permite gerar relatórios como "Quanto gastei em 'Material de Escritório'?"
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoFinanceira::class, 'categoria_id');
    }
}