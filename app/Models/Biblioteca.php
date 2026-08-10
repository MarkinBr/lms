<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Biblioteca extends Model
{
    use HasFactory;

    protected $table = 'biblioteca';

    protected $fillable = [
        'titulo',
        'descricao',
        'categoria',
        'arquivo',
        'publico',
        'downloads',
    ];

    protected $casts = [
        'publico' => 'boolean',
        'downloads' => 'integer',
    ];
}
