<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * RedefinirSenhaController
 * ------------------------------------------------------------------
 * Fluxo nativo do Laravel para o portal do aluno:
 *
 *   1. GET  /senha/esqueci        (esqueci)   → formulário público
 *   2. POST /senha/esqueci        (enviarLink)→ dispara sendResetLink,
 *                                                resposta neutra sempre
 *   3. GET  /senha/redefinir/{tk} (redefinir) → formulário com token/email
 *                                                (rota nomeada password.reset)
 *   4. POST /senha/redefinir      (atualizar) → Password::reset + evento
 *                                                PasswordReset
 *
 * Segurança:
 * - Nunca revela se um e-mail existe (resposta neutra).
 * - Token de uso único, expira em 60 min (config/auth.php).
 * - Não loga senha, token, e-mail ou CPF.
 * - Reaproveita broker/notificação padrão (URL do e-mail resolve
 *   pela rota nomeada password.reset).
 * - Regenera a sessão após redefinir.
 */
class RedefinirSenhaController extends Controller
{
    /**
     * Exibe o formulário público "Esqueci minha senha".
     */
    public function esqueci(): View
    {
        return view('auth.esqueci-senha');
    }

    /**
     * Recebe o e-mail e dispara o broker. Resposta SEMPRE neutra:
     * o retorno é o mesmo para e-mail existente ou inexistente.
     */
    public function enviarLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email:filter', 'max:255'],
        ]);

        // Não tratamos o status retornado nem inspecionamos o banco:
        // se o e-mail existir, o broker envia; se não, nada acontece.
        // A resposta do controller é idêntica nos dois casos.
        try {
            Password::broker()->sendResetLink(
                ['email' => strtolower(trim($request->input('email')))]
            );
        } catch (\Throwable $e) {
            // Silenciamos qualquer exceção do transporte para não
            // revelar existência da conta nem detalhes internos.
            // O broker já faz seu próprio log estruturado; não
            // registramos e-mail aqui.
        }

        return back()->with(
            'status',
            'Se este e-mail estiver cadastrado, enviaremos em instantes as instruções para redefinir sua senha. Verifique também a caixa de spam.'
        );
    }

    /**
     * Formulário para definir a nova senha.
     * Esta é a rota nomeada `password.reset` — a URL usada pelo
     * ResetPassword notification do Laravel resolve exatamente aqui.
     */
    public function redefinir(Request $request, string $token): View
    {
        return view('auth.redefinir-senha', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Efetiva a troca de senha usando Password::reset (broker nativo).
     * Atualiza password + remember_token + senha_alterada_em, dispara
     * evento PasswordReset e regenera a sessão.
     */
    public function atualizar(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:filter'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
        ], [
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.mixed' => 'A senha deve conter pelo menos uma letra maiúscula e uma minúscula.',
            'password.numbers' => 'A senha deve conter pelo menos um número.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'senha_alterada_em' => now(),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Regenera a sessão por segurança, mesmo sem login ativo.
            $request->session()->regenerate();

            return redirect()->route('aluno.login')->with(
                'status',
                'Senha redefinida com sucesso. Faça login com sua nova senha.'
            );
        }

        // Token inválido/expirado, e-mail inválido, throttle etc.
        // Mensagem genérica — nunca revela detalhes do estado interno.
        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Não foi possível redefinir sua senha. Solicite um novo link e tente novamente.',
            ]);
    }
}
