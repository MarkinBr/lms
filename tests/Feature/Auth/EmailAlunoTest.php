<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ConfirmarEmailAluno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;

class EmailAlunoTest extends PortalAuthTestCase
{
    use RefreshDatabase;

    private const SENHA = 'SenhaAtual#2026';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_cadastro_sem_email_exige_confirmacao_e_preserva_identidade(): void
    {
        $aluno = $this->aluno(['email' => null, 'email_verified_at' => null]);
        $url = $this->solicitar($aluno, '  NOVO@EXAMPLE.TEST  ');
        $aluno->refresh();
        $this->assertNull($aluno->email);
        $this->assertNull($aluno->email_verified_at);
        $this->assertSame('novo@example.test', $aluno->email_confirmacao_pendente);
        $this->assertNotNull($aluno->email_confirmacao_expira_em);
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->assertNotSame($token, $aluno->email_confirmacao_token);
        $this->assertSame(hash('sha256', $token), $aluno->email_confirmacao_token);
        $this->assertArrayNotHasKey('email_confirmacao_token', $aluno->toArray());
        $this->get($url)->assertRedirect('/portal/perfil/email');
        $aluno->refresh();
        $this->assertSame('novo@example.test', $aluno->email);
        $this->assertNotNull($aluno->email_verified_at);
        $this->assertNull($aluno->email_confirmacao_token);
        $this->assertNull($aluno->email_confirmacao_pendente);
        $this->assertNull($aluno->email_confirmacao_expira_em);
        auth()->logout();
        $this->post(route('aluno.login.submit'), ['email' => 'leg-001', 'password' => self::SENHA])
            ->assertRedirect(route('aluno.dashboard'));
        $this->assertAuthenticatedAs($aluno);
    }

