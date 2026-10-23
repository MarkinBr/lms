<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * CETSADP v9.3.5 — Testes de Feature: SSO Controller
 *
 * Testa o fluxo completo de SSO: callback, criação de usuário,
 * validação de token e redirecionamentos.
 */
class SsoControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SSO callback sem token deve redirecionar para login com erro.
     */
    public function test_callback_sem_token_redireciona_para_login(): void
    {
        $response = $this->get('/sso/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('sso');
    }

    /**
     * SSO callback com token curto deve rejeitar.
     */
    public function test_callback_token_curto_rejeita(): void
    {
        $response = $this->get('/sso/callback?token=abc123');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('sso');
    }

    /**
     * SSO callback com secret não configurada deve falhar graciosamente.
     */
    public function test_callback_sem_secret_configurada(): void
    {
        config(['services.cetsadp.sso_secret' => 'TROQUE_POR_UM_SECRET']);

        $fakeToken = str_repeat('a', 96); // Token de 96 chars

        $response = $this->get("/sso/callback?token={$fakeToken}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('sso');
    }

    /**
     * SSO callback com token inválido (API retorna erro) deve redirecionar.
     */
    public function test_callback_token_invalido_redireciona(): void
    {
        config(['services.cetsadp.sso_secret' => 'secret-valido-para-teste']);

        Http::fake([
            '*/api/sso/validate' => Http::response([
                'valid' => false,
                'error' => 'Token expirado',
            ], 401),
        ]);

        $fakeToken = str_repeat('b', 96);
        $response = $this->get("/sso/callback?token={$fakeToken}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('sso');
    }

    /**
     * SSO callback com token válido para aluno deve criar usuário e fazer login.
     */
    public function test_callback_aluno_valido_cria_usuario_e_loga(): void
    {
        config(['services.cetsadp.sso_secret' => 'secret-valido-para-teste']);

        Http::fake([
            '*/api/sso/validate' => Http::response([
                'valid' => true,
                'user' => [
                    'identifier' => '52998224725',
                    'name' => 'João Silva',
                    'type' => 'aluno',
                ],
            ], 200),
            '*/api/sso/sync-user' => Http::response([], 200),
        ]);

        $fakeToken = str_repeat('c', 96);
        $response = $this->get("/sso/callback?token={$fakeToken}");

        // Aluno entra no portal canônico, não no painel Filament legado.
        $response->assertRedirect(route('aluno.dashboard'));

        // Usuário deve existir no banco
        $this->assertDatabaseHas('users', [
            'cpf' => '52998224725',
            'nivel' => 'aluno',
            'name' => 'João Silva',
        ]);

        // Deve estar autenticado
        $this->assertAuthenticated();
    }

    /**
     * SSO callback com CPF inválido para aluno deve rejeitar.
     */
    public function test_callback_aluno_cpf_invalido_rejeita(): void
    {
        config(['services.cetsadp.sso_secret' => 'secret-valido-para-teste']);

        Http::fake([
            '*/api/sso/validate' => Http::response([
                'valid' => true,
                'user' => [
                    'identifier' => '00000000000', // CPF inválido (sequência)
                    'name' => 'Teste CPF',
                    'type' => 'aluno',
                ],
            ], 200),
        ]);

        $fakeToken = str_repeat('d', 96);
        $response = $this->get("/sso/callback?token={$fakeToken}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('sso');
    }

    /**
     * SSO callback para professor deve redirecionar para /professor.
     */
    public function test_callback_professor_redireciona_corretamente(): void
    {
        config(['services.cetsadp.sso_secret' => 'secret-valido-para-teste']);

        Http::fake([
            '*/api/sso/validate' => Http::response([
                'valid' => true,
                'user' => [
                    'identifier' => 'prof.joao',
                    'name' => 'Prof. João',
                    'type' => 'professor',
                ],
            ], 200),
            '*/api/sso/sync-user' => Http::response([], 200),
        ]);

        $fakeToken = str_repeat('e', 96);
        $response = $this->get("/sso/callback?token={$fakeToken}");

        $response->assertRedirect('/professor');

        $this->assertDatabaseHas('users', [
            'username' => 'prof.joao',
            'nivel' => 'professor',
        ]);
    }

    /**
     * SSO callback com tipo de usuário desconhecido deve usar fallback 'aluno'.
     */
    public function test_callback_tipo_desconhecido_e_rejeitado(): void
    {
        config(['services.cetsadp.sso_secret' => 'secret-valido-para-teste']);

        Http::fake([
            '*/api/sso/validate' => Http::response([
                'valid' => true,
                'user' => [
                    'identifier' => '52998224725',
                    'name' => 'Teste Fallback',
                    'type' => 'tipo_invalido', // Tipo não reconhecido
                ],
            ], 200),
            '*/api/sso/sync-user' => Http::response([], 200),
        ]);

        $fakeToken = str_repeat('f', 96);
        $response = $this->get("/sso/callback?token={$fakeToken}");

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('sso');
        $this->assertGuest();
    }

    /**
     * SSO re-autenticação deve atualizar dados do usuário existente.
     */
    public function test_callback_reautenticacao_atualiza_usuario(): void
    {
        config(['services.cetsadp.sso_secret' => 'secret-valido-para-teste']);

        // Criar usuário existente
        $user = User::create([
            'name' => 'Nome Antigo',
            'cpf' => '52998224725',
            'email' => '52998224725@aluno.cetsadp.local',
            'password' => bcrypt('senha'),
            'nivel' => 'aluno',
            'ativo' => true,
        ]);

        Http::fake([
            '*/api/sso/validate' => Http::response([
                'valid' => true,
                'user' => [
                    'identifier' => '52998224725',
                    'name' => 'Nome Atualizado', // Nome mudou
                    'type' => 'aluno',
                ],
            ], 200),
            '*/api/sso/sync-user' => Http::response([], 200),
        ]);

        $fakeToken = str_repeat('g', 96);
        $response = $this->get("/sso/callback?token={$fakeToken}");

        $response->assertRedirect(route('aluno.dashboard'));

        // Nome deve ter sido atualizado
        $this->assertDatabaseHas('users', [
            'cpf' => '52998224725',
            'name' => 'Nome Atualizado',
        ]);
    }

    public function test_callback_nao_reativa_aluno_inativo(): void
    {
        config(['services.cetsadp.sso_secret' => 'secret-valido-para-teste']);

        $aluno = User::create([
            'name' => 'Aluno Inativo',
            'cpf' => '52998224725',
            'email' => 'inativo.sso@example.test',
            'password' => bcrypt('senha'),
            'nivel' => 'aluno',
            'ativo' => false,
        ]);

        Http::fake([
            '*/api/sso/validate' => Http::response([
                'valid' => true,
                'user' => [
                    'identifier' => '52998224725',
                    'name' => 'Aluno Inativo',
                    'type' => 'aluno',
                ],
            ], 200),
        ]);

        $response = $this->get('/sso/callback?token='.str_repeat('h', 96));

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('sso');
        $this->assertGuest();
        $this->assertFalse((bool) $aluno->fresh()->ativo);
    }
}
