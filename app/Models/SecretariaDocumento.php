<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecretariaDocumento extends Model
{
    protected $table = 'secretaria_documentos';

    public const MODELOS = [
        'classico' => 'Clássico institucional',
        'mosaico' => 'Mosaico azul',
        'solene' => 'Solene azul e dourado',
        'minimalista' => 'Institucional minimalista',
    ];

    public const TIPOS = [
        'declaracao_matricula' => 'Declaração de matrícula',
        'comprovante_matricula' => 'Comprovante de matrícula',
    ];

    protected $fillable = [
        'matricula_id', 'requerimento_id', 'preparado_por', 'revisado_por',
        'assinatura_id', 'tipo', 'modelo', 'status', 'finalidade',
        'dados_congelados', 'observacao_revisao', 'enviado_em', 'revisado_em',
    ];

    protected $casts = [
        'dados_congelados' => 'array',
        'enviado_em' => 'datetime',
        'revisado_em' => 'datetime',
    ];

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function requerimento(): BelongsTo
    {
        return $this->belongsTo(Requerimento::class);
    }

    public function preparadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preparado_por');
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function assinatura(): BelongsTo
    {
        return $this->belongsTo(SecretariaAssinatura::class, 'assinatura_id');
    }
}
