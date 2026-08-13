<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Frequencia extends Model
{
    use HasFactory;

    public const SITUACAO_PRESENTE = 'presente';

    public const SITUACAO_FALTA = 'falta';

    public const SITUACAO_FALTA_JUSTIFICADA = 'falta_justificada';

    public const SITUACOES_PERMITIDAS = [
        self::SITUACAO_PRESENTE,
        self::SITUACAO_FALTA,
        self::SITUACAO_FALTA_JUSTIFICADA,
    ];

    public const SITUACAO_LABELS = [
        self::SITUACAO_PRESENTE => 'Presente',
        self::SITUACAO_FALTA => 'Falta',
        self::SITUACAO_FALTA_JUSTIFICADA => 'Falta justificada',
    ];

    protected $table = 'frequencias';

    protected $fillable = [
        'chamada_frequencia_id',
        'matricula_id',
        'situacao',
        'observacao',
        'registrado_por',
    ];

    protected static function booted(): void
    {
        static::saving(function (Frequencia $frequencia) {
            if (! in_array($frequencia->situacao, self::SITUACOES_PERMITIDAS, true)) {
                throw ValidationException::withMessages([
                    'situacao' => 'A situação informada é inválida para a frequência.',
                ]);
            }

            // Regra de servidor: só entra na chamada matrícula com vínculo
            // ativo à turma da chamada (legado ou pivot).
            $chamada = $frequencia->relationLoaded('chamada')
                ? $frequencia->chamada
                : ChamadaFrequencia::query()->find($frequencia->chamada_frequencia_id);

            if (! $chamada) {
                throw ValidationException::withMessages([
                    'chamada_frequencia_id' => 'A chamada informada não existe.',
                ]);
            }

            $matricula = Matricula::query()->find($frequencia->matricula_id);

            if (! $matricula) {
                throw ValidationException::withMessages([
                    'matricula_id' => 'A matrícula informada não existe.',
                ]);
            }

            $vinculoLegado = (int) $matricula->turma_id === (int) $chamada->turma_id
                && in_array(mb_strtolower((string) $matricula->status), ['ativo', 'ativa'], true);

            $vinculoPivot = MatriculaTurma::query()
                ->where('matricula_id', $matricula->id)
                ->where('turma_id', $chamada->turma_id)
                ->where('status', MatriculaTurma::STATUS_ATIVO)
                ->exists();

            if (! $vinculoLegado && ! $vinculoPivot) {
                throw ValidationException::withMessages([
                    'matricula_id' => 'Somente alunos com matrícula ativa nesta turma podem constar na chamada.',
                ]);
            }
        });
    }

    public function chamada(): BelongsTo
    {
        return $this->belongsTo(ChamadaFrequencia::class, 'chamada_frequencia_id');
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
