<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'data_validade' => 'date',
        'data_conclusao' => 'date',
    ];

    public function mensalidades(): HasMany
    {
        return $this->hasMany(Mensalidade::class);
    }

    /**
     * Frequências (presenças/faltas) registradas para esta matrícula.
     */
    public function frequencias(): HasMany
    {
        return $this->hasMany(Frequencia::class);
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
     * Turmas acadêmicas associadas à matrícula.
     *
     * O relacionamento legado turma() permanece em uso para manter
     * compatibilidade com matrículas antigas.
     */
    public function turmasHistorico(): BelongsToMany
    {
        return $this->belongsToMany(Turma::class, 'matricula_turma')
            ->withPivot('status', 'observacao')
            ->withTimestamps();
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

    /**
     * Resumo de frequência da matrícula por chamadas concluídas.
     * Pode ser recortado por turma e/ou disciplina. Nunca gera dados:
     * somente leitura. Percentual = (presentes + faltas justificadas) /
     * chamadas concluídas aplicáveis * 100; 0 quando não houver chamada.
     *
     * @return array{chamadas_concluidas:int, presentes:int, faltas:int, faltas_justificadas:int, percentual:float}
     */
    public function resumoFrequencia(?int $turmaId = null, ?int $disciplinaId = null): array
    {
        $concluidas = ChamadaFrequencia::query()
            ->concluidas()
            ->when($turmaId, fn ($q) => $q->where('turma_id', $turmaId))
            ->when($disciplinaId, fn ($q) => $q->where('disciplina_id', $disciplinaId))
            ->whereHas('frequencias', fn ($q) => $q->where('matricula_id', $this->id))
            ->count();

        $base = $this->frequencias()
            ->whereHas('chamada', function ($q) use ($turmaId, $disciplinaId) {
                $q->concluidas()
                    ->when($turmaId, fn ($qq) => $qq->where('turma_id', $turmaId))
                    ->when($disciplinaId, fn ($qq) => $qq->where('disciplina_id', $disciplinaId));
            });

        $presentes = (clone $base)->where('situacao', Frequencia::SITUACAO_PRESENTE)->count();
        $faltas = (clone $base)->where('situacao', Frequencia::SITUACAO_FALTA)->count();
        $faltasJustificadas = (clone $base)->where('situacao', Frequencia::SITUACAO_FALTA_JUSTIFICADA)->count();

        $percentual = $concluidas > 0
            ? round((($presentes + $faltasJustificadas) / $concluidas) * 100, 2)
            : 0.0;

        return [
            'chamadas_concluidas' => $concluidas,
            'presentes' => $presentes,
            'faltas' => $faltas,
            'faltas_justificadas' => $faltasJustificadas,
            'percentual' => $percentual,
        ];
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
