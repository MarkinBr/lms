<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que identifica o curso pelo subdomínio e injeta as configurações visuais.
 * 
 * Uso: Registrar no Kernel ou bootstrap/app.php
 *      ->withMiddleware(function (Middleware $middleware) {
 *          $middleware->alias(['curso.identify' => IdentifyCurso::class]);
 *      })
 */
class IdentifyCurso
{
    /**
     * Configurações visuais de cada curso
     */
    private array $cursos = [
        'diaconato' => [
            'id'          => 'diaconato',
            'nome'        => 'Habilitação ao Diaconato',
            'nome_curto'  => 'Diaconato',
            'subdomain'   => 'diaconato.cetsadp.com.br',
            'icone'       => 'fa-church',
            'emoji'       => '⛪',
            'cor_primaria'   => '#0A2463',
            'cor_secundaria' => '#1E3A8A',
            'cor_accent'     => '#C9A84C',
            'gradient'       => 'from-[#0A2463] via-[#1E3A8A] to-[#1e40af]',
            'bg_class'       => 'bg-blue-900',
            'text_class'     => 'text-blue-900',
            'border_class'   => 'border-blue-900',
            'descricao'   => 'Formação completa para o ministério diaconal, fundamentada nas Escrituras Sagradas.',
            'citacao'     => '"Buscai, pois, irmãos, dentre vós, sete varões de boa reputação." — Atos 6:3',
        ],
        'lideranca' => [
            'id'          => 'lideranca',
            'nome'        => 'Liderança Cristã Ministerial',
            'nome_curto'  => 'Liderança',
            'subdomain'   => 'lideranca.cetsadp.com.br',
            'icone'       => 'fa-crown',
            'emoji'       => '👑',
            'cor_primaria'   => '#065F46',
            'cor_secundaria' => '#047857',
            'cor_accent'     => '#C9A84C',
            'gradient'       => 'from-[#065F46] via-[#047857] to-[#059669]',
            'bg_class'       => 'bg-emerald-800',
            'text_class'     => 'text-emerald-800',
            'border_class'   => 'border-emerald-800',
            'descricao'   => 'Desenvolvimento de líderes cristãos comprometidos com a visão ministerial.',
            'citacao'     => '"O maior entre vós será vosso servo." — Mateus 23:11',
        ],
        'teologia' => [
            'id'          => 'teologia',
            'nome'        => 'Médio em Teologia',
            'nome_curto'  => 'Teologia',
            'subdomain'   => 'teologia.cetsadp.com.br',
            'icone'       => 'fa-book-bible',
            'emoji'       => '📖',
            'cor_primaria'   => '#78350F',
            'cor_secundaria' => '#92400E',
            'cor_accent'     => '#F5E6CA',
            'gradient'       => 'from-[#78350F] via-[#92400E] to-[#B45309]',
            'bg_class'       => 'bg-amber-800',
            'text_class'     => 'text-amber-800',
            'border_class'   => 'border-amber-800',
            'descricao'   => 'Estudo aprofundado das Escrituras e da Teologia Sistemática.',
            'citacao'     => '"Examinem as Escrituras, porque pensam ter nelas a vida eterna." — João 5:39',
        ],
        'capelania' => [
            'id'          => 'capelania',
            'nome'        => 'Capelania',
            'nome_curto'  => 'Capelania',
            'subdomain'   => 'capelania.cetsadp.com.br',
            'icone'       => 'fa-dove',
            'emoji'       => '🕊️',
            'cor_primaria'   => '#7F1D1D',
            'cor_secundaria' => '#991B1B',
            'cor_accent'     => '#E8B4B8',
            'gradient'       => 'from-[#7F1D1D] via-[#991B1B] to-[#DC2626]',
            'bg_class'       => 'bg-red-900',
            'text_class'     => 'text-red-900',
            'border_class'   => 'border-red-900',
            'descricao'   => 'Capacitação em cuidado pastoral, hospitalar e institucional.',
            'citacao'     => '"Consolai, consolai o meu povo, diz o vosso Deus." — Isaías 40:1',
        ],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0] ?? null;

        if ($subdomain && isset($this->cursos[$subdomain])) {
            $curso = $this->cursos[$subdomain];
            
            // Compartilha com todas as views
            view()->share('curso', $curso);
            view()->share('cursoId', $curso['id']);
            
            // Disponibiliza no request para controllers
            $request->merge(['_curso' => $curso]);
            
            // Armazena no config para acesso global
            config(['cetsadp.curso_atual' => $curso]);
        }

        return $next($request);
    }

    /**
     * Retorna configuração de um curso específico
     */
    public static function getCurso(string $id): ?array
    {
        return (new self())->cursos[$id] ?? null;
    }

    /**
     * Retorna todos os cursos
     */
    public static function getAllCursos(): array
    {
        return (new self())->cursos;
    }
}
