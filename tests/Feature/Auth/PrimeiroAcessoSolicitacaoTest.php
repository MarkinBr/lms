<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\AtivarAcessoAluno;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;

class PrimeiroAcessoSolicitacaoTest extends PortalAuthTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_primeiro_acesso_e_esqueci_senha_possuem_acoes_distintas(): void
    {
        $this->get(route('password.request', ['primeiro_acesso' => 1]))
            ->assertOk()
            ->assertSee('action="'.route('aluno.primeiro-acesso.solicitar').'"', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('action="'.route('password.email').'"', false);
    }

    public function test_aluno_pendente_solicita_link_assinado_e_conclui_ativacao(): void
    {
        $aluno = $this->alunoPendente();

        $this->from(route('password.request', ['primeiro_acesso' => 1]))
            ->post(route('aluno.primeiro-acesso.solicitar'), ['email' => strtoupper($aluno->email)])
            ->assertRedirect(route('password.request', ['primeiro_acesso' => 1]))
            ->assertSessionHas('status');

        $url = null;
        Notification::assertSentTo($aluno, AtivarAcessoAluno::class, function (AtivarAcessoAluno $notificacao) use ($aluno, &$url) {
            $url = $notificacao->toMail($aluno)->actionUrl;

            return str_contains((string) $url, 'signature=');
        });
        Notification::assertNotSentTo($aluno, ResetPassword::class);
        $this->assertNotNull($url);

        $this->get($url)->assertOk()->assertSee($aluno->matricula_login);
        $this->assertNull($aluno->fresh()->email_verified_at);

        $this->post($url, [
            'password' => 'NovaSenha#2026',
            'password_confirmation' => 'NovaSenha#2026',
        ])->assertRedirect(route('aluno.login'));

        $aluno->refresh();
        $this->assertFalse($aluno->primeiro_acesso_pendente);
        $this->assertNotNull($aluno->email_verified_at);
        $this->assertTrue(Hash::check('NovaSenha#2026', $aluno->password));
        $this->get($url)->assertForbidden();
    }

    #[DataProvider('contasNaoElegiveis')]
    public function test_primeiro_acesso_nao_emite_link_para_conta_inelegivel(array $alteracoes): void
    {
        $aluno = $this->alunoPendente($alteracoes);
        $this->post(route('aluno.primeiro-acesso.solicitar'), ['email' => $aluno->email])
            ->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public static function contasNaoElegiveis(): array
    {
        return [
            'administrador' => [['nivel' => 'admin']],
            'secretario' => [['nivel' => 'secretario']],
            'professor' => [['nivel' => 'professor']],
            'aluno inativo' => [['ativo' => false]],
            'aluno verificado' => [['email_verified_at' => '2026-01-01 00:00:00']],
            'aluno nao pendente' => [['primeiro_acesso_pendente' => false]],
            'credencial assistida pendente' => [['senha_definitiva_pendente' => true]],
        ];
    }

    public function test_resposta_publica_e_identica_para_email_valido_e_inexistente(): void
    {
        $aluno = $this->alunoPendente();

        $enviado = $this->post(route('aluno.primeiro-acesso.solicitar'), ['email' => $aluno->email])
            ->assertSessionHas('status');
        $naoEnviado = $this->post(route('aluno.primeiro-acesso.solicitar'), ['email' => 'inexistente@example.test'])
            ->assertSessionHas('status');

        $this->assertSame(
            $enviado->getSession()->get('status'),
            $naoEnviado->getSession()->get('status'),
        );
        Notification::assertSentTimes(AtivarAcessoAluno::class, 1);
    }

    public function test_email_duplicado_nao_recebe_link_de_primeiro_acesso(): void
    {
        $this->alunoPendente(['email' => 'primeiro@example.test']);
        $this->alunoPendente(['email' => 'PRIMEIRO@example.test', 'matricula_login' => '2026-000002']);

        $this->post(route('aluno.primeiro-acesso.solicitar'), ['email' => 'primeiro@example.test'])
            ->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_repeticao_imediata_nao_envia_um_segundo_link(): void
    {
        $aluno = $this->alunoPendente();

        $this->post(route('aluno.primeiro-acesso.solicitar'), ['email' => $aluno->email])
            ->assertSessionHas('status');
        $this->post(route('aluno.primeiro-acesso.solicitar'), ['email' => $aluno->email])
            ->assertSessionHas('status');

        Notification::assertSentTimes(AtivarAcessoAluno::class, 1);
    }

    public function test_parametro_publico_de_primeiro_acesso_na_recuperacao_nao_emite_ativacao(): void
    {
        $aluno = $this->alunoPendente();

        $this->post(route('password.email'), [
            'email' => $aluno->email,
            'primeiro_acesso' => true,
            'ativacao' => true,
        ])->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    private function alunoPendente(array $alteracoes = []): User
    {
        return User::factory()->create(array_merge([
            'nivel' => 'aluno',
            'ativo' => true,
            'email' => 'novo@example.test',
            'matricula_login' => '2026-000001',
            'email_verified_at' => null,
            'primeiro_acesso_pendente' => true,
            'senha_definitiva_pendente' => false,
        ], $alteracoes));
    }
}
