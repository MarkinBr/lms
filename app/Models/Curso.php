<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Curso extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'titulo',
        'descricao',
        'capa',
        'categoria',
        'nivel',
        'duracao_total',
        'valor',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'duracao_total' => 'integer',
        'valor' => 'decimal:2',
    ];

    // --- RELACIONAMENTOS ---

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    public function modulos()
    {
        return $this->hasMany(Modulo::class)->orderBy('ordem');
    }

    // === RELACIONAMENTO ADICIONADO PARA CORRIGIR O ERRO ===
    public function aulas(): HasManyThrough
    {
        // Um Curso tem muitas Aulas ATRAVÉS dos Módulos
        return $this->hasManyThrough(Aula::class, Modulo::class);
    }
    // ======================================================

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }

    public function alunos()
    {
        return $this->belongsToMany(User::class, 'matriculas', 'curso_id', 'aluno_id')
            ->withPivot('status', 'data_matricula', 'data_validade')
            ->withTimestamps();
    }

    public function aulasAoVivo()
    {
        return $this->hasMany(AulaAoVivo::class);
    }

    // Acessor mantido (agora pode usar o relacionamento acima se quiser)
    public function getTotalAulasAttribute()
    {
        // Opção otimizada usando o novo relacionamento:
        return $this->aulas()->count();
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}