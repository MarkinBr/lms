<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class AcervoDidatico extends Model
{
    protected $fillable = [
        'curso_id',
        'disciplina_id',
        'aula_id',
        'titulo',
        'tipo',
        'url',
        'categoria',
        'classificacao',
        'chave_importacao',
        'publicado',
    ];

    protected $casts = [
        'publicado' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $url = filter_var($item->url, FILTER_VALIDATE_URL);
            $scheme = $url ? strtolower((string) parse_url($url, PHP_URL_SCHEME)) : '';
            if ($scheme !== 'https') {
                throw ValidationException::withMessages([
                    'url' => 'É necessário informar uma URL HTTPS válida para o material.',
                ]);
            }

            if ($item->disciplina_id) {
                $disciplina = Disciplina::query()->find($item->disciplina_id);
                if (! $disciplina || (int) $disciplina->curso_id !== (int) $item->curso_id) {
                    throw ValidationException::withMessages([
                        'disciplina_id' => 'A disciplina não pertence ao curso selecionado.',
                    ]);
                }
            }

            if ($item->aula_id) {
                $aula = Aula::query()->find($item->aula_id);
                if (! $aula || ! $aula->disciplina_id
                    || (int) $aula->disciplina->curso_id !== (int) $item->curso_id
                    || ($item->disciplina_id && (int) $aula->disciplina_id !== (int) $item->disciplina_id)) {
                    throw ValidationException::withMessages([
                        'aula_id' => 'A aula deve pertencer à disciplina e ao curso selecionados.',
                    ]);
                }
            }

            if ($item->publicado && ! $item->curso_id) {
                throw ValidationException::withMessages([
                    'curso_id' => 'Defina o curso antes de disponibilizar o material aos alunos.',
                ]);
            }
        });
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }
}
