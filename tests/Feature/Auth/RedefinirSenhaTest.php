<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * RedefinirSenhaTest
 * ------------------------------------------------------------------
 * Cobre o fluxo Issue #17:
 *   • Solicitação neutra (existente vs. inexistente)
 *   • URL enviada pelo broker resolve a rota `password.reset`
 *   • Redefinição efetiva com token válido, evento PasswordReset,
 *     senha_alterada_em atualizada, token invalidado, autenticação
 *     com a nova senha
 *   • Token inválido/expirado e confirmação divergente não alteram
 *     a senha
 */
class RedefinirSenhaTest extends TestCase
{
    use RefreshDatabase;

    private function criarAluno(array $override = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'Aluno de Teste',
            'email' => 'aluno@example.test',
            'cpf' => '12345678901',
            'password' => Hash::make('SenhaAntiga1'),
            'nivel' => 'aluno',
            'ativo' => true,
        ], $override));
    }

    /**
     * 1) A URL do reset resolve a rota nomeada `password.reset`.
     */
    public function test_a_url_do_reset_resolve_a_rota_password_reset(): void
    {
        // A rota nomeada tem que existir para o broker construir a URL.
        $this->assertTrue(
            Route::has('password.reset'),
            'A rota nomeada password.reset precisa existir.'
        );

        $url = route('password.reset', ['token' => 'abc123', 'email' => 'x@y.z']);
        $this->assertStringContainsString('/senha/redefinir/abc123', $url);
        $this->assertStringContainsString('email=', $url);
    }

    /**
     * 2) Solicitação para e-mail existente dispara o ResetPassword
     *    e a resposta NÃO revela se a conta existe.
     */
    public function test_solicitacao_para_email_existente_envia_link_com_resposta_neutra(): void
    {
        Notification::fake();

        $aluno = $this->criarAluno();

        $resp = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $aluno->email]);

        $resp->assertRedirect(route('password.request'));
        $resp->assertSessionHas('status');

        Notification::assertSentTo($aluno, ResetPassword::class);

        // Token gravado em password_reset_tokens.
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    /**
     * 3) Solicitação para e-mail INEXISTENTE responde EXATAMENTE
     *    igual e não cria token.
     */
    public function test_solicitacao_para_email_inexistente_nao_revela_e_nao_cria_token(): void
    {
        Notification::fake();

        $respExistente = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'ninguem@example.test']);

        $respExistente->assertRedirect(route('password.request'));
        $respExistente->assertSessionHas('status');
        $respExistente->assertSessionMissing('errors');

        // Nada enviado, nada gravado.
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);

        // Confere paridade da mensagem com o caso existente.
        $aluno = $this->criarAluno();
        $respPresente = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $aluno->email]);

        $this->assertSame(
            session('status'),
            $respPresente->baseResponse->getSession()->get('status'),
            'A mensagem de sucesso deve ser idêntica para e-mail existente ou não.'
        );
    }

    /**
     * 4) A URL enviada pelo broker aponta para a rota `password.reset`.
     */
    public function test_url_enviada_pelo_broker_aponta_para_rota_password_reset(): void
    {
        Notification::fake();

        $aluno = $this->criarAluno();

        $this->post(route('password.email'), ['email' => $aluno->email]);

        Notification::assertSentTo($aluno, ResetPassword::class, function (ResetPassword $notif) use ($aluno) {
            $url = $notif->toMail($aluno)->actionUrl;

            $this->assertStringContainsString('/senha/redefinir/', $url);
            $this->assertStringContainsString('email=', $url);

            return true;
        });
    }

    /**
     * 5) Token válido troca a senha, dispara PasswordReset, atualiza
     *    senha_alterada_em, invalida o token e permite login.
     */
    public function test_token_valido_troca_senha_e_dispara_evento(): void
    {
        Event::fake([PasswordReset::class]);

        $aluno = $this->criarAluno(['senha_alterada_em' => null]);
        $token = Password::broker()->createToken($aluno);

        $resp = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $aluno->email,
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ]);

        $resp->assertRedirect(route('aluno.login'));
        $resp->assertSessionHas('status');

        Event::assertDispatched(PasswordReset::class);

        $aluno->refresh();
        $this->assertTrue(Hash::check('NovaSenha123', $aluno->password));
        $this->assertNotNull($aluno->senha_alterada_em);
        $this->assertNotNull($aluno->remember_token);

        // Token invalidado após uso.
        $this->assertDatabaseCount('password_reset_tokens', 0);

        // Autentica com a nova senha.
        $this->assertTrue(auth()->attempt([
            'email' => $aluno->email,
            'password' => 'NovaSenha123',
        ]));
    }

    /**
     * 6) Token inválido não altera a senha.
     */
    public function test_token_invalido_nao_altera_senha(): void
    {
        $aluno = $this->criarAluno();
        $hashOriginal = $aluno->password;

        $resp = $this->from(route('password.reset', ['token' => 'fake']))
            ->post(route('password.update'), [
                'token' => 'token-que-nao-existe',
                'email' => $aluno->email,
                'password' => 'NovaSenha123',
                'password_confirmation' => 'NovaSenha123',
            ]);

        $resp->assertSessionHasErrors('email');

        $aluno->refresh();
        $this->assertSame($hashOriginal, $aluno->password);
        $this->assertFalse(Hash::check('NovaSenha123', $aluno->password));
    }

    /**
     * 7) Token expirado não altera a senha.
     */
    public function test_token_expirado_nao_altera_senha(): void
    {
        $aluno = $this->criarAluno();
        $hashOriginal = $aluno->password;

        $token = Password::broker()->createToken($aluno);

        // Envelhece o token além do expire configurado (60 min).
        DB::table('password_reset_tokens')
            ->where('email', $aluno->email)
            ->update(['created_at' => now()->subMinutes(120)]);

        $resp = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $aluno->email,
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ]);

        $resp->assertSessionHasErrors('email');

        $aluno->refresh();
        $this->assertSame($hashOriginal, $aluno->password);
    }

    /**
     * 8) Confirmação divergente falha na validação, sem alterar senha.
     */
    public function test_confirmacao_divergente_falha_sem_alterar_senha(): void
    {
        $aluno = $this->criarAluno();
        $hashOriginal = $aluno->password;
        $token = Password::broker()->createToken($aluno);

        $resp = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $aluno->email,
            'password' => 'NovaSenha123',
            'password_confirmation' => 'Diferente123',
        ]);

        $resp->assertSessionHasErrors('password');

        $aluno->refresh();
        $this->assertSame($hashOriginal, $aluno->password);
        $this->assertDatabaseCount('password_reset_tokens', 1); // Token preservado
    }

    /**
     * 9) Senha fraca (sem maiúscula/minúscula/número/8 chars) é rejeitada.
     */
    public function test_senha_fraca_eh_rejeitada(): void
    {
        $aluno = $this->criarAluno();
        $token = Password::broker()->createToken($aluno);

        foreach ([
            'curta1A',           // < 8
            'semnumeromaius',    // sem maiúscula e sem número
            'SEMMINUSCULA123',   // sem minúscula
            'SemNumeros',        // sem número
        ] as $fraca) {
            $resp = $this->post(route('password.update'), [
                'token' => $token,
                'email' => $aluno->email,
                'password' => $fraca,
                'password_confirmation' => $fraca,
            ]);

            $resp->assertSessionHasErrors('password');
        }
    }

    /**
     * 10) Tela GET de "esqueci" e GET de "redefinir" respondem 200.
     */
    public function test_telas_get_publicas_respondem_ok(): void
    {
        $this->get(route('password.request'))->assertOk();
        $this->get(route('password.reset', ['token' => 'qualquer', 'email' => 'x@y.z']))
            ->assertOk();
    }
}
