<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles  Níveis canônicos aceitos em User::nivel
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Autenticação necessária.',
                ],
            ], 401);
        }

        if (! (bool) $user->ativo) {
            return $this->forbidden();
        }

        // super_admin é um campo booleano, não um valor de User::nivel.
        if ((bool) $user->super_admin) {
            return $next($request);
        }

        if (! in_array($user->nivel, $roles, true)) {
            return $this->forbidden();
        }

        return $next($request);
    }

    private function forbidden(): Response
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'Você não tem permissão para acessar este recurso.',
            ],
        ], 403);
    }
}
