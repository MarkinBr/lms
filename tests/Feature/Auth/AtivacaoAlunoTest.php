<?php

namespace Tests\Feature\Auth;

use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\User;
use App\Notifications\AtivarAcessoAluno;
use App\Services\AprovarInscricaoResult;
use App\Services\AprovarInscricaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;

class AtivacaoAlunoTest extends PortalAuthTestCase
{
    use RefreshDatabase;

    private const SENHA = 'SenhaInicial#2026';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Mail::fake();
    }

    public function test_aprovacao_ativa_com_senha_confirma_email_e_libera_login_permanente(): void
    {
        $resultado = $this->aprovar();
        $aluno = $resultado->user->fresh();
        $this->assertSame(mb_strtolower($resultado->matricula->numero_matricula), $aluno->matricula_login);
        $this->assertNull($aluno->email_verified_at);
        $this->assertTrue($aluno->primeiro_acesso_pendente);
        $url = $this->link($aluno);
        $hash = $aluno->password;
        $this->get($url)->assertOk()->assertSee($aluno->matricula_login)->assertSee('name="password_confirmation"', false);
        $this->assertSame($hash, $aluno->fresh()->password);
        $this->assertNull($aluno->fresh()->email_verified_at);
        $this->post($url, $this->senha())->assertRedirect(route('aluno.login'))->assertSessionHas('status');
        $this->assertGuest();
        $aluno->refresh();
        $this->assertFalse($aluno->primeiro_acesso_pendente);
        $this->assertNotNull($aluno->email_verified_at);
        $this->assertTrue(Hash::check(self::SENHA, $aluno->password));
        $this->assertDatabaseCount('password_reset_tokens', 0);
        foreach ([$aluno->matricula_login, $aluno->email] as $login) {
            $this->post(route('aluno.login.submit'), ['email' => $login, 'password' => self::SENHA])->assertRedirect(route('aluno.dashboard'));
            $this->assertAuthenticatedAs($aluno);
            auth()->logout();
        }
        $this->assertDatabaseHas('matriculas', ['id' => $resultado->matricula->id, 'user_id' => $aluno->id]);
    }

    public function test_token_repetido_nao_modifica_a_senha_ou_email(): void
    {
        $aluno = $this->aprovar()->user;
        $url = $this->link($aluno);
        $this->post($url, $this->senha())->assertRedirect(route('aluno.login'));
        $hash = $aluno->fresh()->password;
        $this->post($url, $this->senha('OutraSenha#2026'))->assertForbidden();
        $this->get($url)->assertForbidden();
        $this->assertSame($hash, $aluno->fresh()->password);
    }

    #[DataProvider('linksInvalidos')]
    public function test_link_invalido_ou_conta_inelegivel_nao_ativa(string $caso): void
    {
        $aluno = $this->aprovar()->user;
        $url = $this->link($aluno);
        $hash = $aluno->password;
        switch ($caso) {
            case 'sem assinatura': $url = explode('?', $url)[0];
                break;
            case 'adulterado': $url .= '&ativacao=1';
                break;
            case 'token errado': $url = URL::temporarySignedRoute('aluno.ativacao', now()->addHour(), ['aluno' => $aluno->id, 'token' => str_repeat('a', 64), 'email' => $aluno->email]);
                break;
            case 'assinatura expirada': $this->travel(61)->minutes();
                break;
            case 'token expirado': DB::table('password_reset_tokens')->update(['created_at' => now()->subMinutes(61)]);
                break;
            case 'inativo': $aluno->forceFill(['ativo' => false])->save();
                break;
            case 'admin': $aluno->forceFill(['nivel' => 'admin'])->save();
                break;
            case 'nao pendente': $aluno->forceFill(['primeiro_acesso_pendente' => false])->save();
                break;
            case 'email alterado': $aluno->forceFill(['email' => 'outro@example.test'])->save();
                break;
        }
        $this->get($url)->assertForbidden();
        $this->post($url, $this->senha())->assertForbidden();
        $this->assertNull($aluno->fresh()->email_verified_at);
        $this->assertSame($hash, $aluno->fresh()->password);
    }

    public static function linksInvalidos(): array
    {
        return array_map(fn ($caso) => [$caso], ['sem assinatura', 'adulterado', 'token errado', 'assinatura expirada', 'token expirado', 'inativo', 'admin', 'nao pendente', 'email alterado']);
    }

    public function test_sessao_de_terceiro_nao_consume_link_individual(): void
    {
        $aluno = $this->aprovar()->user;
        $url = $this->link($aluno);
        $terceiro = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->actingAs($terceiro)->get($url)->assertForbidden();
        $this->post($url, $this->senha())->assertForbidden();
        $this->assertNull($aluno->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($terceiro);
        auth()->logout();
        $this->post($url, $this->senha())->assertRedirect(route('aluno.login'));
    }

    public function test_senha_fraca_nao_confirma_email_nem_consumo_do_token(): void
    {
        $aluno = $this->aprovar()->user;
        $url = $this->link($aluno);
        $this->from($url)->post($url, $this->senha('fraca'))->assertSessionHasErrors('password');
        $this->assertNull($aluno->fresh()->email_verified_at);
        $this->post($url, $this->senha())->assertRedirect(route('aluno.login'));
    }

    public function test_token_de_ativacao_e_boolean_publico_nao_liberam_recuperacao_comum(): void
    {
        $aluno = $this->aprovar()->user;
        $this->link($aluno);
        Notification::fake();
        $this->post(route('password.email'), ['email' => $aluno->email, 'ativacao' => true, 'primeiro_acesso' => true])->assertSessionHas('status');
        Notification::assertNothingSent();
        $token = Password::broker()->createToken($aluno);
        $this->post(route('password.update'), array_merge($this->senha(), ['email' => $aluno->email, 'token' => $token, 'ativacao' => true]))
            ->assertSessionHasErrors('email');
        $this->assertNull($aluno->fresh()->email_verified_at);
    }

    public function test_aprovar_outro_curso_preserva_matricula_login_e_nao_reenvia_ativacao(): void
    {
        $primeiro = $this->aprovar();
        $segundo = $this->aprovar($primeiro->user);
        $this->assertSame($primeiro->user->id, $segundo->user->id);
        $this->assertNotNull($primeiro->user->matricula_login);
        $this->assertSame($primeiro->user->matricula_login, $segundo->user->matricula_login);
        $this->assertNotSame($primeiro->matricula->numero_matricula, $segundo->matricula->numero_matricula);
        Notification::assertSentTimes(AtivarAcessoAluno::class, 1);
    }

    public function test_aprovar_usuario_existente_preserva_login_senha_e_confirmacao(): void
    {
        $aluno = User::factory()->create(['email' => 'candidato@example.test', 'cpf' => '12345678901', 'matricula_login' => 'legado-permanente', 'nivel' => 'aluno', 'ativo' => true]);
        $hash = $aluno->password;
        $verificado = $aluno->email_verified_at->toDateTimeString();
        $resultado = $this->aprovar($aluno);
        $this->assertSame('legado-permanente', $resultado->user->matricula_login);
        $this->assertSame($hash, $resultado->user->password);
        $this->assertSame($verificado, $resultado->user->email_verified_at->toDateTimeString());
        $this->assertFalse($resultado->user->primeiro_acesso_pendente);
        Notification::assertNothingSent();
    }

    public function test_falha_de_envio_nao_desfaz_aprovacao_nem_vaza_mensagem_do_transporte(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('TOKEN-PRIVADO-SMTP'));
        Log::spy()->shouldReceive('channel')->andReturnSelf();
        $resultado = $this->aprovar();
        $this->assertDatabaseHas('matriculas', ['id' => $resultado->matricula->id]);
        $this->assertNull($resultado->user->fresh()->email_verified_at);
        $this->assertTrue($resultado->user->fresh()->primeiro_acesso_pendente);
        Log::shouldHaveReceived('error')->once()->withArgs(function ($mensagem, $contexto) {
            return $mensagem === 'Falha ao enviar link de ativação para novo aluno.'
                && ! str_contains(json_encode($contexto), 'TOKEN-PRIVADO-SMTP')
                && ! array_key_exists('token', $contexto);
        });
    }

    private function link(User $aluno): string
    {
        $url = null;
        Notification::assertSentTo($aluno, AtivarAcessoAluno::class, function ($notificacao) use ($aluno, &$url) {
            $url = $notificacao->toMail($aluno)->actionUrl;
            $this->assertNotNull($url);
            $this->assertStringContainsString('signature=', $url);
            $this->assertDatabaseMissing('password_reset_tokens', ['token' => $notificacao->token]);

            return true;
        });

        return $url;
    }

    private function senha(string $senha = self::SENHA): array
    {
        return ['password' => $senha, 'password_confirmation' => $senha];
    }

    private function aprovar(?User $existente = null): AprovarInscricaoResult
    {
        $curso = Curso::create(['titulo' => 'Curso sintético '.Curso::count(), 'ativo' => true]);
        $modalidade = CourseModality::create(['curso_id' => $curso->id, 'nome' => 'EAD', 'ativo' => true]);
        $secretaria = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $inscricao = Application::create([
            'protocol' => 'SINT-'.Str::uuid(),
            'nome_completo' => 'Candidato sintético', 'cpf' => $existente?->cpf ?? '12345678901',
            'data_nascimento' => '1990-01-15', 'sexo' => 'F', 'nacionalidade' => 'Brasileira',
            'email' => $existente?->email ?? 'candidato@example.test', 'telefone' => '11999999999',
            'cep' => '01001000', 'logradouro' => 'Rua sintética', 'numero' => '1', 'bairro' => 'Centro',
            'cidade' => 'São Paulo', 'estado' => 'SP', 'escolaridade' => 'medio_completo',
            'curso_id' => $curso->id, 'course_modality_id' => $modalidade->id, 'status' => Application::STATUS_EM_ANALISE,
        ]);

        return app(AprovarInscricaoService::class)->executar($inscricao, $secretaria);
    }
}
