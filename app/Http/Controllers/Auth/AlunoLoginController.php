<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Acessos\CredenciaisAluno;
use App\Services\Auth\IdentificadorAluno;
use App\Services\Auth\SenhaAluno;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AlunoLoginController extends Controller
{
    private const MAX_TENTATIVAS = 5;

    private const BLOQUEIO_SEGUNDOS = 600;

    /**
     * Hash válido usado quando a conta não é elegível. Mantém o custo da
     * verificação de senha e reduz diferenças de tempo que ajudariam a
     * descobrir quais e-mails estão cadastrados.
     */
    private const HASH_SENHA_FICTICIA = '$2y$12$BMvgkPxYKShMMpad7E1KoOF25jOjZeCHUYEP5L6seHzjzyL3HWUYq';

    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('aluno.dashboard');
        }

        return view('aluno.auth.login');
    }

    public function login(Request $request, IdentificadorAluno $identificadores, SenhaAluno $senhas): RedirectResponse
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $dados = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $email = mb_strtolower(trim($dados['email']));
        $chaveLimite = $this->chaveLimite($request, $identificadores->chaveLimite($email));

        if (RateLimiter::tooManyAttempts($chaveLimite, self::MAX_TENTATIVAS)) {
            return $this->falhar(
                $request,
                'Muitas tentativas de acesso. Aguarde alguns minutos e tente novamente.',
            );
        }

        $aluno = $identificadores->resolver($email);

        $resultado = DB::transaction(function () use ($aluno, $dados, $senhas, $request) {
            $atual = $aluno ? User::query()->lockForUpdate()->find($aluno->id) : null;
            if ($atual && $atual->ativo && $atual->nivel === 'aluno' && $atual->senha_definitiva_pendente) {
                $sessao = app(CredenciaisAluno::class)->consumirTemporaria($atual, $dados['password']);

                return $sessao ? ['sessao' => $sessao] : null;
            }
            $senhaValida = $senhas->confere($dados['password'], $atual?->password ?: self::HASH_SENHA_FICTICIA);
            if (! $atual || ! $atual->ativo || $atual->nivel !== 'aluno' || ! $senhaValida) {
                return null;
            }
            if (Hash::needsRehash($atual->password)) {
                $atual->forceFill(['password' => Hash::make($dados['password'])])->save();
            }
            Auth::login($atual, $request->boolean('remember'));
            $request->session()->put('aluno_acesso_versao', (int) $atual->acesso_versao);

            return $atual;
        });

        if (! $resultado) {
            RateLimiter::hit($chaveLimite, self::BLOQUEIO_SEGUNDOS);

            return $this->falhar($request);
        }

        if (is_array($resultado)) {
            return AcessoAssistidoController::iniciarSessao($request, $resultado['sessao']);
        }
        $request->session()->regenerate();
        $request->session()->forget('acesso_assistido');
        RateLimiter::clear($chaveLimite);

        return redirect()->intended(route('aluno.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('aluno.login');
    }

    private function chaveLimite(Request $request, string $email): string
    {
        return 'login-aluno:'.hash('sha256', $email.'|'.$request->ip());
    }

    private function falhar(
        Request $request,
        string $mensagem = 'Não foi possível entrar. Verifique seus dados e tente novamente.',
    ): RedirectResponse {
        return back()
            ->withErrors(['email' => $mensagem])
            ->withInput(['email' => mb_strtolower(trim((string) $request->input('email')))]);
    }
}
