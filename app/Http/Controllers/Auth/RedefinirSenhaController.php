<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class RedefinirSenhaController extends Controller
{
    private const MENSAGEM_NEUTRA = 'Se este e-mail pertencer a um aluno ativo, enviaremos as instruções de acesso em instantes. Verifique também a caixa de spam.';

    public function esqueci(Request $request): View
    {
        return view('auth.esqueci-senha', [
            'primeiroAcesso' => $request->boolean('primeiro_acesso'),
        ]);
    }

    public function enviarLink(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $dados = $request->validate([
            'email' => ['required', 'string', 'email:filter', 'max:255'],
        ]);

        $email = mb_strtolower(trim($dados['email']));

        try {
            $aluno = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('nivel', 'aluno')
                ->where('ativo', true)
                ->first();

            if ($aluno) {
                Password::broker()->sendResetLink(['email' => $aluno->email]);
            }
        } catch (Throwable) {
            // A resposta permanece idêntica para não revelar a existência da conta
            // nem detalhes do provedor de e-mail.
        }

        return back()->with('status', self::MENSAGEM_NEUTRA);
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
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $dados = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(12)->mixedCase()->numbers()->symbols(),
            ],
            'password_confirmation' => ['required', 'string', 'max:1024'],
        ], [
            'password.min' => 'A senha deve ter pelo menos 12 caracteres.',
            'password.mixed' => 'Use letras maiúsculas e minúsculas.',
            'password.numbers' => 'Inclua pelo menos um número.',
            'password.symbols' => 'Inclua pelo menos um símbolo.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
        ]);

        $email = mb_strtolower(trim($dados['email']));
        $aluno = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('nivel', 'aluno')
            ->where('ativo', true)
            ->first();

        if (! $aluno) {
            return $this->falhaRedefinicao($request);
        }

        $status = Password::broker()->reset(
            [
                'email' => $aluno->email,
                'password' => $dados['password'],
                'password_confirmation' => $dados['password_confirmation'],
                'token' => $dados['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'senha_alterada_em' => now(),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->falhaRedefinicao($request);
        }

        $request->session()->regenerateToken();

        return redirect()->route('aluno.login')->with(
            'status',
            'Senha definida com sucesso. Agora você já pode entrar no Portal do Aluno.',
        );
    }

    private function falhaRedefinicao(Request $request): RedirectResponse
    {
        return back()
            ->withInput(['email' => mb_strtolower(trim((string) $request->input('email')))])
            ->withErrors([
                'email' => 'Não foi possível definir a senha. Solicite um novo link e tente novamente.',
            ]);
    }
}
