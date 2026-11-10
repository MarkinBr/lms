<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecretariaAssinatura extends Model
{
    protected $table = 'secretaria_assinaturas';

    protected $fillable = ['responsavel_id', 'cargo', 'arquivo_privado', 'mime', 'origem', 'ativa'];

    protected $casts = ['ativa' => 'boolean'];

    protected $hidden = ['arquivo_privado'];

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
}
