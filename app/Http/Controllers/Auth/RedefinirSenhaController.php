<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AtivarAcessoAluno;
use App\Services\Acessos\AtendimentoAluno;
use App\Services\Auth\IdentificadorAluno;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class RedefinirSenhaController extends Controller
{
    private const MENSAGEM_NEUTRA = 'Se este e-mail pertencer a um aluno ativo, enviaremos as instruções de acesso em instantes. Verifique também a caixa de spam.';

    private const MENSAGEM_NEUTRA_PRIMEIRO = 'Se houver uma conta elegível para primeiro acesso com este e-mail, enviaremos um link de ativação. Verifique também o spam. Se não tiver acesso ao e-mail cadastrado, procure a Secretaria.';

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
            $aluno = app(IdentificadorAluno::class)->porEmailConfirmado($email);

            if ($aluno) {
                Password::broker()->sendResetLink(['email' => $aluno->email, 'id' => $aluno->id, 'nivel' => 'aluno', 'ativo' => true]);
            }
        } catch (Throwable $exception) {
            // A resposta permanece idêntica, sem revelar contas nem dados do transporte.
            Log::warning('Falha ao solicitar recuperação de senha do aluno.', [
                'exception' => $exception::class,
            ]);
        }

        return back()->with('status', self::MENSAGEM_NEUTRA);
    }

    /**
     * Primeiro acesso não reutiliza a recuperação de senha: contas ainda não
     * confirmadas exigem o link assinado de ativação, com token individual.
     */
    public function solicitarPrimeiroAcesso(Request $request, IdentificadorAluno $identificadores): RedirectResponse
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $dados = $request->validate([
            'email' => ['required', 'string', 'email:filter', 'max:255'],
        ]);

        try {
            $aluno = $identificadores->porEmailPrimeiroAcesso($dados['email']);

            if ($aluno) {
                Password::broker()->sendResetLink(
                    [
                        'id' => $aluno->id,
                        'email' => $aluno->email,
                        'nivel' => 'aluno',
                        'ativo' => true,
                        'primeiro_acesso_pendente' => true,
                        'email_verified_at' => null,
                        'senha_definitiva_pendente' => false,
                    ],
                    function (User $usuario, string $token): void {
                        $usuario->notify(new AtivarAcessoAluno($token));
                    },
                );
            }
        } catch (Throwable $exception) {
            // Resposta pública neutra; detalhes técnicos apenas no log interno.
            Log::warning('Falha ao solicitar primeiro acesso do aluno.', [
                'exception' => $exception::class,
            ]);
        }

        return back()->with('status', self::MENSAGEM_NEUTRA_PRIMEIRO);
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
        $aluno = app(IdentificadorAluno::class)->porEmailConfirmado($email);

        if (! $aluno) {
            return $this->falhaRedefinicao($request);
        }

        $status = DB::transaction(function () use ($aluno, $dados, $email) {
            $atual = User::query()->lockForUpdate()->find($aluno->id);
            $elegivel = app(IdentificadorAluno::class)->porEmailConfirmado($email);
            if (! $atual || ! $elegivel || $elegivel->id !== $atual->id) {
                return Password::INVALID_USER;
            }

            return Password::broker()->reset(
                [
                    'email' => $atual->email,
                    'id' => $atual->id,
                    'nivel' => 'aluno',
                    'ativo' => true,
                    'password' => $dados['password'],
                    'password_confirmation' => $dados['password_confirmation'],
                    'token' => $dados['token'],
                ],
                function (User $user, string $password): void {
                    AtendimentoAluno::revogar($user);
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'senha_definitiva_pendente' => false,
                        'primeiro_acesso_pendente' => false,
                        'senha_alterada_em' => now(),
                        'email_confirmacao_pendente' => null,
                        'email_confirmacao_token' => null,
                        'email_confirmacao_expira_em' => null,
                    ])->save();

                    event(new PasswordReset($user));
                },
            );
        });

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
