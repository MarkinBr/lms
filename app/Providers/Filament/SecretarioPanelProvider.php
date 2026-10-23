<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\SecretariaLogin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SecretarioPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('secretario')
            ->path('secretaria')
            ->login(SecretariaLogin::class)
            
            // === CORES ===
            ->colors([
                'primary' => Color::Orange,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Blue,
            ])
            
            // === IDENTIDADE VISUAL ===
            ->brandName('Secretaria CETSADP')
            ->darkMode(true)
            
            // === NAVEGAÇÃO ===
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->navigationGroups([
                NavigationGroup::make('📚 Acadêmico')
                    ->collapsed(false),
                NavigationGroup::make('💰 Financeiro')
                    ->collapsed(true),
                NavigationGroup::make('📊 Relatórios')
                    ->collapsed(true),
                NavigationGroup::make('👥 Pessoas')
                    ->collapsed(true),
                NavigationGroup::make('⚙️ Configurações')
                    ->collapsed(true),
            ])
            
            // === RECURSOS ===
            ->discoverResources(in: app_path('Filament/Secretario/Resources'), for: 'App\\Filament\\Secretario\\Resources')
            ->discoverPages(in: app_path('Filament/Secretario/Pages'), for: 'App\\Filament\\Secretario\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            
            // === WIDGETS ===
            ->discoverWidgets(in: app_path('Filament/Secretario/Widgets'), for: 'App\\Filament\\Secretario\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Secretario\Widgets\SecretariaStatsWidget::class,
                \App\Filament\Secretario\Widgets\SecretariaMatriculasChartWidget::class,
                \App\Filament\Secretario\Widgets\SecretariaInadimplentesWidget::class,
            ])
            
            // === RECURSOS AVANÇADOS ===
            ->spa()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->profile()
            ->breadcrumbs(true)
            ->maxContentWidth('full')
            ->font('Inter')
            
            // === MIDDLEWARE ===
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