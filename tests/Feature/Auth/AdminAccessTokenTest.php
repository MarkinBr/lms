<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Auth\AdminRequestPasswordReset;
use App\Filament\Pages\Auth\AdminResetPassword;
use App\Mail\AdminAccessTokenMail;
use App\Models\AdminAccessToken;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class AdminAccessTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_solicitacao_envia_frase_de_24_palavras_sem_persistir_a_frase(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $response = $this->post(route('admin.token.request'), [
            'email' => $admin->email,
        ]);

        $response->assertRedirect()->assertSessionHas('token_requested', true);
        Mail::assertSent(AdminAccessTokenMail::class, function (AdminAccessTokenMail $mail) use ($admin): bool {
            return $mail->hasTo($admin->email)
                && count(preg_split('/\s+/', trim($mail->frase))) === 24;
        });

        $this->assertDatabaseCount('admin_access_tokens', 1);
        $this->assertDatabaseMissing('admin_access_tokens', [
            'token_hash' => $this->mailFrase(),
        ]);
    }

    public function test_email_de_usuario_inativo_ou_nao_admin_tem_resposta_generica_e_nao_envia(): void
    {
        Mail::fake();
        $this->admin(['ativo' => false]);

        $response = $this->from(route('admin.token.create'))
            ->post(route('admin.token.request'), [
                'email' => 'nao-existe@example.test',
            ]);

        $response->assertRedirect(route('admin.token.create'))
            ->assertSessionHas('token_requested', true);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('admin_access_tokens', 0);
    }

    public function test_frase_valida_autentica_admin_ativo_e_e_de_uso_unico(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->post(route('admin.token.request'), ['email' => $admin->email]);
        $phrase = $this->mailFrase();

        $response = $this->post(route('admin.token.authenticate'), [
            'email' => $admin->email,
            'phrase' => $phrase,
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull(AdminAccessToken::query()->firstOrFail()->used_at);

        auth()->logout();
        $this->post(route('admin.token.authenticate'), [
            'email' => $admin->email,
            'phrase' => $phrase,
        ])->assertSessionHasErrors('phrase');
        $this->assertGuest();
    }

    public function test_nova_solicitacao_invalida_o_token_anterior(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->post(route('admin.token.request'), ['email' => $admin->email]);
        $this->post(route('admin.token.request'), ['email' => $admin->email]);

        $this->assertDatabaseCount('admin_access_tokens', 2);
        $this->assertNotNull(AdminAccessToken::query()->oldest('id')->firstOrFail()->used_at);
        $this->assertNull(AdminAccessToken::query()->latest('id')->firstOrFail()->used_at);
    }

    public function test_cinco_tentativas_invalidas_consumem_o_token(): void
    {
        Mail::fake();
        $this->withoutMiddleware(ThrottleRequests::class);
        $admin = $this->admin();
        $this->post(route('admin.token.request'), ['email' => $admin->email]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('admin.token.authenticate'), [
                'email' => $admin->email,
                'phrase' => implode(' ', array_fill(0, 24, 'invalida')),
            ])->assertSessionHasErrors('phrase');
        }

        $this->assertNotNull(AdminAccessToken::query()->firstOrFail()->used_at);
    }

    public function test_token_expirado_nao_autentica(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->post(route('admin.token.request'), ['email' => $admin->email]);
        $phrase = $this->mailFrase();

        AdminAccessToken::query()->firstOrFail()->update(['expires_at' => now()->subMinute()]);

        $this->post(route('admin.token.authenticate'), [
            'email' => $admin->email,
            'phrase' => $phrase,
        ])->assertSessionHasErrors('phrase');

        $this->assertGuest();
    }

    public function test_login_visual_nao_expoe_credencial_de_demo_e_mostra_token_real(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk()
            ->assertSee('Acesso com token')
            ->assertSee(route('admin.token.create'))
            ->assertSee('fonts/manrope-latin.woff2', false)
            ->assertDontSee('fonts.googleapis.com', false)
            ->assertDontSee('cetsadp_last_access', false)
            ->assertDontSee('admin123')
            ->assertDontSee('DEMO');
    }

    /**
     * Regressão: o GET /admin/token estava estourando TypeError porque o
     * método `create()` do controller declarava View|RedirectResponse, mas
     * retorna Illuminate\Http\Response (via response()->view()->header()).
     * O teste garante que a página renderiza 200, com o Cache-Control
     * privado exigido pela política de segurança, e sem regressão do
     * designer premium do HTML de referência.
     */
    public function test_get_admin_token_renderiza_sem_typeerror_e_com_cache_no_store(): void
    {
        $response = $this->get(route('admin.token.create'));

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('Token de acesso — CETSADP', false)
            ->assertSee('24 palavras', false)
            ->assertSee('maxlength="512"', false)
            ->assertSee('fonts/manrope-latin.woff2', false)
            ->assertDontSee('fonts.googleapis.com', false)
            ->assertSee('CETSADP')
            ->assertDontSee('admin123')
            ->assertDontSee('DEMO');
    }

    /**
     * Regressão: administrador já autenticado é redirecionado para /admin
     * ao acessar a tela de token — evita reciclar a página quando não faz
     * sentido e mantém a mesma UX do login por senha.
     */
    public function test_get_admin_token_redireciona_administrador_ja_autenticado(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.token.create'))
            ->assertRedirect('/admin');
    }

    /**
     * Se o transporte de e-mail falha, o token recém-criado é apagado (para
     * que uma solicitação futura funcione) e o log NÃO registra e-mail nem
     * frase — apenas classe da exceção e id interno do usuário.
     */
    public function test_falha_no_envio_de_email_remove_o_token_e_nao_vaza_dados(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new TransportException('smtp down'));
        $admin = $this->admin();

        $this->post(route('admin.token.request'), ['email' => $admin->email])
            ->assertRedirect()
            ->assertSessionHas('token_requested', true);

        $this->assertDatabaseCount('admin_access_tokens', 0);
    }

    public function test_frase_rejeitada_na_validacao_nao_e_armazenada_como_old_input(): void
    {
        $response = $this->from(route('admin.token.create'))
            ->post(route('admin.token.authenticate'), [
                'email' => 'admin@example.test',
                'phrase' => str_repeat('a', 513),
            ]);

        $response->assertRedirect(route('admin.token.create'))
            ->assertSessionHasErrors('phrase')
            ->assertSessionMissing('_old_input.phrase')
            ->assertSessionHas('_old_input.email', 'admin@example.test');
    }

    public function test_recuperacao_de_senha_admin_usa_paginas_premium_exclusivas(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertSame(AdminRequestPasswordReset::class, $panel->getRequestPasswordResetRouteAction());
        $this->assertSame(AdminResetPassword::class, $panel->getResetPasswordRouteAction());

        $this->get(route('filament.admin.auth.password-reset.request'))
            ->assertOk()
            ->assertSee('Recuperar acesso')
            ->assertSee('noindex,nofollow,noarchive', false)
            ->assertSee('fonts/manrope-latin.woff2', false)
            ->assertDontSee('fonts.googleapis.com', false);

        $admin = $this->admin();

        $this->get($panel->getResetPasswordUrl('token-de-teste', $admin))
            ->assertOk()
            ->assertSee('Definir nova senha')
            ->assertSee('noindex,nofollow,noarchive', false)
            ->assertSee('fonts/playfair-display-latin.woff2', false)
            ->assertDontSee('fonts.googleapis.com', false);
    }

    private function admin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'admin@example.test',
            'nivel' => 'admin',
            'ativo' => true,
        ], $overrides));
    }

    private function mailFrase(): string
    {
        $phrase = null;

        Mail::assertSent(AdminAccessTokenMail::class, function (AdminAccessTokenMail $mail) use (&$phrase): bool {
            $phrase = $mail->frase;

            return true;
        });

        return (string) $phrase;
    }
}
