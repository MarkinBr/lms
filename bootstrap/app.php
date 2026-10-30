<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsAluno;
use App\Http\Middleware\ValidarSessaoAluno;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. Confiar no Proxy (Nginx/Cloudflare)
        $middleware->trustProxies(at: '*');

        // 2. Liberar Webhook do CSRF
        $middleware->validateCsrfTokens(except: [
            'api/webhook/*',
            'webhook/*',
        ]);

        // 3. Registrar aliases de middleware personalizados
        $middleware->alias([
            'aluno' => EnsureUserIsAluno::class,
            'role' => EnsureUserHasRole::class,
        ]);
        $middleware->web(append: [ValidarSessaoAluno::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'senha_confirmacao', 'codigo', 'segredo', 'token']);
    })->create();
