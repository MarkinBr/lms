<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aviso extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'mensagem',
        'tipo',
        'ativo',
        'data_expiracao',
        'data_publicacao',
        'data_evento',
        'anexo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'data_expiracao' => 'date',
        'data_publicacao' => 'datetime',
        'data_evento' => 'date',
    ];
}
