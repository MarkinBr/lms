<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Requerimento extends Model
{
    use HasFactory;

    protected $fillable = [
        'aluno_id',
        'tipo',
        'assunto',
        'descricao',
        'anexos',
        'status',
        'resposta',
        'respondido_por',
        'respondido_em',
    ];

    protected $casts = [
        'anexos' => 'array',
        'respondido_em' => 'datetime',
    ];

    // Relacionamento com Aluno
    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    // Relacionamento com quem respondeu (Secretário/Admin)
    // Renomeado de 'respondente' para 'respondidoPor' para facilitar o uso no Filament
    public function respondidoPor()
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }
}