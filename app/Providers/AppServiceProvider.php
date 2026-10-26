<?php

namespace App\Providers;

use App\Models\Mensalidade;
use App\Models\User;
use App\Observers\MensalidadeObserver;
use App\Observers\TelegramUsuarioObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Força HTTPS se estiver em produção ou rodando via Ngrok.
        if ($this->app->environment('production') || str_contains(config('app.url'), 'ngrok-free.dev')) {
            URL::forceScheme('https');
        }

        Mensalidade::observe(MensalidadeObserver::class);
        User::observe(TelegramUsuarioObserver::class);
    }
}
