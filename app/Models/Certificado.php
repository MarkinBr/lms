<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Certificado extends Model
{
    use HasFactory;

    protected $table = 'certificados';

    protected $fillable = [
        'aluno_id',
        'curso_id',
        'matricula_id', // ADICIONADO: Importante para o vínculo correto
        'codigo_verificacao',
        'data_conclusao',
        'data_emissao',
        'arquivo',
        'carga_horaria',
    ];

    protected $casts = [
        'data_conclusao' => 'date',
        'data_emissao' => 'date',
        'carga_horaria' => 'integer',
    ];

    /**
     * Relacionamento: O certificado pertence a um Aluno (User).
     */
    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    /**
     * Relacionamento: O certificado pertence a um Curso.
     */
    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    /**
     * ADICIONADO: Relacionamento com a Matrícula.
     * Útil para saber exatamente de qual turma/período esse certificado veio.
     */
    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }
}