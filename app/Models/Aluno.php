<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aluno extends Model
{
    use HasFactory;

    // A LISTA VIP (Fillable)
    // Só estes campos podem ser salvos. Qualquer outro será bloqueado.
    protected $fillable = [
        'nome',
        'email',
        'cpf',
        'telefone',
        'data_nascimento',
        'endereco',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'nome_responsavel',
        'telefone_responsavel',
        'cpf_responsavel',
        'ativo',
    ];
    public function matriculas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Matricula::class);
    }
}