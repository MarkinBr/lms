<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatriculaTurma extends Model
{
    public const STATUS_ATIVO = 'ativo';

    public const STATUS_TRANCADO = 'trancado';

    public const STATUS_CONCLUIDO = 'concluido';

    public const STATUS_CANCELADO = 'cancelado';

    public const STATUS_PERMITIDOS = [
        self::STATUS_ATIVO,
        self::STATUS_TRANCADO,
        self::STATUS_CONCLUIDO,
        self::STATUS_CANCELADO,
    ];

    public const STATUS_LABELS = [
        self::STATUS_ATIVO => 'Ativo',
        self::STATUS_TRANCADO => 'Trancado',
        self::STATUS_CONCLUIDO => 'Concluído',
        self::STATUS_CANCELADO => 'Cancelado',
    ];

    protected $table = 'matricula_turma';

    protected $fillable = [
        'matricula_id',
        'turma_id',
        'status',
        'observacao',
    ];

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }
}
