<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ProfessorAtribuicao extends Model
{
    protected $table = 'professor_atribuicoes';

    public const ESCOPOS = [
        'turma' => 'Turma inteira',
        'disciplina' => 'Disciplina inteira',
        'modulo' => 'Módulo',
        'aula' => 'Aula específica',
        'instrumento' => 'Prova, teste ou avaliação',
    ];

    public const PERMISSOES = [
        'visualizar' => 'Visualizar',
        'editar_conteudo' => 'Editar conteúdo',
        'gerenciar_atividades' => 'Gerenciar atividades',
        'corrigir' => 'Corrigir avaliações',
        'lancar_notas' => 'Lançar notas',
    ];

    protected $fillable = [
        'turma_id', 'professor_id', 'escopo', 'disciplina_id', 'modulo_id',
        'aula_id', 'instrumento_id', 'permissoes', 'ativa',
    ];

    protected $casts = ['permissoes' => 'array', 'ativa' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $atribuicao): void {
            $turma = Turma::query()->find($atribuicao->turma_id);
            $professor = User::query()->find($atribuicao->professor_id);

            if (! $turma || ! $professor || $professor->nivel !== 'professor' || ! $professor->ativo) {
                throw ValidationException::withMessages(['professor_id' => 'Turma ou professor ativo inválido.']);
            }

            if (! array_key_exists($atribuicao->escopo, self::ESCOPOS)) {
                throw ValidationException::withMessages(['escopo' => 'Escopo de atribuição inválido.']);
            }

            $permissoes = $atribuicao->permissoes;
            if (! is_array($permissoes) || ! $permissoes
                || array_diff($permissoes, array_keys(self::PERMISSOES))) {
                throw ValidationException::withMessages(['permissoes' => 'Selecione permissões válidas.']);
            }

            $disciplinaId = $atribuicao->disciplina_id;
            if ($atribuicao->escopo !== 'turma') {
                if (! $disciplinaId ||
                    (! $turma->disciplinas()->whereKey($disciplinaId)->exists()
                    && (int) $turma->disciplina_id !== (int) $disciplinaId)) {
                    throw ValidationException::withMessages(['disciplina_id' => 'A disciplina precisa estar vinculada à turma.']);
                }
            }

            if (in_array($atribuicao->escopo, ['modulo', 'aula'], true)) {
                $disciplina = Disciplina::query()->findOrFail($disciplinaId);
                $moduloId = $atribuicao->modulo_id;

                if (! $moduloId || ((int) $disciplina->modulo_id !== (int) $moduloId
                    && ! $disciplina->modulos()->whereKey($moduloId)->exists())) {
                    throw ValidationException::withMessages(['modulo_id' => 'O módulo precisa estar vinculado à disciplina.']);
                }
            }

            if ($atribuicao->escopo === 'aula') {
                $aula = Aula::query()->find($atribuicao->aula_id);
                if (! $aula || (int) $aula->disciplina_id !== (int) $disciplinaId
                    || (int) $aula->modulo_id !== (int) $atribuicao->modulo_id) {
                    throw ValidationException::withMessages(['aula_id' => 'A aula deve pertencer à disciplina e ao módulo selecionados.']);
                }
            }

            if ($atribuicao->escopo === 'instrumento') {
                $instrumento = InstrumentoAvaliacao::query()->find($atribuicao->instrumento_id);
                if (! $instrumento || (int) $instrumento->disciplina_id !== (int) $disciplinaId) {
                    throw ValidationException::withMessages(['instrumento_id' => 'A avaliação não pertence à disciplina selecionada.']);
                }
            }

            // Evita escopos incongruentes, mesmo para entradas fora do formulário.
            if ($atribuicao->escopo === 'turma' && ($disciplinaId || $atribuicao->modulo_id || $atribuicao->aula_id || $atribuicao->instrumento_id)) {
                throw ValidationException::withMessages(['escopo' => 'Atribuição de turma inteira não aceita itens específicos.']);
            }
            if ($atribuicao->escopo === 'disciplina' && ($atribuicao->modulo_id || $atribuicao->aula_id || $atribuicao->instrumento_id)) {
                throw ValidationException::withMessages(['escopo' => 'Atribuição de disciplina não aceita itens específicos.']);
            }

            $inconsistentes = match ($atribuicao->escopo) {
                'turma' => false,
                'disciplina' => false,
                'modulo' => (bool) ($atribuicao->aula_id || $atribuicao->instrumento_id),
                'aula' => (bool) $atribuicao->instrumento_id,
                'instrumento' => (bool) ($atribuicao->modulo_id || $atribuicao->aula_id),
                default => true,
            };
            if ($inconsistentes) {
                throw ValidationException::withMessages([
                    'escopo' => 'A atribuição contém alvos de outra responsabilidade.',
                ]);
            }

            $duplicado = static::query()
                ->where('turma_id', $atribuicao->turma_id)
                ->where('professor_id', $atribuicao->professor_id)
                ->where('escopo', $atribuicao->escopo);
            foreach (['disciplina_id', 'modulo_id', 'aula_id', 'instrumento_id'] as $campo) {
                $duplicado->where($campo, $atribuicao->{$campo});
            }
            if ($atribuicao->exists) {
                $duplicado->whereKeyNot($atribuicao->id);
            }
            if ($duplicado->exists()) {
                throw ValidationException::withMessages(['professor_id' => 'Professor já designado para esse mesmo escopo.']);
            }
        });
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(InstrumentoAvaliacao::class, 'instrumento_id');
    }

    /**
     * Uma atribuição somente libera a operação explícita e seu objeto descendente.
     * O professor não recebe privilégios globais ao ser vinculado à turma.
     */
    public function permite(string $permissao, ?int $disciplinaId = null, ?int $moduloId = null, ?int $aulaId = null, ?int $instrumentoId = null): bool
    {
        if (! $this->ativa || ! in_array($permissao, $this->permissoes ?? [], true)) {
            return false;
        }

        return match ($this->escopo) {
            'turma' => true,
            'disciplina' => $disciplinaId && (int) $this->disciplina_id === $disciplinaId,
            'modulo' => $disciplinaId && $moduloId
                && (int) $this->disciplina_id === $disciplinaId && (int) $this->modulo_id === $moduloId,
            'aula' => $disciplinaId && $moduloId && $aulaId
                && (int) $this->disciplina_id === $disciplinaId
                && (int) $this->modulo_id === $moduloId && (int) $this->aula_id === $aulaId,
            'instrumento' => $disciplinaId && $instrumentoId
                && (int) $this->disciplina_id === $disciplinaId && (int) $this->instrumento_id === $instrumentoId,
            default => false,
        };
    }
}
