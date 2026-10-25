<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class AlunoLoginTest extends PortalAuthTestCase
{
    use RefreshDatabase;

    private const SENHA = 'SenhaAluno#2026';

    public function test_tela_aprovada_envia_credenciais_ao_backend_real(): void
    {
        $this->get(route('aluno.login'))
            ->assertOk()
            ->assertSee('PORTAL DO')
            ->assertSee('ALUNO')
            ->assertSee('action="'.route('aluno.login.submit').'"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee(route('password.request'), false)
            ->assertDontSee('dash-aluno');
    }

    public function test_aluno_ativo_com_senha_correta_entra_no_dashboard_real(): void
    {
        $aluno = $this->criarUsuario();

        $resposta = $this->post(route('aluno.login.submit'), [
            'email' => '  ALUNO@EXAMPLE.TEST  ',
            'password' => self::SENHA,
            'remember' => '1',
        ]);

        $resposta->assertRedirect(route('aluno.dashboard'));
        $this->assertAuthenticatedAs($aluno);
    }

    public function test_login_respeita_destino_protegido_solicitado_antes_da_autenticacao(): void
    {
        $aluno = $this->criarUsuario();

        $this->get(route('aluno.financeiro'))->assertRedirect(route('login'));

        $this->post(route('aluno.login.submit'), [
            'email' => $aluno->email,
            'password' => self::SENHA,
        ])->assertRedirect(route('aluno.financeiro'));

        $this->assertAuthenticatedAs($aluno);
    }

    public function test_senha_incorreta_email_inexistente_admin_e_aluno_inativo_recebem_erro_generico(): void
    {
        $aluno = $this->criarUsuario();
        $admin = $this->criarUsuario([
            'email' => 'admin@example.test',
            'nivel' => 'admin',
        ]);
        $inativo = $this->criarUsuario([
            'email' => 'inativo@example.test',
            'ativo' => false,
        ]);

        $casos = [
            [$aluno->email, 'senha-errada'],
            ['naoexiste@example.test', self::SENHA],
            [$admin->email, self::SENHA],
            [$inativo->email, self::SENHA],
        ];
        $mensagens = [];

        foreach ($casos as [$email, $senha]) {
            $resposta = $this->from(route('aluno.login'))->post(route('aluno.login.submit'), [
                'email' => $email,
                'password' => $senha,
            ]);

            $resposta->assertRedirect(route('aluno.login'));
            $resposta->assertSessionHasErrors('email');
            $mensagens[] = $resposta->getSession()->get('errors')->first('email');
            $this->assertGuest();
        }

        $this->assertCount(1, array_unique($mensagens));
    }

    public function test_limiter_bloqueia_repeticao_por_email_e_ip(): void
    {
        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $this->post(route('aluno.login.submit'), [
                'email' => 'alvo@example.test',
                'password' => 'incorreta',
            ])->assertSessionHasErrors('email');
        }

        $resposta = $this->post(route('aluno.login.submit'), [
            'email' => 'ALVO@example.test',
            'password' => 'incorreta',
        ]);

        $resposta->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Muitas tentativas',
            $resposta->getSession()->get('errors')->first('email'),
        );
        $this->assertGuest();
    }

    public function test_todas_as_rotas_do_portal_exigem_auth_e_aluno(): void
    {
        $rotasProtegidas = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($rota) => str_starts_with($rota->uri(), 'portal/'))
            ->reject(fn ($rota) => in_array($rota->getName(), ['aluno.login', 'aluno.login.submit'], true));

        $this->assertNotEmpty($rotasProtegidas);

        foreach ($rotasProtegidas as $rota) {
            $middlewares = $rota->gatherMiddleware();

            $this->assertContains('auth', $middlewares, "A rota {$rota->uri()} não exige autenticação.");
            $this->assertContains('aluno', $middlewares, "A rota {$rota->uri()} não exige perfil de aluno ativo.");
        }
    }

    public function test_outros_perfis_e_aluno_inativo_nao_abrem_o_dashboard(): void
    {
        $admin = $this->criarUsuario(['email' => 'admin2@example.test', 'nivel' => 'admin']);
        $inativo = $this->criarUsuario(['email' => 'inativo2@example.test', 'ativo' => false]);

        $this->actingAs($admin)->get(route('aluno.dashboard'))->assertForbidden();

        auth()->logout();

        $this->actingAs($inativo)->get(route('aluno.dashboard'))->assertForbidden();
    }

    public function test_logout_existe_somente_por_post_e_invalida_a_autenticacao(): void
    {
        $aluno = $this->criarUsuario();

        $this->actingAs($aluno)
            ->post(route('aluno.logout'))
            ->assertRedirect(route('aluno.login'));

        $this->assertGuest();
        $this->get('/portal/logout')->assertMethodNotAllowed();
    }

    private function criarUsuario(array $atributos = []): User
    {
        $usuario = User::query()->create(array_merge([
            'name' => 'Aluno de Teste',
            'email' => 'aluno@example.test',
            'password' => Hash::make(self::SENHA),
            'nivel' => 'aluno',
            'ativo' => true,
        ], $atributos));
        $usuario->forceFill(['email_verified_at' => now()])->save();

        return $usuario;
    }
}
