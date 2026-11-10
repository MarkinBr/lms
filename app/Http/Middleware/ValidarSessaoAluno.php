<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidarSessaoAluno
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        if (! $usuario) {
            return $next($request);
        }
        $atual = User::find($usuario->id);
        $versaoSessao = $request->session()->get('aluno_acesso_versao');
        if ($usuario->nivel === 'aluno' || $atual?->nivel === 'aluno' || $versaoSessao !== null) {
            if ($versaoSessao === null && Auth::guard()->viaRemember()) {
                // O guard já validou o cookie cifrado. Conferir novamente o token
                // impede aceitar uma revogação ocorrida entre a restauração e esta leitura.
                $tokenAtual = (string) $atual?->getRememberToken();
                $rememberValido = $tokenAtual !== '' && hash_equals($tokenAtual, (string) $usuario->getRememberToken());
                $versaoSessao = $rememberValido ? (int) $atual->acesso_versao : -1;
                if ($rememberValido) {
                    $request->session()->put('aluno_acesso_versao', $versaoSessao);
                }
            }
            // Sessões anteriores à migração só sobrevivem enquanto a versão é zero.
            $versaoValida = $versaoSessao === null ? (int) $atual?->acesso_versao === 0 : (int) $versaoSessao === (int) $atual?->acesso_versao;
            // Inatividade legada mantém o 403 nas próximas rotas protegidas.
            // Bloqueios do atendimento incrementam a versão e encerram a sessão abaixo.
            abort_if($atual && ! $atual->ativo && $versaoValida, 403, 'Conta inativa.');
            if (! $atual || ! $atual->ativo || $atual->nivel !== 'aluno' || $atual->senha_definitiva_pendente || ! $versaoValida) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                abort_if($atual && ! $atual->ativo, 403, 'Conta inativa.');

                return $request->expectsJson() ? response()->json(['message' => 'Sessão encerrada. Entre novamente.'], 401) : redirect()->route('aluno.login');
            }
            Auth::setUser($atual);
        }

        return $next($request);
    }
}
