<?php

namespace App\Filament\Concerns;

use App\Support\GestaoAcademica\NavegacaoAcademica;

/**
 * Issue #61 — Shell visual acadêmico compartilhado.
 *
 * Aplicado às páginas dos Resources acadêmicos do painel /admin para que
 * listagens, criação, visualização e edição abertas a partir da Central de
 * Gestão Acadêmica mantenham a mesma identidade visual institucional, sem
 * renderizar a sidebar nem a topbar globais do Filament.
 *
 * O trait é estritamente visual: não altera formulários, tabelas, ações,
 * validações, Policies, Relation Managers, rotas ou URLs. O CRUD continua
 * sendo executado integralmente pelo Filament no servidor.
 */
trait UsesGestaoAcademicaLayout
{
    /**
     * Layout Blade standalone compartilhado. Usa a base de assets do
     * Filament (Livewire, Alpine, modais, notificações e CSRF) sem o
     * chrome global do painel (sem layout.index, fi-main-sidebar ou
     * fi-topbar).
     */
    public function getLayout(): string
    {
        return 'layouts.gestao-academica-resource';
    }

    /**
     * Classe marcador no <body>: permite escopar o CSS também aos overlays
     * renderizados fora do container da página (modais e notificações do
     * Filament), sem afetar nenhum outro painel ou Resource.
     */
    public function getExtraBodyAttributes(): array
    {
        $attributes = parent::getExtraBodyAttributes();
        $existingClasses = trim((string) ($attributes['class'] ?? ''));

        $attributes['class'] = trim($existingClasses.' ga-standalone-body ga-resource-body');

        return $attributes;
    }

    /**
     * Dados institucionais injetados no shell: navegação acadêmica, retorno
     * contextual à Central, caminho ao dashboard e identificação real do
     * administrador autenticado.
     *
     * @return array<string, mixed>
     */
    protected function getLayoutData(): array
    {
        return array_merge(parent::getLayoutData(), [
            'gaNavegacao' => NavegacaoAcademica::itens(),
            'gaResourceAtivo' => static::getResource(),
            'gaUrlCentral' => NavegacaoAcademica::urlCentral(
                NavegacaoAcademica::secaoRetornoParaResource(static::getResource())
            ),
            'gaUrlPainel' => filament()->getPanel('admin')->getUrl(),
            'gaAdministrador' => auth()->user(),
        ]);
    }
}