    public function test_troca_mantem_email_confirmado_ate_confirmar_novo_e_revoga_reset_antigo(): void
    {
        $aluno = $this->aluno();
        $resetAntigo = Password::broker()->createToken($aluno);
        $id = $aluno->id;
        $url = $this->solicitar($aluno, 'novo@example.test');
        $this->assertSame('antigo@example.test', $aluno->fresh()->email);
        $this->assertNotNull($aluno->fresh()->email_verified_at);
        $this->get($url)->assertRedirect('/portal/perfil/email');
        $this->assertSame($id, $aluno->fresh()->id);
        $this->assertSame('leg-001', $aluno->fresh()->matricula_login);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        auth()->logout();
        $this->post(route('password.update'), ['token' => $resetAntigo, 'email' => 'antigo@example.test',
            'password' => 'OutraSenha#2026', 'password_confirmation' => 'OutraSenha#2026'])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check(self::SENHA, $aluno->fresh()->password));
    }

    #[DataProvider('senhasErradas')]
    public function test_solicitacao_exige_senha_atual_sem_gravar_ou_enviar(?string $senha): void
    {
        $aluno = $this->aluno();
        $this->actingAs($aluno)->post('/portal/perfil/email', ['email' => 'novo@example.test', 'senha_atual' => $senha])
            ->assertSessionHasErrors('senha_atual');
        $this->assertNull(session('_old_input.senha_atual'));
        $this->assertNull($aluno->fresh()->email_confirmacao_pendente);
        Notification::assertNothingSent();
    }

    public static function senhasErradas(): array
    {
        return [[null], ['errada']];
    }

    public function test_hash_invalido_na_confirmacao_de_senha_nao_causa_500(): void
    {
        $aluno = $this->aluno();
        DB::table('users')->where('id', $aluno->id)->update(['password' => 'hash-invalido']);
        $this->actingAs($aluno->fresh())->post('/portal/perfil/email', ['email' => 'novo@example.test', 'senha_atual' => self::SENHA])
            ->assertSessionHasErrors('senha_atual');
        Notification::assertNothingSent();
    }

    public function test_apenas_aluno_ativo_autenticado_solicita_ou_confirma_email(): void
    {
        $this->post('/portal/perfil/email', ['email' => 'novo@example.test', 'senha_atual' => self::SENHA])->assertRedirect(route('login'));
        $aluno = $this->aluno();
        $url = $this->solicitar($aluno, 'novo@example.test');
        foreach ([['nivel' => 'admin'], ['nivel' => 'aluno', 'ativo' => false]] as $atributos) {
            $aluno->forceFill($atributos)->save();
            $this->actingAs($aluno)->post('/portal/perfil/email', ['email' => 'novo@example.test', 'senha_atual' => self::SENHA])->assertForbidden();
            $this->get($url)->assertForbidden();
        }
        $this->assertSame('antigo@example.test', $aluno->fresh()->email);
    }

    public function test_links_repetidos_e_tokens_substituidos_nao_alteram_conta(): void
    {
        $aluno = $this->aluno();
        $antigo = $this->solicitar($aluno, 'primeiro@example.test');
        $novo = $this->solicitar($aluno, 'segundo@example.test');
        $this->get($antigo)->assertForbidden();
        $this->assertSame('antigo@example.test', $aluno->fresh()->email);
        $this->get($novo)->assertRedirect('/portal/perfil/email');
        $this->get($novo)->assertForbidden();
        $this->assertSame('segundo@example.test', $aluno->fresh()->email);
    }

    public function test_assinatura_token_e_expiracao_sao_obrigatorios(): void
    {
        $aluno = $this->aluno();
        $url = $this->solicitar($aluno, 'novo@example.test');
        $this->get(parse_url($url, PHP_URL_PATH))->assertForbidden();
        $this->get(URL::temporarySignedRoute('aluno.email.confirmar', now()->addHour(), ['token' => str_repeat('a', 64)]))->assertForbidden();
        // A expiração persistida deve valer mesmo se a assinatura ainda estiver válida.
        $aluno->forceFill(['email_confirmacao_expira_em' => now()->subSecond()])->save();
        $this->get($url)->assertForbidden();
        $this->travel(61)->minutes();
        $this->get($url)->assertForbidden();
        $this->assertSame('antigo@example.test', $aluno->fresh()->email);
    }

    public function test_link_de_outra_conta_nao_altera_nenhuma_das_duas(): void
    {
        $aluno = $this->aluno();
        $url = $this->solicitar($aluno, 'novo@example.test');
        $outro = $this->aluno(['email' => 'outro@example.test', 'matricula_login' => 'leg-002']);
        $this->actingAs($outro)->get($url)->assertForbidden();
        $this->assertSame('antigo@example.test', $aluno->fresh()->email);
        $this->assertSame('outro@example.test', $outro->fresh()->email);
    }

    public function test_email_em_uso_e_revalidado_no_consumo_do_token(): void
    {
        $aluno = $this->aluno();
        $url = $this->solicitar($aluno, 'novo@example.test');
        $this->aluno(['email' => 'NOVO@example.test', 'matricula_login' => 'leg-002']);
        $this->get($url)->assertSessionHasErrors('email');
        $this->assertSame('antigo@example.test', $aluno->fresh()->email);
    }

    public function test_nao_aceita_endereco_de_outra_conta_mesmo_com_caixa_diferente(): void
    {
        $aluno = $this->aluno();
        $this->aluno(['email' => 'ocupado@example.test', 'matricula_login' => 'leg-002']);
        $this->actingAs($aluno)->post('/portal/perfil/email', ['email' => 'OCUPADO@example.test', 'senha_atual' => self::SENHA])
            ->assertSessionHasErrors('email');
        $this->assertNull($aluno->fresh()->email_confirmacao_pendente);
        Notification::assertNothingSent();
    }

    public function test_perfil_nao_troca_email_diretamente_e_nao_exige_email(): void
    {
        $aluno = $this->aluno(['email' => null, 'email_verified_at' => null]);
        $this->actingAs($aluno)->put(route('aluno.perfil.update'), ['name' => 'Nome atualizado'])->assertSessionHasNoErrors();
        $this->assertSame('Nome atualizado', $aluno->fresh()->name);
        $this->put(route('aluno.perfil.update'), ['name' => 'Nome preservado', 'email' => 'intruso@example.test'])->assertRedirect();
        $this->assertNull($aluno->fresh()->email);
    }

    public function test_tela_de_email_orienta_aluno_sem_email_sem_impedir_estudo(): void
    {
        $aluno = $this->aluno(['email' => null, 'email_verified_at' => null]);
        $this->actingAs($aluno)->get('/portal/perfil/email')->assertOk()->assertSee('leg-001')->assertSee('secretaria');
    }

    public function test_recuperacao_nao_envia_para_email_nao_confirmado_e_resposta_e_neutra(): void
    {
        $aluno = $this->aluno(['email_verified_at' => null]);
        $resposta = $this->post(route('password.email'), ['email' => $aluno->email]);
        $resposta->assertSessionHas('status');
        $mensagem = $resposta->getSession()->get('status');
        $this->post(route('password.email'), ['email' => 'ausente@example.test'])->assertSessionHas('status', $mensagem);
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_reset_preexistente_nao_funciona_se_email_nao_esta_confirmado(): void
    {
        $aluno = $this->aluno(['email_verified_at' => null]);
        $token = Password::broker()->createToken($aluno);
        $hash = $aluno->password;
        $this->post(route('password.update'), ['email' => $aluno->email, 'token' => $token,
            'password' => 'OutraSenha#2026', 'password_confirmation' => 'OutraSenha#2026'])->assertSessionHasErrors('email');
        $this->assertSame($hash, $aluno->fresh()->password);
    }

    public function test_email_ambiguo_nao_recebe_recuperacao(): void
    {
        $this->aluno();
        $this->aluno(['email' => 'ANTIGO@example.test', 'matricula_login' => 'leg-002']);
        $this->post(route('password.email'), ['email' => 'antigo@example.test'])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_redefinir_senha_invalida_confirmacao_pendente_anterior(): void
    {
        $aluno = $this->aluno();
        $url = $this->solicitar($aluno, 'novo@example.test');
        $token = Password::broker()->createToken($aluno);
        auth()->logout();
        $this->post(route('password.update'), ['email' => $aluno->email, 'token' => $token,
            'password' => 'OutraSenha#2026', 'password_confirmation' => 'OutraSenha#2026'])->assertRedirect(route('aluno.login'));
        $this->actingAs($aluno->fresh())->get($url)->assertForbidden();
        $this->assertSame('antigo@example.test', $aluno->fresh()->email);
    }

    public function test_alterar_senha_no_perfil_invalida_confirmacao_pendente_anterior(): void
    {
        $aluno = $this->aluno();
        $url = $this->solicitar($aluno, 'novo@example.test');
        $this->post(route('aluno.perfil.alterar-senha'), ['senha_atual' => self::SENHA,
            'nova_senha' => 'OutraSenha#2026', 'nova_senha_confirmation' => 'OutraSenha#2026'])->assertSessionHasNoErrors();
        $this->get($url)->assertForbidden();
        $this->assertSame('antigo@example.test', $aluno->fresh()->email);
    }

    private function solicitar(User $aluno, string $email): string
    {
        $this->actingAs($aluno)->post('/portal/perfil/email', ['email' => $email, 'senha_atual' => self::SENHA])
            ->assertRedirect('/portal/perfil/email')->assertSessionHasNoErrors();
        $url = null;
        Notification::assertSentOnDemand(ConfirmarEmailAluno::class, function ($notificacao, $canais, $destinatario) use (&$url, $email) {
            $this->assertSame(mb_strtolower(trim($email)), $destinatario->routes['mail']);
            $this->assertSame(['mail'], $canais);
            $url = $notificacao->toMail($destinatario)->actionUrl;

            return true;
        });
        Notification::fake();

        return $url;
    }

    private function aluno(array $atributos = []): User
    {
        return User::factory()->create(array_merge(['email' => 'antigo@example.test', 'matricula_login' => 'leg-001',
            'password' => self::SENHA, 'nivel' => 'aluno', 'ativo' => true], $atributos));
    }
}
