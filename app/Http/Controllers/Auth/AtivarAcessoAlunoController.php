<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AtivarAcessoAlunoController extends Controller
{
    private const ERRO = 'Não foi possível ativar o acesso. Procure a secretaria para solicitar outro link.';

    public function formulario(Request $request, int $aluno, string $token): View
    {
        $usuario = User::query()->find($aluno);
        $this->validarLink($request, $usuario, $token);

        return view('aluno.auth.ativar', ['aluno' => $usuario, 'url' => $request->fullUrl()]);
    }

    public function ativar(Request $request, int $aluno, string $token): RedirectResponse
    {
        DB::transaction(function () use ($request, $aluno, $token) {
            $usuario = User::query()->lockForUpdate()->find($aluno);
            $this->validarLink($request, $usuario, $token);
            $dados = $request->validate([
                'password' => ['required', 'string', 'max:1024', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()],
                'password_confirmation' => ['required', 'string', 'max:1024'],
            ]);

            $status = Password::broker()->reset([
                'id' => $usuario->id,
                'email' => $usuario->email,
                'nivel' => 'aluno',
                'ativo' => true,
                'primeiro_acesso_pendente' => true,
                'email_verified_at' => null,
                'token' => $token,
                'password' => $dados['password'],
                'password_confirmation' => $dados['password_confirmation'],
            ], function (User $usuario, string $senha): void {
                $usuario->forceFill([
                    'password' => Hash::make($senha),
                    'remember_token' => Str::random(60),
                    'senha_alterada_em' => now(),
                    'email_verified_at' => now(),
                    'primeiro_acesso_pendente' => false,
                    'email_confirmacao_pendente' => null,
                    'email_confirmacao_token' => null,
                    'email_confirmacao_expira_em' => null,
                ])->save();
                event(new PasswordReset($usuario));
            });
            abort_unless($status === Password::PASSWORD_RESET, 403, self::ERRO);
        });

        $request->session()->regenerateToken();

        return redirect()->route('aluno.login')->with('status', 'Acesso ativado e e-mail confirmado. Entre com sua matrícula e a nova senha.');
    }

    private function validarLink(Request $request, ?User $aluno, string $token): void
    {
        abort_unless(
            $aluno
            && (! $request->user() || $request->user()->id === $aluno->id)
            && $aluno->ativo
            && $aluno->nivel === 'aluno'
            && $aluno->primeiro_acesso_pendente
            && $aluno->email_verified_at === null
            && is_string($request->query('email'))
            && hash_equals((string) $aluno->email, $request->query('email'))
            && Password::broker()->tokenExists($aluno, $token),
            403,
            self::ERRO,
        );
    }
}
