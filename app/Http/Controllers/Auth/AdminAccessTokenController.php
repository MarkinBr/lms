<?php

namespace App\Http\Controllers\Auth;

use App\Mail\AdminAccessTokenMail;
use App\Models\AdminAccessToken;
use App\Models\User;
use App\Services\AdminAccessTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * Fluxo de token temporário de acesso administrativo.
 *
 * Os endpoints de solicitação e autenticação são anônimos (middleware
 * `guest`); o GET permite que o controller redirecione sessões existentes.
 * O fluxo não vaza informação: respostas idênticas para e-mail existente e
 * inexistente, sem logs sensíveis, sem token em URL/HTML/JS.
 */
class AdminAccessTokenController
{
    public function __construct(
        private readonly AdminAccessTokenService $tokens,
    ) {}

    /**
     * Renderiza a tela de acesso por token.
     *
     * A resposta usa `Cache-Control: no-store, private` porque a página
     * exibe estados de sessão sensíveis (mensagens de solicitação enviada)
     * que não podem ser servidos por caches intermediários. Para preservar
     * esses cabeçalhos usamos `response()->view()`, o que produz um
     * `Illuminate\Http\Response` — daí a assinatura estendida.
     */
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->intended('/admin');
        }

        return response()
            ->view('filament.admin.auth.token')
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function request(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $email = mb_strtolower(trim($data['email']));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('nivel', 'admin')
            ->where('ativo', true)
            ->first();

        if ($user) {
            $phrase = $this->tokens->gerar();
            $token = DB::transaction(function () use ($user, $phrase): AdminAccessToken {
                AdminAccessToken::query()
                    ->where('user_id', $user->id)
                    ->whereNull('used_at')
                    ->update(['used_at' => now()]);

                return AdminAccessToken::query()->create([
                    'user_id' => $user->id,
                    'token_hash' => $this->tokens->hash($phrase),
                    'expires_at' => now()->addMinutes(10),
                ]);
            });

            try {
                Mail::to($user->email)->send(new AdminAccessTokenMail($user->name, $phrase));
            } catch (\Throwable $exception) {
                $token->delete();

                // Nunca registrar e-mail, frase, token ou stack completo:
                // apenas a classe da exceção e o id interno bastam para
                // triagem sem expor dados do administrador.
                Log::warning('Falha ao enviar token temporário de acesso administrativo.', [
                    'exception' => $exception::class,
                    'user_id' => $user->id,
                ]);
            }
        }

        // Resposta idêntica para e-mail existente e inexistente. Devolvemos
        // apenas o campo de e-mail em `withInput` para que a UI possa
        // preservá-lo — a frase nunca é ecoada.
        return back()->with('token_requested', true)->withInput(['email' => $email]);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->only(['email', 'phrase']), [
            'email' => ['required', 'email:rfc', 'max:255'],
            'phrase' => ['required', 'string', 'max:512'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput([
                    'email' => mb_strtolower(trim((string) $request->input('email'))),
                ]);
        }

        $data = $validator->validated();

        $email = mb_strtolower(trim($data['email']));
        $phrase = (string) $data['phrase'];
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('nivel', 'admin')
            ->where('ativo', true)
            ->first();

        $authenticated = false;

        if ($user && $this->tokens->fraseValida($phrase)) {
            DB::transaction(function () use ($user, $phrase, &$authenticated): void {
                $token = AdminAccessToken::query()
                    ->where('user_id', $user->id)
                    ->whereNull('used_at')
                    ->where('expires_at', '>', now())
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if (! $token || $token->attempts >= 5) {
                    return;
                }

                if (! hash_equals($token->token_hash, $this->tokens->hash($phrase))) {
                    $token->attempts++;

                    if ($token->attempts >= 5) {
                        $token->used_at = now();
                    }

                    $token->save();

                    return;
                }

                $token->used_at = now();
                $token->save();
                $authenticated = true;
            });
        }

        if (! $authenticated) {
            return back()
                ->withErrors(['phrase' => 'A combinação não é válida, expirou ou já foi utilizada.'])
                ->withInput(['email' => $email]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }
}
