<?php

namespace Tests\Feature\Auth;

use App\Mail\AdminAccessTokenMail;
use App\Models\AdminAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
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
            ->assertSee('Entrar com token de 24 palavras')
            ->assertDontSee('admin123')
            ->assertDontSee('DEMO');
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
