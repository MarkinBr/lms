<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ChamadaFrequencia extends Model
{
    use HasFactory;

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_CONCLUIDA = 'concluida';

    public const STATUS_PERMITIDOS = [
        self::STATUS_RASCUNHO,
        self::STATUS_CONCLUIDA,
    ];

    public const STATUS_LABELS = [
        self::STATUS_RASCUNHO => 'Rascunho',
        self::STATUS_CONCLUIDA => 'Concluída',
    ];

    protected $table = 'chamadas_frequencia';

    protected $fillable = [
        'turma_id',
        'disciplina_id',
        'data_aula',
        'titulo',
        'status',
        'criada_por',
        'concluida_por',
    ];

    protected $casts = [
        'data_aula' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (ChamadaFrequencia $chamada) {
            if (! in_array($chamada->status, self::STATUS_PERMITIDOS, true)) {
                throw ValidationException::withMessages([
                    'status' => 'O status informado é inválido para a chamada.',
                ]);
            }

            // Regra de servidor: a disciplina deve pertencer ao curso da turma.
            $turma = Turma::query()->find($chamada->turma_id);
            $disciplina = Disciplina::query()->find($chamada->disciplina_id);

            if (! $turma) {
                throw ValidationException::withMessages([
                    'turma_id' => 'A turma informada não existe.',
                ]);
            }

            if (! $disciplina) {
                throw ValidationException::withMessages([
                    'disciplina_id' => 'A disciplina informada não existe.',
                ]);
            }

            if ((int) $disciplina->curso_id !== (int) $turma->curso_id) {
                throw ValidationException::withMessages([
                    'disciplina_id' => 'A disciplina selecionada não pertence ao curso da turma.',
                ]);
            }

            if ($chamada->status === self::STATUS_CONCLUIDA && blank($chamada->concluida_por)) {
                throw ValidationException::withMessages([
                    'concluida_por' => 'Informe o responsável pela conclusão da chamada.',
                ]);
            }
        });
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function criadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criada_por');
    }

    public function concluidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'concluida_por');
    }

    public function frequencias(): HasMany
    {
        return $this->hasMany(Frequencia::class, 'chamada_frequencia_id');
    }

    public function scopeConcluidas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONCLUIDA);
    }

    public function scopeRascunhos(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RASCUNHO);
    }

    /**
     * Chamadas visíveis para o professor: apenas das turmas sob sua
     * responsabilidade. O professor não altera chamadas nesta Issue.
     */
    public function scopeParaProfessor(Builder $query, int $professorId): Builder
    {
        return $query->whereHas('turma', function (Builder $turmaQuery) use ($professorId) {
            $turmaQuery->where('professor_id', $professorId);
        });
    }

    /**
     * Matrículas com vínculo ativo à turma, considerando o legado
     * (`matriculas.turma_id`) e o vínculo atual (`matricula_turma` ativo),
     * sem duplicar a mesma matrícula.
     *
     * @return Collection<int, Matricula>
     */
    public function alunosAtivos(): Collection
    {
        $legados = Matricula::query()
            ->where('turma_id', $this->turma_id)
            ->ativas()
            ->get();

        $doPivot = Matricula::query()
            ->whereHas('turmasHistorico', function (Builder $q) {
                $q->where('turmas.id', $this->turma_id)
                    ->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO);
            })
            ->get();

        return $legados
            ->concat($doPivot)
            ->unique('id')
            ->sortBy(fn (Matricula $m) => mb_strtolower($m->user->name ?? ''))
            ->values();
    }

    /**
     * Percentual de presença da matrícula nesta turma/disciplina.
     * Conta apenas chamadas concluídas; falta justificada soma como presença.
     * Retorna 0 quando não houver chamada concluída aplicável.
     */
    public static function percentualDaMatricula(int $matriculaId, ?int $turmaId = null, ?int $disciplinaId = null): float
    {
        $concluidas = static::query()
            ->concluidas()
            ->when($turmaId, fn (Builder $q) => $q->where('turma_id', $turmaId))
            ->when($disciplinaId, fn (Builder $q) => $q->where('disciplina_id', $disciplinaId))
            ->whereHas('frequencias', fn (Builder $q) => $q->where('matricula_id', $matriculaId))
            ->count();

        if ($concluidas === 0) {
            return 0.0;
        }

        $presencas = Frequencia::query()
            ->where('matricula_id', $matriculaId)
            ->whereIn('situacao', [Frequencia::SITUACAO_PRESENTE, Frequencia::SITUACAO_FALTA_JUSTIFICADA])
            ->whereHas('chamada', function (Builder $q) use ($turmaId, $disciplinaId) {
                $q->concluidas()
                    ->when($turmaId, fn (Builder $qq) => $qq->where('turma_id', $turmaId))
                    ->when($disciplinaId, fn (Builder $qq) => $qq->where('disciplina_id', $disciplinaId));
            })
            ->count();

        return round(($presencas / $concluidas) * 100, 2);
    }
}
