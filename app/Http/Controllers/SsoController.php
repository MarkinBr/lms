<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CETSADP v9.3.5 — SSO Controller
 * Etapa 4.3: Validação CPF + Segurança reforçada
 *
 * MELHORIAS v9.3.5:
 * - Validação de CPF via algoritmo local
 * - Sanitização reforçada do identifier
 * - Logging estruturado para auditoria
 * - Tratamento de erros HTTP mais granular
 * - Logout SSO bidirecional (LMS → Live)
 *
 * Fluxo:
 * 1. Usuário no Live clica "Acessar LMS"
 * 2. Backend Live gera token SSO (POST /api/sso/generate)
 * 3. Usuário é redirecionado para app.cetsadp.com.br/sso/callback?token=XXX
 * 4. LMS valida o token via API interna (POST /api/sso/validate)
 * 5. LMS cria/encontra o usuário e faz login automático
 */
class SsoController extends Controller
{
    /**
     * Tipos de usuário válidos (whitelist)
     */
    private const VALID_USER_TYPES = ['aluno', 'professor', 'admin'];

    /**
     * Callback SSO — Recebe token do Live e faz login automático
     */
    public function callback(Request $request)
    {
        $token = $request->query('token');

        if (! $token || strlen($token) < 32) {
            return redirect('/login')->withErrors([
                'sso' => 'Token SSO não fornecido ou inválido.',
            ]);
        }

        try {
            // Validar token via API interna do Live
            $liveApiUrl = config('services.cetsadp.live_api_url', 'http://backend:3000');
            $ssoSecret = config('services.cetsadp.sso_secret', '');

            // v9.3.5: Verificar se o secret está configurado antes de chamar a API
            if (empty($ssoSecret) || str_contains($ssoSecret, 'TROQUE')) {
                Log::error('SSO: CETSADP_SSO_SECRET não configurada no LMS');

                return redirect('/login')->withErrors([
                    'sso' => 'Configuração SSO incompleta. Contacte o administrador.',
                ]);
            }

            $response = Http::timeout(10)->post("{$liveApiUrl}/api/sso/validate", [
                'token' => $token,
                'secret' => $ssoSecret,
            ]);

            if (! $response->successful() || ! $response->json('valid')) {
                Log::warning('SSO: Token inválido ou expirado', [
                    'status' => $response->status(),
                    'ip' => $request->ip(),
                ]);

                return redirect('/login')->withErrors([
                    'sso' => 'Token SSO inválido ou expirado. Tente novamente.',
                ]);
            }

            $ssoUser = $response->json('user');
            $identifier = $ssoUser['identifier'] ?? null;
            $userName = $ssoUser['name'] ?? 'Usuário';
            $userType = $ssoUser['type'] ?? 'aluno';

            // Validar tipo de usuário (whitelist)
            if (! in_array($userType, self::VALID_USER_TYPES, true)) {
                Log::warning('SSO: Tipo de usuário inválido', [
                    'type' => $userType,
                    'ip' => $request->ip(),
                ]);

                return redirect('/login')->withErrors([
                    'sso' => 'Não foi possível autorizar este perfil no LMS.',
                ]);
            }

            if (! $identifier) {
                return redirect('/login')->withErrors([
                    'sso' => 'Dados do usuário não encontrados.',
                ]);
            }

            // v9.3.5 (N01): Validação de CPF para alunos
            if ($userType === 'aluno') {
                $cpfLimpo = preg_replace('/\D/', '', $identifier);
                if (! $this->validarCpf($cpfLimpo)) {
                    Log::warning('SSO: CPF inválido no callback', [
                        'identifier' => substr($identifier, 0, 3).'***',
                        'ip' => $request->ip(),
                    ]);

                    return redirect('/login')->withErrors([
                        'sso' => 'CPF inválido. Verifique seus dados no sistema Live.',
                    ]);
                }
                $identifier = $cpfLimpo; // Usar CPF normalizado
            }

            // Sanitizar identifier (apenas alfanuméricos e pontos)
            $identifier = preg_replace('/[^a-zA-Z0-9._\-]/', '', $identifier);

            // Encontrar ou criar usuário no LMS
            $user = $this->findOrCreateUser($identifier, $userName, $userType);

            if (! $user) {
                return redirect('/login')->withErrors([
                    'sso' => 'Não foi possível criar a conta no LMS.',
                ]);
            }

            // Fazer login automático
            Auth::login($user, true); // remember = true
            $request->session()->regenerate();

            // Atualizar campo last_sso_at
            $user->update([
                'last_sso_at' => now(),
                'sso_provider' => 'live',
            ]);

            // Sincronizar mapeamento (fire and forget)
            $this->syncUserMapping($liveApiUrl, $ssoSecret, $identifier, $userType, $user->id);

            Log::info('SSO: Login bem-sucedido', [
                'user_id' => $user->id,
                'identifier' => substr($identifier, 0, 3).'***',
                'type' => $userType,
                'ip' => $request->ip(),
            ]);

            // Redirecionar baseado no tipo de usuário
            return match ($userType) {
                'admin' => redirect('/admin'),
                'professor' => redirect('/professor'),
                default => redirect()->route('aluno.dashboard'),
            };

        } catch (\Exception $e) {
            Log::error('SSO: Erro na validação', [
                'exception' => $e::class,
                'ip' => $request->ip(),
            ]);

            return redirect('/login')->withErrors([
                'sso' => 'Erro ao processar login SSO. Tente novamente.',
            ]);
        }
    }

