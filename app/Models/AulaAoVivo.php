<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Importante importar
use Illuminate\Validation\ValidationException;

class AulaAoVivo extends Model
{
    use HasFactory;

    public const MODALIDADE_ONLINE = 'online';

    public const MODALIDADE_PRESENCIAL = 'presencial';

    public const MODALIDADES = [
        self::MODALIDADE_ONLINE,
        self::MODALIDADE_PRESENCIAL,
    ];

    public const MODALIDADE_LABELS = [
        self::MODALIDADE_ONLINE => 'Online',
        self::MODALIDADE_PRESENCIAL => 'Presencial',
    ];

    public const STATUS_AGENDADA = 'agendada';

    public const STATUS_CANCELADA = 'cancelada';

    public const STATUS_REALIZADA = 'realizada';

    public const STATUS_PERMITIDOS = [
        self::STATUS_AGENDADA,
        self::STATUS_CANCELADA,
        self::STATUS_REALIZADA,
    ];

    public const STATUS_LABELS = [
        self::STATUS_AGENDADA => 'Agendada',
        self::STATUS_CANCELADA => 'Cancelada',
        self::STATUS_REALIZADA => 'Realizada',
    ];

    protected $table = 'aulas_ao_vivo';

    protected $fillable = [
        'curso_id',
        'turma_id', // ADICIONADO: Necessário para o relacionamento funcionar
        'titulo',
        'descricao',
        'data_hora',
        'link_transmissao',
        'duracao',
        'gravacao_disponivel',
        'gravacao_url',
        // Issue #25 — encontro híbrido (online/presencial) por turma
        'modalidade',
        'local',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'data_hora' => 'datetime',
        'duracao' => 'integer',
        'gravacao_disponivel' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (AulaAoVivo $aula) {
            if (filled($aula->modalidade) && ! in_array($aula->modalidade, self::MODALIDADES, true)) {
                throw ValidationException::withMessages([
                    'modalidade' => 'A modalidade informada é inválida.',
                ]);
            }

            if (filled($aula->status) && ! in_array($aula->status, self::STATUS_PERMITIDOS, true)) {
                throw ValidationException::withMessages([
                    'status' => 'O status informado é inválido para o encontro.',
                ]);
            }

            if ($aula->modalidade === self::MODALIDADE_ONLINE) {
                if (blank($aula->link_transmissao) || ! filter_var($aula->link_transmissao, FILTER_VALIDATE_URL)) {
                    throw ValidationException::withMessages([
                        'link_transmissao' => 'Encontros online exigem um link de transmissão válido.',
                    ]);
                }

                if (filled($aula->local)) {
                    throw ValidationException::withMessages([
                        'local' => 'Encontros online não devem ter local presencial preenchido.',
                    ]);
                }
            }

            if ($aula->modalidade === self::MODALIDADE_PRESENCIAL && blank($aula->local)) {
                throw ValidationException::withMessages([
                    'local' => 'Encontros presenciais exigem um local.',
                ]);
            }

            if (filled($aula->turma_id)) {
                $turma = Turma::query()->find($aula->turma_id);

                if (! $turma) {
                    throw ValidationException::withMessages([
                        'turma_id' => 'A turma informada não existe.',
                    ]);
                }

                if ($aula->curso_id && (int) $turma->curso_id !== (int) $aula->curso_id) {
                    throw ValidationException::withMessages([
                        'turma_id' => 'A turma selecionada não pertence ao curso informado.',
                    ]);
                }
            }
        });
    }

    // Relacionamento com Curso (já existia)
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    // ADICIONADO: O relacionamento que estava faltando
    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    /**
     * Marca o encontro como cancelado sem excluir o registro (mantém histórico).
     */
    public function cancelar(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELADA]);
    }

    public function isCancelada(): bool
    {
        return $this->status === self::STATUS_CANCELADA;
    }

    /**
     * Encontros das turmas sob responsabilidade do professor informado.
     */
    public function scopeParaProfessor(Builder $query, int $professorId): Builder
    {
        return $query->whereHas('turma', function (Builder $turmaQuery) use ($professorId) {
            $turmaQuery->where('professor_id', $professorId);
        });
    }

    /**
     * Encontros visíveis para o aluno: apenas da(s) turma(s) em que ele possui
     * MatriculaTurma ativa — nunca de outra turma, mesmo que o ID seja
     * conhecido. Por compatibilidade, também inclui encontros gerais do curso
     * (sem turma definida) quando o aluno tem alguma matrícula-turma ativa
     * naquele curso.
     */
    public function scopeParaAluno(Builder $query, User $aluno): Builder
    {
        $turmaIdsDoVinculoAtual = MatriculaTurma::query()
            ->where('status', MatriculaTurma::STATUS_ATIVO)
            ->whereHas('matricula', fn (Builder $matricula) => $matricula->where('user_id', $aluno->id))
            ->pluck('turma_id')
            ->all();

        // Compatibilidade com matrículas legadas, criadas antes da tabela
        // matricula_turma: nelas a turma ainda está em matriculas.turma_id.
        $turmaIdsLegadas = Matricula::query()
            ->ativas()
            ->where('user_id', $aluno->id)
            ->whereNotNull('turma_id')
            ->pluck('turma_id')
            ->all();

        $turmaIdsAtivas = array_values(array_unique([
            ...$turmaIdsDoVinculoAtual,
            ...$turmaIdsLegadas,
        ]));

        $cursoIdsAtivos = Turma::query()
            ->whereIn('id', $turmaIdsAtivas)
            ->pluck('curso_id')
            ->unique()
            ->all();

        return $query->where(function (Builder $escopo) use ($turmaIdsAtivas, $cursoIdsAtivos) {
            $escopo->whereIn('turma_id', $turmaIdsAtivas);

            if (! empty($cursoIdsAtivos)) {
                $escopo->orWhere(function (Builder $geral) use ($cursoIdsAtivos) {
                    $geral->whereNull('turma_id')->whereIn('curso_id', $cursoIdsAtivos);
                });
            }
        });
    }

    /**
     * Próximos encontros (não cancelados) a partir de hoje.
     */
    public function scopeProximas(Builder $query): Builder
    {
        return $query
            ->where('data_hora', '>=', now()->startOfDay())
            ->where(function (Builder $status) {
                $status->whereNull('status')->orWhere('status', '!=', self::STATUS_CANCELADA);
            })
            ->orderBy('data_hora');
    }
}
