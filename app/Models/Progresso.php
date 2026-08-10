<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Progresso extends Model
{
    use HasFactory;

    // Define que este model usa a tabela 'progressos'
    protected $table = 'progressos';

    // Campos que podem ser preenchidos (Mass Assignment)
    protected $fillable = [
        'user_id',
        'aula_id',
        'progresso_porcentagem',
        'concluida',
        'tempo_estudo_minutos',
        'notas',
        'quiz_score',
        'quiz_respostas',
        'quiz_tentativas',
        'quiz_concluido_em',
        'data_conclusao',
    ];

    // Converte automaticamente JSON para Array e Datas para Carbon
    protected $casts = [
        'concluida' => 'boolean',
        'quiz_respostas' => 'array',
        'quiz_tentativas' => 'array',
        'quiz_concluido_em' => 'datetime',
        'data_conclusao' => 'datetime',
    ];

    // Relacionamento com o Usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento com a Aula
    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }
}