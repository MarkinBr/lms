<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransacaoTeste extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload_completo' => 'array',
    ];
}