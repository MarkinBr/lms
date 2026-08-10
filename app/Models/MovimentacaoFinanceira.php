<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimentacaoFinanceira extends Model
{
    use SoftDeletes;

    protected $table = 'movimentacoes_financeiras';

    protected $fillable = [
        'tipo', 'conta_id', 'categoria_id', 'mensalidade_id',
        'descricao', 'valor', 'data_movimentacao', 'status',
        'beneficiario', 'pagador', 'comprovante', 'observacoes',
        'criado_por', 'alterado_por', 'data_alteracao',
        'efetivado', // BUG-C13 — flag para unique parcial
    ];

    // BUG-B01: datetime em vez de date
    protected $casts = [
        'data_movimentacao' => 'datetime',
        'data_alteracao'    => 'datetime',
        'valor'             => 'decimal:2',
        'efetivado'         => 'boolean',
    ];

    public function conta(): BelongsTo         { return $this->belongsTo(Conta::class); }
    public function categoria(): BelongsTo     { return $this->belongsTo(CategoriaFinanceira::class); }
    public function mensalidade(): BelongsTo   { return $this->belongsTo(Mensalidade::class); }
    public function criadoPor(): BelongsTo     { return $this->belongsTo(User::class, 'criado_por'); }
    public function alteradoPor(): BelongsTo   { return $this->belongsTo(User::class, 'alterado_por'); }
    public function auditorias(): HasMany      { return $this->hasMany(MovimentacaoAuditoria::class, 'movimentacao_id'); }

    public function registrarAuditoria(string $acao, ?array $dadosAnteriores = null, ?string $motivo = null): void
    {
        $this->auditorias()->create([
            'usuario_id'       => auth()->id() ?? 1,
            'acao'             => $acao,
            'dados_anteriores' => $dadosAnteriores,
            'dados_novos'      => $this->toArray(),
            'motivo'           => $motivo,
        ]);
    }
}