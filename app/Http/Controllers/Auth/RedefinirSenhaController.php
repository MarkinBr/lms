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

class RedefinirSenhaController extends Controller
{
    public function esqueci(): View
    {
        return view('auth.esqueci-senha');
    }

    public function enviarLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email:filter', 'max:255']]);

        try {
            Password::broker()->sendResetLink([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        } catch (\Throwable) {
            // A resposta é sempre neutra para não revelar contas cadastradas.
        }

        return back()->with('status', 'Se este e-mail estiver cadastrado, enviaremos em instantes as instruções para definir sua senha. Verifique também a caixa de spam.');
    }

    public function redefinir(Request $request, string $token): View
    {
        return view('auth.redefinir-senha', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function atualizar(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:filter'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
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
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            $request->session()->regenerate();

            return redirect()->route('aluno.login')->with('status', 'Senha definida com sucesso. Faça login para continuar.');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'Não foi possível definir a senha. Solicite um novo link e tente novamente.',
        ]);
    }
}
