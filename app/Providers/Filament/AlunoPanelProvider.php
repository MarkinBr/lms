<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ValidarSessaoAluno;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AlunoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('aluno') // Identificador do painel
            ->path('aluno') // O link será: seusite.com/aluno
            ->login(fn () => redirect()->route('aluno.login')) // Uma única política de autenticação para o aluno.
            ->colors([
                'primary' => Color::Green, // Verde para Alunos (diferente do Admin)
            ])
            ->discoverResources(in: app_path('Filament/Aluno/Resources'), for: 'App\\Filament\\Aluno\\Resources')
            ->discoverPages(in: app_path('Filament/Aluno/Pages'), for: 'App\\Filament\\Aluno\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Aluno/Widgets'), for: 'App\\Filament\\Aluno\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                ValidarSessaoAluno::class,
            ]);
    }
}
