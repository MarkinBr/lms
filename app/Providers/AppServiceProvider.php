<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
// Importar os Models e Observers
use App\Models\Mensalidade;
use App\Observers\MensalidadeObserver;

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
        // Força HTTPS se estiver em produção ou rodando via Ngrok
        if($this->app->environment('production') || str_contains(config('app.url'), 'ngrok-free.dev')) {
            URL::forceScheme('https');
        }

        // === REGISTRO DO OBSERVER FINANCEIRO ===
        // Isso garante que toda vez que uma mensalidade mudar, o observer é acionado
        Mensalidade::observe(MensalidadeObserver::class);
    }
}