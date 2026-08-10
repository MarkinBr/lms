<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AulaProgresso extends Model
{
    use HasFactory;

    protected $table = 'aula_progresso';

    protected $fillable = [
        'aluno_id',
        'aula_id',
        'concluida',
        'ultima_visualizacao',
        'tempo_assistido',
    ];

    protected $casts = [
        'concluida' => 'boolean',
        'ultima_visualizacao' => 'datetime',
        'tempo_assistido' => 'integer',
    ];

    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }
}
