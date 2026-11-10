<?php

namespace App\Support\GestaoAcademica;

use App\Filament\Pages\GestaoAcademica;
use App\Filament\Resources\AulaAoVivoResource;
use App\Filament\Resources\CursoResource;
use App\Filament\Resources\DisciplinaResource;
use App\Filament\Resources\MatriculaResource;
use App\Filament\Resources\ModuloResource;
use App\Filament\Resources\PeriodoLetivoResource;
use App\Filament\Resources\PessoaResource;
use App\Filament\Resources\TurmaResource;
use App\Filament\Resources\UserResource;

/**
 * Issue #61 — Fonte única da navegação do shell acadêmico.
 *
 * Todos os links usam rotas reais do painel /admin via Resource::getUrl(),
 * sem URLs fixas de outros painéis, sem JavaScript fictício e sem dados
 * inventados. O item ativo é determinado pela página/rota atual.
 */
class NavegacaoAcademica
{
    /**
     * Itens da sidebar acadêmica, na ordem definida pela Issue.
     *
     * @return array<int, array{slug: string, titulo: string, icone: string, url: string, resource: ?class-string}>
     */
    public static function itens(): array
    {
        return [
            [
                'slug' => 'visao-geral',
                'titulo' => 'Visão geral',
                'icone' => 'heroicon-o-squares-2x2',
                'url' => GestaoAcademica::getUrl(panel: 'admin'),
                'resource' => null,
            ],
            [
                'slug' => 'cursos',
                'titulo' => 'Cursos',
                'icone' => 'heroicon-o-book-open',
                'url' => CursoResource::getUrl('index', panel: 'admin'),
                'resource' => CursoResource::class,
            ],
            [
                'slug' => 'modulos',
                'titulo' => 'Módulos',
                'icone' => 'heroicon-o-squares-2x2',
                'url' => ModuloResource::getUrl('index', panel: 'admin'),
                'resource' => ModuloResource::class,
            ],
            [
                'slug' => 'disciplinas',
                'titulo' => 'Disciplinas',
                'icone' => 'heroicon-o-document-text',
                'url' => DisciplinaResource::getUrl('index', panel: 'admin'),
                'resource' => DisciplinaResource::class,
            ],
            [
                'slug' => 'periodos',
                'titulo' => 'Períodos letivos',
                'icone' => 'heroicon-o-calendar-days',
                'url' => PeriodoLetivoResource::getUrl('index', panel: 'admin'),
                'resource' => PeriodoLetivoResource::class,
            ],
            [
                'slug' => 'turmas',
                'titulo' => 'Turmas',
                'icone' => 'heroicon-o-user-group',
                'url' => TurmaResource::getUrl('index', panel: 'admin'),
                'resource' => TurmaResource::class,
            ],
            [
                'slug' => 'matriculas',
                'titulo' => 'Matrículas',
                'icone' => 'heroicon-o-identification',
                'url' => MatriculaResource::getUrl('index', panel: 'admin'),
                'resource' => MatriculaResource::class,
            ],
            [
                'slug' => 'aulas-ao-vivo',
                'titulo' => 'Aulas ao vivo',
                'icone' => 'heroicon-o-video-camera',
                'url' => AulaAoVivoResource::getUrl('index', panel: 'admin'),
                'resource' => AulaAoVivoResource::class,
            ],
            [
                'slug' => 'professores',
                'titulo' => 'Professores',
                'icone' => 'heroicon-o-briefcase',
                'url' => UserResource::getUrl('index', panel: 'admin'),
                'resource' => UserResource::class,
            ],
            [
                'slug' => 'alunos',
                'titulo' => 'Alunos',
                'icone' => 'heroicon-o-user',
                'url' => PessoaResource::getUrl('index', ['activeTab' => 'aluno'], panel: 'admin'),
                'resource' => PessoaResource::class,
            ],
        ];
    }

    /**
     * URL da Central de Gestão Acadêmica com a seção de retorno contextual.
     */
    public static function urlCentral(string $secao): string
    {
        $base = GestaoAcademica::getUrl(panel: 'admin');

        if ($secao === GestaoAcademica::SECAO_PADRAO) {
            return $base;
        }

        return $base.'?secao='.$secao;
    }

    /**
     * Seção da Central para a qual cada Resource acadêmico retorna.
     *
     * @param  class-string  $resourceClass
     */
    public static function secaoRetornoParaResource(string $resourceClass): string
    {
        return match ($resourceClass) {
            CursoResource::class => 'cursos',
            ModuloResource::class, DisciplinaResource::class => 'estrutura',
            PeriodoLetivoResource::class, TurmaResource::class, MatriculaResource::class => 'operacao',
            PessoaResource::class, UserResource::class => 'pessoas',
            AulaAoVivoResource::class => 'acompanhamento',
            default => GestaoAcademica::SECAO_PADRAO,
        };
    }
}
