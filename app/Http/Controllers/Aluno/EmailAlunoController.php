<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ConfirmarEmailAluno;
use App\Services\Auth\SenhaAluno;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmailAlunoController extends Controller
{
    public function index(Request $request): View
    {
        return view('aluno.perfil.email', ['aluno' => $request->user()]);
    }

    public function solicitar(Request $request, SenhaAluno $senhas): RedirectResponse
    {
        $entrada = $request->all();
        // A senha em português também deve ficar fora do old input em qualquer erro.
        $request->replace($request->except('senha_atual'));
        $request->query->remove('senha_atual');
        $dados = Validator::make($entrada, [
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'senha_atual' => ['required', 'string', 'max:1024'],
        ])->validate();
        $email = mb_strtolower(trim($dados['email']));
        $token = bin2hex(random_bytes(32));
        $expiraEm = now()->addMinutes(60);

        DB::transaction(function () use ($request, $senhas, $dados, $email, $token, $expiraEm) {
            $aluno = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($aluno->nivel === 'aluno' && $aluno->ativo, 403);
            if (! $senhas->confere($dados['senha_atual'], $aluno->password)) {
                throw ValidationException::withMessages(['senha_atual' => 'Confira sua senha atual.']);
            }
            $this->validarDisponibilidade($email, $aluno);
            $aluno->forceFill([
                'email_confirmacao_pendente' => $email,
                'email_confirmacao_token' => hash('sha256', $token),
                'email_confirmacao_expira_em' => $expiraEm,
            ])->save();
        });

        $url = URL::temporarySignedRoute('aluno.email.confirmar', $expiraEm, ['token' => $token]);
        Notification::route('mail', $email)->notify(new ConfirmarEmailAluno($url));

        return redirect()->route('aluno.email.index')->with('status', 'Enviamos o link ao endereço informado. Confirme para concluir.');
    }

    public function confirmar(Request $request, string $token): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $token) {
                $aluno = User::query()->lockForUpdate()->findOrFail($request->user()->id);
                abort_unless($aluno->nivel === 'aluno' && $aluno->ativo, 403);
                abort_unless(
                    $aluno->email_confirmacao_pendente
                    && $aluno->email_confirmacao_token
                    && $aluno->email_confirmacao_expira_em?->isFuture()
                    && hash_equals($aluno->email_confirmacao_token, hash('sha256', $token)),
                    403,
                    'Link de confirmação inválido ou expirado. Solicite outro no seu perfil.',
                );
                $email = $aluno->email_confirmacao_pendente;
                $this->validarDisponibilidade($email, $aluno);
                // Invalidar links de recuperação associados ao endereço antigo e ao novo.
                DB::table('password_reset_tokens')->whereIn('email', array_filter([$aluno->email, $email]))->delete();
                $aluno->forceFill([
                    'email' => $email,
                    'email_verified_at' => now(),
                    'email_confirmacao_pendente' => null,
                    'email_confirmacao_token' => null,
                    'email_confirmacao_expira_em' => null,
                ])->save();
            });
        } catch (QueryException $e) {
            // A restrição única também protege a corrida com outra confirmação.
            if (! in_array((string) $e->getCode(), ['23000', '23505'], true)) {
                throw $e;
            }
            throw ValidationException::withMessages(['email' => 'Não foi possível confirmar este endereço. Solicite outro link.']);
        }

        return redirect()->route('aluno.email.index')->with('status', 'E-mail confirmado com sucesso. Sua matrícula permanece a mesma.');
    }

    private function validarDisponibilidade(string $email, User $aluno): void
    {
        if (User::query()->whereKeyNot($aluno->id)->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'Este endereço não está disponível. Informe outro e-mail.']);
        }
    }
}
