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
 *
 * Issue #49: usuário inativo deve ser negado nas rotas protegidas.
 */
class EnsureUserIsAluno
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('aluno.login');
        }

        // Verificar se o nível do usuário é 'aluno'
        if ($user->nivel !== 'aluno') {
            abort(403, 'Acesso restrito a alunos.');
        }

        // Issue #49: usuário inativo não acessa rotas protegidas do portal
        if (! (bool) $user->ativo) {
            abort(403, 'Conta inativa.');
        }

        abort_if((bool) $user->senha_definitiva_pendente, 403, 'Defina sua senha definitiva antes de acessar o portal.');

        return $next($request);
    }
}
