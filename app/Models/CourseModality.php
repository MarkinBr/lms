<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseModality extends Model
{
    protected $fillable = [
        'curso_id',
        'nome',
        'descricao',
        'valor_total',
        'numero_parcelas',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'valor_total' => 'decimal:2',
        'numero_parcelas' => 'integer',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }
}
