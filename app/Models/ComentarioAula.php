<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComentarioAula extends Model
{
    use HasFactory;

    protected $table = 'comentarios_aula';

    protected $fillable = [
        'aula_id',
        'user_id',
        'parent_id',
        'comentario',
        'aprovado',
    ];

    protected $casts = [
        'aprovado' => 'boolean',
    ];

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function respostas()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
