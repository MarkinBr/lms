<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Noticia extends Model
{
    protected $table = 'noticias';

    protected $fillable = [
        'titulo', 'resumo', 'texto', 'publicada', 'publicada_em', 'publicada_por',
    ];

    protected $casts = [
        'publicada' => 'boolean',
        'publicada_em' => 'datetime',
    ];

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('publicada', true)->where('publicada_em', '<=', now());
    }
}
