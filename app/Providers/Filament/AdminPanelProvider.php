<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\AdminLogin;
use App\Filament\Pages\Auth\AdminRequestPasswordReset;
use App\Filament\Pages\Auth\AdminResetPassword;
use App\Filament\Widgets\CadastrosRecentesWidget;
// Seus Widgets
use App\Filament\Widgets\DistribuicaoUsuariosChart;
use App\Filament\Widgets\GestaoPessoasStatsWidget; // Importação do Shield
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(AdminLogin::class)
            ->passwordReset(AdminRequestPasswordReset::class, AdminResetPassword::class)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<meta name="robots" content="noindex,nofollow,noarchive"><meta name="referrer" content="same-origin">',
            )
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
            ])
            // Issue #57 — carregado somente no painel administrativo.
            ->assets([
                Css::make('gestao-academica', resource_path('css/filament/admin/gestao-academica.css')),
            ])
            ->navigationGroups([
                NavigationGroup::make('Acadêmico')->collapsed(false),
                NavigationGroup::make('Secretaria')->collapsed(false),
                NavigationGroup::make('Pessoas')->collapsed(true),
                NavigationGroup::make('Financeiro')->collapsed(true),
                NavigationGroup::make('Site')->collapsed(true),
                NavigationGroup::make('Administração')->collapsed(true),
            ])
            // === ATENÇÃO: É ESTA LINHA QUE CARREGA SEUS CURSOS AUTOMATICAMENTE ===
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')

            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets Customizados
                GestaoPessoasStatsWidget::class,
                DistribuicaoUsuariosChart::class,
                CadastrosRecentesWidget::class,
            ])
            // === ADICIONE ISTO PARA ATIVAR AS PERMISSÕES ===
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            // ===============================================
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
            ]);
    }
}
