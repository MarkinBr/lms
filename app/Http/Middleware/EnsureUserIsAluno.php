<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CETSADP v9.3.5 — Middleware: Garante que o usuário é aluno
 * 
 * Verifica se o usuário autenticado possui o nível 'aluno'.
 * Utilizado nas rotas exclusivas do portal do aluno (aula-v2, etc.)
 */
class EnsureUserIsAluno
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('aluno.login');
        }

        // Verificar se o nível do usuário é 'aluno'
        if ($user->nivel !== 'aluno') {
            abort(403, 'Acesso restrito a alunos.');
        }

        return $next($request);
    }
}
