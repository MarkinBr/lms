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

    public const SITUACOES_ACADEMICAS = [
        'planejada' => 'Planejada',
        'em_andamento' => 'Em andamento',
        'concluida' => 'Concluída',
        'cancelada' => 'Cancelada',
        'arquivada' => 'Arquivada',
    ];

    public const SITUACOES_INSCRICOES = [
        'nao_iniciadas' => 'Não iniciadas',
        'abertas' => 'Abertas',
        'encerradas' => 'Encerradas',
        'suspensas' => 'Suspensas',
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
        'situacao_academica',
        'situacao_inscricoes',
        'publicada',
        'inscricoes_inicio',
        'inscricoes_fim',
    ];

    protected $casts = [
        'capacidade' => 'integer',
        'ativa' => 'boolean',
        'publicada' => 'boolean',
        'inscricoes_inicio' => 'date',
        'inscricoes_fim' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Turma $turma) {
            if (filled($turma->situacao_academica)) {
                if (! array_key_exists($turma->situacao_academica, self::SITUACOES_ACADEMICAS)) {
                    throw ValidationException::withMessages(['situacao_academica' => 'Situação acadêmica inválida.']);
                }
                if ($turma->isDirty('situacao_academica')) {
                    // Mantém consumidores legados da coluna status compatíveis.
                    $turma->status = match ($turma->situacao_academica) {
                        'em_andamento' => self::STATUS_ABERTA,
                        'concluida', 'arquivada' => self::STATUS_ENCERRADA,
                        'cancelada' => self::STATUS_CANCELADA,
                        default => self::STATUS_PLANEJADA,
                    };
                }
            } elseif ($turma->isDirty('status') && filled($turma->status)) {
                $turma->situacao_academica = match ($turma->status) {
                    self::STATUS_ABERTA => 'em_andamento',
                    self::STATUS_ENCERRADA => 'concluida',
                    self::STATUS_CANCELADA => 'cancelada',
                    default => 'planejada',
                };
            }

            if (filled($turma->situacao_inscricoes)
                && ! array_key_exists($turma->situacao_inscricoes, self::SITUACOES_INSCRICOES)) {
                throw ValidationException::withMessages(['situacao_inscricoes' => 'Situação de inscrições inválida.']);
            }
            if ($turma->inscricoes_inicio && $turma->inscricoes_fim
                && $turma->inscricoes_fim->lt($turma->inscricoes_inicio)) {
                throw ValidationException::withMessages(['inscricoes_fim' => 'O encerramento das inscrições deve ser posterior ao início.']);
            }
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

            if (blank($turma->modalidade) && ! $turma->exists) {
                $turma->modalidade = self::MODALIDADE_HIBRIDA;
            }

            if (
                filled($turma->modalidade)
                && $turma->modalidade !== self::MODALIDADE_HIBRIDA
                && (! $turma->exists || $turma->isDirty('modalidade'))
            ) {
                throw ValidationException::withMessages([
                    'modalidade' => 'Todas as turmas do CETSADP devem usar a modalidade híbrida.',
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

        static::saved(function (Turma $turma): void {
            // Espelha referências antigas nos vínculos múltiplos sem remover histórico.
            if ($turma->periodo_letivo_id) {
                $turma->periodos()->syncWithoutDetaching([$turma->periodo_letivo_id]);
            }
            if ($turma->disciplina_id) {
                $turma->disciplinas()->syncWithoutDetaching([$turma->disciplina_id]);
            }
            if ($turma->professor_id && ! $turma->professorAtribuicoes()
                ->where('professor_id', $turma->professor_id)->where('escopo', 'turma')->exists()) {
                $turma->professorAtribuicoes()->create([
                    'professor_id' => $turma->professor_id,
                    'escopo' => 'turma',
                    'permissoes' => ['visualizar', 'editar_conteudo', 'gerenciar_atividades', 'corrigir', 'lancar_notas'],
                    'ativa' => true,
                ]);
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

    public function periodos(): BelongsToMany
    {
        return $this->belongsToMany(PeriodoLetivo::class, 'turma_periodos', 'turma_id', 'periodo_letivo_id')->withTimestamps();
    }

    public function disciplinas(): BelongsToMany
    {
        return $this->belongsToMany(Disciplina::class, 'turma_disciplinas', 'turma_id', 'disciplina_id')
            ->withPivot(['ordem', 'ativa', 'periodo_letivo_id'])->withTimestamps();
    }

    public function turmaDisciplinas(): HasMany
    {
        return $this->hasMany(TurmaDisciplina::class);
    }

    public function turmaPeriodos(): HasMany
    {
        return $this->hasMany(TurmaPeriodo::class);
    }

    public function professorAtribuicoes(): HasMany
    {
        return $this->hasMany(ProfessorAtribuicao::class);
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
