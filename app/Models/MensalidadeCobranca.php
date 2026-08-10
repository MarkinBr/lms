<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensalidadeCobranca extends Model
{
    protected $table = 'mensalidade_cobrancas';

    protected $fillable = [
        'mensalidade_id', 'tipo', 'canal', 'status',
        'conteudo', 'erro_detalhe', 'metadata',
        'disparado_por', 'enviado_em', 'lido_em',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'enviado_em' => 'datetime',
        'lido_em'    => 'datetime',
    ];

    public const TIPOS = [
        'lembrete_3dias'    => '📅 Lembrete (3 dias antes)',
        'cobranca_1dia'     => '⚠️  Cobrança (1 dia de atraso)',
        'cobranca_7dias'    => '🟠 Cobrança (7 dias de atraso)',
        'cobranca_15dias'   => '🔴 Cobrança (15 dias de atraso)',
        'cobranca_30dias'   => '⛔ Cobrança Final (30+ dias)',
        'manual'            => '👤 Manual',
    ];

    public function mensalidade(): BelongsTo
    {
        return $this->belongsTo(Mensalidade::class);
    }

    public function disparadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disparado_por');
    }

    public function marcarComoEnviado(?array $metadata = null): void
    {
        $this->update([
            'status'     => 'enviado',
            'enviado_em' => now(),
            'metadata'   => array_merge($this->metadata ?? [], $metadata ?? []),
        ]);
    }

    public function marcarComoErro(string $erro): void
    {
        $this->update(['status' => 'erro', 'erro_detalhe' => $erro]);
    }
}