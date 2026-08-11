<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Matricula extends Model
{
    use HasFactory;

    /**
     * Status canônicos utilizados pelo fluxo de aprovação/matrícula.
     * Valores em minúsculas para bater com os scopes existentes (LOWER()).
     */
    public const STATUS_ATIVA = 'ativa';
    public const STATUS_TRANCADA = 'trancada';
    public const STATUS_CONCLUIDA = 'concluida';
    public const STATUS_CANCELADA = 'cancelada';

    protected $fillable = [
        'user_id',
        'curso_id',
        'application_id', // vínculo com a inscrição de origem
        'turma_id',
        'data_matricula',
        'dia_vencimento',
        'status',
        'numero_matricula',
        'data_validade',
        'observacoes',
        'data_conclusao',
    ];

    protected $casts = [
        'data_matricula' => 'date',
        'data_validade'  => 'date',
        'data_conclusao' => 'date',
    ];

    public function mensalidades(): HasMany
    {
        return $this->hasMany(Mensalidade::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    /**
     * Inscrição (Application) que originou esta matrícula.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function certificado(): HasOne
    {
        return $this->hasOne(Certificado::class);
    }

    public function declaracoes(): HasMany
    {
        return $this->hasMany(Declaracao::class);
    }

    // BUG-M01: scope agrupado e case-insensitive
    public function scopeAtivas($query)
    {
        return $query->whereRaw("LOWER(COALESCE(status, '')) IN ('ativo','ativa')");
    }

    public function scopeExpiradas($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw("LOWER(COALESCE(status, '')) IN ('expirado','expirada')")
              ->orWhereDate('data_validade', '<', now(config('app.timezone', 'America/Sao_Paulo')));
        });
    }

    public function scopeCanceladas($query)
    {
        return $query->whereRaw("LOWER(COALESCE(status, '')) IN ('cancelado','cancelada','inativo','inativa')");
    }
}
