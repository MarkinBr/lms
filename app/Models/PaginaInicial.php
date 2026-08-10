<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaginaInicial extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'ativa' => 'boolean',
        'estatisticas' => 'array',
        'sobre_diferenciais' => 'array',
        'cursos_lista' => 'array',
        'cursos_categorias' => 'array',
        'metodologia_passos' => 'array',
        'depoimentos_lista' => 'array',
        'galeria_imagens' => 'array',
        'galeria_videos' => 'array',
        'blog_destaques' => 'array',
        'blog_ativo' => 'boolean',
        'parceiros_lista' => 'array',
        'newsletter_ativo' => 'boolean',
        'vantagens_lista' => 'array',
        'faq_lista' => 'array',
        'cta_flutuante_ativo' => 'boolean',
        'cores_personalizadas' => 'array',
        'modo_manutencao' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($pagina) {
            if ($pagina->ativa) {
                static::where('id', '!=', $pagina->id)->update(['ativa' => false]);
            }
        });
    }

    // Accessor para pegar a página ativa
    public static function ativa()
    {
        return static::where('ativa', true)->first();
    }

    // Accessor para cores personalizadas com fallback
    public function getCoresAttribute()
    {
        return $this->cores_personalizadas ?? [
            'primary' => '#003D7A',
            'secondary' => '#D62828',
            'accent' => '#F77F00',
        ];
    }
}
