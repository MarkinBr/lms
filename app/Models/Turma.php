<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Turma extends Model
{
    use HasFactory;

    public const STATUS_PLANEJADA = 'planejada';

    public const STATUS_ABERTA = 'aberta';

    public const STATUS_ENCERRADA = 'encerrada';

    public const STATUS_CANCELADA = 'cancelada';

    public const STATUS_PERMITIDOS = [
        self::STATUS_PLANEJADA,
        self::STATUS_ABERTA,
        self::STATUS_ENCERRADA,
        self::STATUS_CANCELADA,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PLANEJADA => 'Planejada',
        self::STATUS_ABERTA => 'Aberta',
        self::STATUS_ENCERRADA => 'Encerrada',
        self::STATUS_CANCELADA => 'Cancelada',
    ];

    public const MODALIDADE_PRESENCIAL = 'presencial';

    public const MODALIDADE_ONLINE = 'online';

    public const MODALIDADE_HIBRIDA = 'hibrido';

    public const MODALIDADE_SEMIPRESENCIAL = 'semipresencial';

    public const MODALIDADES = [
        self::MODALIDADE_PRESENCIAL,
        self::MODALIDADE_ONLINE,
        self::MODALIDADE_HIBRIDA,
        self::MODALIDADE_SEMIPRESENCIAL,
    ];

    public const MODALIDADE_LABELS = [
        self::MODALIDADE_PRESENCIAL => 'Presencial',
        self::MODALIDADE_ONLINE => 'Online',
        self::MODALIDADE_HIBRIDA => 'Híbrido',
        self::MODALIDADE_SEMIPRESENCIAL => 'Semipresencial',
    ];

    protected $fillable = [
        'curso_id',
        'periodo_letivo_id',
        'disciplina_id',
        'professor_id',
        'codigo',
        'capacidade',
        'modalidade',
        'status',
        'nome',
        'ano_letivo',
        'turno',
        'sala',
        'valor_total_curso',
        'qtd_parcelas',
        'data_inicio',
        'data_fim',
        'ativa',
    ];

    protected $casts = [
        'capacidade' => 'integer',
        'ativa' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Turma $turma) {
            if (filled($turma->status)) {
                if (! in_array($turma->status, self::STATUS_PERMITIDOS, true)) {
                    throw ValidationException::withMessages([
                        'status' => 'O status informado é inválido para a turma.',
                    ]);
                }

                $turma->ativa = ! in_array($turma->status, [self::STATUS_ENCERRADA, self::STATUS_CANCELADA], true);
            }

            if (filled($turma->modalidade) && ! in_array($turma->modalidade, self::MODALIDADES, true)) {
                throw ValidationException::withMessages([
                    'modalidade' => 'A modalidade informada é inválida.',
                ]);
            }

            if ($turma->capacidade !== null && $turma->capacidade !== '' && (int) $turma->capacidade < 1) {
                throw ValidationException::withMessages([
                    'capacidade' => 'A capacidade deve ser um inteiro positivo.',
                ]);
            }

            if ($turma->disciplina_id) {
                if (! $turma->curso_id) {
                    throw ValidationException::withMessages([
                        'curso_id' => 'Informe o curso da turma antes de selecionar a disciplina.',
                    ]);
                }

                $disciplina = Disciplina::query()->find($turma->disciplina_id);

                if (! $disciplina || (int) $disciplina->curso_id !== (int) $turma->curso_id) {
                    throw ValidationException::withMessages([
                        'disciplina_id' => 'A disciplina selecionada não pertence ao curso da turma.',
                    ]);
                }
            }

            if ($turma->professor_id) {
                $professor = User::query()->find($turma->professor_id);

                if (! $professor || $professor->nivel !== 'professor' || ! $professor->ativo) {
                    throw ValidationException::withMessages([
                        'professor_id' => 'O professor informado é inválido ou está inativo.',
                    ]);
                }
            }

            if (filled($turma->codigo) && filled($turma->periodo_letivo_id)) {
                $query = static::query()
                    ->where('periodo_letivo_id', $turma->periodo_letivo_id)
                    ->where('codigo', $turma->codigo);

                if ($turma->exists) {
                    $query->whereKeyNot($turma->getKey());
                }

                if ($query->exists()) {
                    throw ValidationException::withMessages([
                        'codigo' => 'Já existe uma turma com este código neste período letivo.',
                    ]);
                }
            }
        });
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function periodoLetivo(): BelongsTo
    {
        return $this->belongsTo(PeriodoLetivo::class);
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function matriculasTurmas(): BelongsToMany
    {
        return $this->belongsToMany(
            Matricula::class,
            'matricula_turma',
            'turma_id',
            'matricula_id'
        )
            ->withPivot('status', 'observacao')
            ->withTimestamps();
    }

    public function scopePorCurso(Builder $query, int $cursoId): Builder
    {
        return $query->where('curso_id', $cursoId);
    }

    public function scopePorPeriodoLetivo(Builder $query, int $periodoLetivoId): Builder
    {
        return $query->where('periodo_letivo_id', $periodoLetivoId);
    }

    public function scopePorDisciplina(Builder $query, int $disciplinaId): Builder
    {
        return $query->where('disciplina_id', $disciplinaId);
    }

    public function scopePorProfessor(Builder $query, int $professorId): Builder
    {
        return $query->where('professor_id', $professorId);
    }
}