    /**
     * Validar CPF (algoritmo completo)
     * v9.3.5: Validação local para não depender do backend Live
     */
    private function validarCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        // Rejeitar sequências repetidas (000.000.000-00, 111.111.111-11, etc.)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validar dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += intval($cpf[$i]) * (($t + 1) - $i);
            }
            $resto = ($soma * 10) % 11;
            if ($resto === 10) {
                $resto = 0;
            }
            if (intval($cpf[$t]) !== $resto) {
                return false;
            }
        }

        return true;
    }

    /**
     * Encontrar ou criar usuário no LMS com base nos dados do Live
     */
    private function findOrCreateUser(string $identifier, string $name, string $type): ?User
    {
        try {
            // Tentar encontrar por CPF ou username
            $user = User::where('cpf', $identifier)
                ->orWhere('username', $identifier)
                ->first();

            if ($user) {
                if (! $user->ativo) {
                    Log::warning('SSO: tentativa de acesso por conta inativa', [
                        'user_id' => $user->id,
                    ]);

                    return null;
                }

                // Atualizar nome se necessário
                $updateData = [];
                if ($user->name !== $name) {
                    $updateData['name'] = $name;
                }
                // Garantir que o nivel está correto (para SSO re-auth)
                if ($user->nivel !== $type) {
                    $updateData['nivel'] = $type;
                }
                if (! empty($updateData)) {
                    $user->update($updateData);
                }

                return $user;
            }

            // Criar novo usuário
            $userData = [
                'name' => $name,
                'password' => bcrypt(Str::random(32)),
                'ativo' => true,
                'sso_provider' => 'live',
                'last_sso_at' => now(),
            ];

            // Configurar campos baseado no tipo
            if ($type === 'aluno') {
                $userData['cpf'] = $identifier;
                $userData['email'] = $identifier.'@aluno.cetsadp.local';
                $userData['nivel'] = 'aluno';
            } elseif ($type === 'professor') {
                $userData['username'] = $identifier;
                $userData['email'] = $identifier.'@professor.cetsadp.local';
                $userData['nivel'] = 'professor';
            } else {
                $userData['username'] = $identifier;
                $userData['email'] = $identifier.'@admin.cetsadp.local';
                $userData['nivel'] = 'admin';
                $userData['super_admin'] = true;
            }

            return User::create($userData);

        } catch (\Exception $e) {
            Log::error('SSO: Erro ao criar/encontrar usuário', [
                'identifier' => substr($identifier, 0, 3).'***',
                'exception' => $e::class,
            ]);

            return null;
        }
    }

    /**
     * Logout SSO Bidirecional — Invalida sessão no LMS E notifica o Live
     * v9.3.5 (LO-01): Quando o aluno faz logout no LMS, invalida tokens no Live
     */
    public function logoutBidirecional(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return redirect('/login');
        }

        $identifier = $user->cpf ?? $user->username ?? null;

        // 1. Notificar o backend Live para invalidar tokens SSO
        if ($identifier) {
            try {
                $liveApiUrl = config('services.cetsadp.live_api_url', 'http://backend:3000');
                $ssoSecret = config('services.cetsadp.sso_secret', '');

                // v9.3.5: NÃO enviar secret do frontend — apenas via server-to-server
                if (! empty($ssoSecret) && ! str_contains($ssoSecret, 'TROQUE') && ! str_contains($ssoSecret, 'GERE-COM')) {
                    Http::timeout(5)->post("{$liveApiUrl}/api/sso/logout", [
                        'secret' => $ssoSecret,
                        'identifier' => $identifier,
                        'source' => 'lms',
                    ]);

                    Log::info('SSO: Logout bidirecional enviado ao Live', [
                        'identifier' => substr($identifier, 0, 3).'***',
                        'ip' => $request->ip(),
                    ]);
                }
            } catch (\Exception $e) {
                // Não bloquear o logout local se a notificação falhar
                Log::warning('SSO: Falha na notificação de logout bidirecional', [
                    'exception' => $e::class,
                    'ip' => $request->ip(),
                ]);
            }
        }

        // 2. Invalidar sessão local do LMS
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('SSO: Logout bidirecional concluído', [
            'identifier' => $identifier ? substr($identifier, 0, 3).'***' : 'unknown',
            'ip' => $request->ip(),
        ]);

        return redirect('/login')->with('message', 'Logout realizado com sucesso.');
    }

    /**
     * Sincronizar mapeamento entre sistemas (fire and forget)
     */
    private function syncUserMapping(
        string $liveApiUrl,
        string $ssoSecret,
        string $identifier,
        string $userType,
        int $lmsUserId
    ): void {
        try {
            Http::timeout(5)->post("{$liveApiUrl}/api/sso/sync-user", [
                'secret' => $ssoSecret,
                'identifier' => $identifier,
                'userType' => $userType,
                'lmsUserId' => $lmsUserId,
            ]);
        } catch (\Exception $e) {
            // Não bloquear o login se a sincronização falhar
            Log::warning('SSO: Falha ao sincronizar mapeamento', [
                'identifier' => substr($identifier, 0, 3).'***',
                'exception' => $e::class,
            ]);
        }
    }
}
