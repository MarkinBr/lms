<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RedefinirSenhaAlunoTest extends TestCase
{
    use RefreshDatabase;

    public function test_telas_de_recuperacao_e_primeiro_acesso_usam_o_fluxo_real(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('RECUPERAR')
            ->assertSee('action="'.route('password.email').'"', false);

        $this->get(route('password.request', ['primeiro_acesso' => 1]))
            ->assertOk()
            ->assertSee('PRIMEIRO')
            ->assertSee('DEFINIR MINHA SENHA');

        $this->get(route('password.reset', ['token' => 'token-teste', 'email' => 'aluno@example.test']))
            ->assertOk()
            ->assertSee('name="token" value="token-teste"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_somente_aluno_ativo_recebe_link_e_resposta_nao_enumera_contas(): void
    {
        Notification::fake();

        $aluno = $this->criarUsuario();
        $admin = $this->criarUsuario(['email' => 'admin@example.test', 'nivel' => 'admin']);
        $inativo = $this->criarUsuario(['email' => 'inativo@example.test', 'ativo' => false]);

        $respostas = [];
        foreach ([$aluno->email, 'naoexiste@example.test', $admin->email, $inativo->email] as $email) {
            $resposta = $this->from(route('password.request'))->post(route('password.email'), [
                'email' => $email,
            ]);

            $resposta->assertRedirect(route('password.request'));
            $resposta->assertSessionHas('status');
            $respostas[] = $resposta->getSession()->get('status');
        }

        $this->assertCount(1, array_unique($respostas));
        Notification::assertSentTo($aluno, ResetPassword::class);
        Notification::assertNotSentTo($admin, ResetPassword::class);
        Notification::assertNotSentTo($inativo, ResetPassword::class);
    }

    public function test_link_valido_define_senha_forte_sem_logar_automaticamente_e_so_funciona_uma_vez(): void
    {
        Event::fake([PasswordReset::class]);

        $aluno = $this->criarUsuario();
        $token = Password::broker()->createToken($aluno);
        $novaSenha = 'NovaSenha#2026';

        $resposta = $this->post(route('password.update'), [
            'token' => $token,
            'email' => strtoupper($aluno->email),
            'password' => $novaSenha,
            'password_confirmation' => $novaSenha,
        ]);

        $resposta->assertRedirect(route('aluno.login'));
        $resposta->assertSessionHas('status');
        $this->assertGuest();
        Event::assertDispatched(PasswordReset::class);

        $aluno->refresh();
        $this->assertTrue(Hash::check($novaSenha, $aluno->password));
        $this->assertNotNull($aluno->senha_alterada_em);
        $this->assertDatabaseCount('password_reset_tokens', 0);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $aluno->email,
            'password' => 'OutraSenha#2026',
            'password_confirmation' => 'OutraSenha#2026',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check($novaSenha, $aluno->fresh()->password));
    }

    public function test_senha_fraca_e_rejeitada_sem_consumir_o_token(): void
    {
        $aluno = $this->criarUsuario();
        $token = Password::broker()->createToken($aluno);
        $hashAnterior = $aluno->password;

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $aluno->email,
            'password' => 'senhafraca',
            'password_confirmation' => 'senhafraca',
        ])->assertSessionHasErrors('password');

        $this->assertSame($hashAnterior, $aluno->fresh()->password);
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_token_de_admin_ou_aluno_inativo_nao_pode_ser_usado_no_portal(): void
    {
        $admin = $this->criarUsuario(['email' => 'admin2@example.test', 'nivel' => 'admin']);
        $inativo = $this->criarUsuario(['email' => 'inativo2@example.test', 'ativo' => false]);

        foreach ([$admin, $inativo] as $usuario) {
            $token = Password::broker()->createToken($usuario);
            $hashAnterior = $usuario->password;

            $this->post(route('password.update'), [
                'token' => $token,
                'email' => $usuario->email,
                'password' => 'NovaSenha#2026',
                'password_confirmation' => 'NovaSenha#2026',
            ])->assertSessionHasErrors('email');

            $this->assertSame($hashAnterior, $usuario->fresh()->password);
            $this->assertGuest();
        }
    }

    private function criarUsuario(array $atributos = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'Aluno de Teste',
            'email' => 'aluno@example.test',
            'password' => Hash::make('SenhaAntiga#2026'),
            'nivel' => 'aluno',
            'ativo' => true,
        ], $atributos));
    }
}
