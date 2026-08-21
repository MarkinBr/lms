<?php

declare(strict_types=1);

namespace App\Filament\Aluno\Widgets;

use App\Models\User;
use App\Support\ApplicationDocuments\DocumentosInscricaoService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Aviso persistente no topo do dashboard do painel `/aluno` enquanto
 * houver documentos pendentes. Escondido automaticamente quando não há
 * inscrição ou quando não há pendências (critério #12/#13 da Issue #53).
 */
class DocumentosPendentesWidget extends Widget
{
    protected static string $view = 'filament.aluno.widgets.documentos-pendentes-widget';

    /**
     * O widget deve aparecer ANTES do widget legado
     * {@see AlunoDashboardWidget} (sort=1), então usamos sort=0.
     */
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $usuario = Auth::user();

        if (! $usuario instanceof User || ! $usuario->ativo) {
            return false;
        }

        /** @var DocumentosInscricaoService $service */
        $service = app(DocumentosInscricaoService::class);

        $resumo = $service->resumoDo($usuario);

        return $resumo['tem_inscricao'] && $resumo['pendentes'] > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        /** @var DocumentosInscricaoService $service */
        $service = app(DocumentosInscricaoService::class);

        $resumo = $service->resumoDo($usuario);

        return [
            'resumo' => $resumo,
            'url_central' => route('aluno.documentos-inscricao'),
        ];
    }
}
