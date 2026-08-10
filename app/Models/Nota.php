<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // CORRIGIDO: Era 'aluno_id', agora é 'user_id' para bater com o banco
        'disciplina',
        'periodo',
        'professor',
        'nota_av1',
        'nota_av2',
        'nota_av3',
        'media_final',
        'faltas',
        'situacao',
        'observacoes',
    ];

    protected $casts = [
        'nota_av1' => 'decimal:2',
        'nota_av2' => 'decimal:2',
        'nota_av3' => 'decimal:2',
        'media_final' => 'decimal:2',
        'faltas' => 'integer',
    ];

    public function aluno()
    {
        // CORRIGIDO: A chave estrangeira agora é 'user_id'
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($nota) {
            $av1 = $nota->nota_av1 ?? 0;
            $av2 = $nota->nota_av2 ?? 0;
            $av3 = $nota->nota_av3 ?? 0;

            if ($av3 > 0) {
                $av1 = max($av1, $av3);
                $av2 = max($av2, $av3);
            }

            $nota->media_final = ($av1 + $av2) / 2;

            if ($nota->media_final >= 7 && $nota->faltas <= 15) {
                $nota->situacao = 'Aprovado';
            } elseif ($nota->faltas > 15) {
                $nota->situacao = 'Reprovado por Falta';
            } elseif ($nota->media_final < 5) {
                $nota->situacao = 'Reprovado';
            } else {
                // Caso não caia nas condições acima (ex: média entre 5 e 7), fica em recuperação/exame
                $nota->situacao = 'Recuperação'; 
            }
        });
    }
}