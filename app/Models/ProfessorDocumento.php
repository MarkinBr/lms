<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorDocumento extends Model
{
    use HasFactory;

    // Define quais campos podem ser salvos no banco
    protected $fillable = [
        'user_id', // ID do professor dono do documento
        'titulo',  // Nome do arquivo (ex: RG, CPF, Diploma)
        'caminho', // Onde o arquivo está salvo na pasta storage
    ];

    // Relacionamento: Todo documento pertence a um Professor (User)
    public function professor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}