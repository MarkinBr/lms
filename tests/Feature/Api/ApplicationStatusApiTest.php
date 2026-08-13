<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApplicationStatusApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
    }

    private function criarCurso(string $titulo = 'Teologia'): Curso
    {
        return Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);
    }

    private function criarModalidade(Curso $curso): CourseModality
    {
        return CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'Híbrido',
            'ativo' => true,
        ]);
    }

    private function criarInscricao(
        Curso $curso,
        CourseModality $modalidade,
        array $sobrescrever = [],
    ): Application {
        return Application::query()->create(array_merge([
            'protocol' => 'PROT-'.uniqid(),
            'nome_completo' => 'Maria da Silva',
            'cpf' => '52998224725',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'nacionalidade' => 'Brasileira',
            'email' => 'maria-'.uniqid().'@example.test',
            'telefone' => '11999999999',
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'numero' => '1',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'escolaridade' => 'medio_completo',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ], $sobrescrever));
    }

    private function criarUsuario(string $nivel, array $sobrescrever = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'Usuário '.$nivel.' '.uniqid(),
            'email' => $nivel.'-'.uniqid().'@cetsadp.test',
            'password' => 'senha-de-teste',
            'nivel' => $nivel,
            'ativo' => true,
        ], $sobrescrever));
    }

    public function test_tabela_de_rotas_carrega_e_publica_somente_operacoes_existentes(): void
    {
        $this->artisan('route:list', ['--except-vendor' => true])->assertSuccessful();

        $routes = app('router')->getRoutes();
        foreach ([
            'applications.index',
            'applications.show',
            'applications.destroy',
            'applications.status',
        ] as $routeName) {
            $route = $routes->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertSame([
                'api',
                'auth:sanctum',
                'role:secretario,admin',
            ], $route->gatherMiddleware());
        }

        $this->assertNull($routes->getByName('applications.update'));
        $this->assertCount(1, collect($routes)->filter(
            fn ($route) => $route->getName() === 'applications.index',
        ));
        $this->assertCount(0, collect($routes)->filter(
            fn ($route) => $route->uri() === 'api/v1/applications/{application}'
                && array_intersect(['PUT', 'PATCH'], $route->methods()) !== [],
        ));
    }

    public function test_visitante_nao_acessa_rota_de_decisao(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);

        $this->patchJson("/api/v1/applications/{$inscricao->id}/status", [
            'status' => Application::STATUS_APROVADO,
        ])->assertUnauthorized();
    }

    public function test_token_bearer_real_autentica_secretario(): void
    {
        $secretario = $this->criarUsuario('secretario');
        $token = $secretario->createToken('teste-secretaria')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/applications')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_perfis_sem_permissao_e_usuario_inativo_recebem_403(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);

        foreach ([
            ['aluno', true, false],
            ['professor', true, false],
            ['secretary', true, false],
            ['secretario', false, false],
            ['admin', false, true],
        ] as $indice => [$nivel, $ativo, $superAdmin]) {
            $inscricao = $this->criarInscricao($curso, $modalidade, [
                'cpf' => '111444777'.str_pad((string) ($indice + 10), 2, '0', STR_PAD_LEFT),
            ]);
            $usuario = $this->criarUsuario($nivel, [
                'ativo' => $ativo,
                'super_admin' => $superAdmin,
            ]);

            $this->actingAs($usuario, 'sanctum')
                ->patchJson("/api/v1/applications/{$inscricao->id}/status", [
                    'status' => Application::STATUS_APROVADO,
                ])
                ->assertForbidden()
                ->assertJsonPath('error.code', 'FORBIDDEN');
        }

        $this->assertSame(0, Matricula::query()->count());
    }

    public function test_secretario_admin_e_super_admin_ativos_acessam_listagem(): void
    {
        foreach ([
            ['secretario', false],
            ['admin', false],
            ['aluno', true],
        ] as [$nivel, $superAdmin]) {
            $usuario = $this->criarUsuario($nivel, ['super_admin' => $superAdmin]);

            $this->actingAs($usuario, 'sanctum')
                ->getJson('/api/v1/applications')
                ->assertOk()
                ->assertJsonPath('success', true);
        }
    }

    public function test_aprovacao_cria_aluno_matricula_e_registra_operador(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);
        $secretario = $this->criarUsuario('secretario');

        $this->actingAs($secretario, 'sanctum')
            ->patchJson("/api/v1/applications/{$inscricao->id}/status", [
                'status' => Application::STATUS_APROVADO,
                'observacoes_secretaria' => 'Documentação conferida.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Application::STATUS_MATRICULADO)
            ->assertJsonPath('data.matricula.application_id', $inscricao->id);

        $aluno = User::query()->where('cpf', $inscricao->cpf)->sole();
        $matricula = Matricula::query()->where('application_id', $inscricao->id)->sole();

        $this->assertSame('aluno', $aluno->nivel);
        $this->assertSame($aluno->id, $matricula->user_id);
        $this->assertSame($curso->id, $matricula->curso_id);
        $this->assertSame(Application::STATUS_MATRICULADO, $inscricao->refresh()->status);
        $this->assertSame($secretario->id, (int) $inscricao->analisado_por_id);
        $this->assertSame('Documentação conferida.', $inscricao->observacoes_secretaria);
        $this->assertNotNull($inscricao->analisado_em);
        Notification::assertSentToTimes($aluno, ResetPassword::class, 1);
    }

    public function test_aprovacao_reutiliza_aluno_existente_pelo_cpf_e_email(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade, [
            'email' => 'aluna.existente@example.test',
        ]);
        $secretario = $this->criarUsuario('secretario');
        $aluna = $this->criarUsuario('aluno', [
            'email' => $inscricao->email,
            'cpf' => $inscricao->cpf,
        ]);

        $this->actingAs($secretario, 'sanctum')
            ->patchJson("/api/v1/applications/{$inscricao->id}/status", [
                'status' => Application::STATUS_APROVADO,
            ])->assertOk();

        $this->assertSame(1, User::query()->where('cpf', $inscricao->cpf)->count());
        $this->assertDatabaseHas('matriculas', [
            'application_id' => $inscricao->id,
            'user_id' => $aluna->id,
        ]);
        Notification::assertNothingSent();
    }

    public function test_segunda_aprovacao_preserva_aluno_matricula_e_auditoria(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);
        $secretario = $this->criarUsuario('secretario');

        $payload = [
            'status' => Application::STATUS_APROVADO,
            'observacoes_secretaria' => 'Primeira análise.',
        ];
        $url = "/api/v1/applications/{$inscricao->id}/status";

        $this->actingAs($secretario, 'sanctum')->patchJson($url, $payload)->assertOk();
        $inscricao->refresh();
        $matricula = Matricula::query()->where('application_id', $inscricao->id)->sole();
        $aluno = $matricula->user;
        $analisadoEm = $inscricao->analisado_em?->toISOString();
        $operadorOriginal = $inscricao->analisado_por_id;
        $observacaoOriginal = $inscricao->observacoes_secretaria;
        $outroSecretario = $this->criarUsuario('secretario');

        $this->actingAs($outroSecretario, 'sanctum')->patchJson($url, [
            'status' => Application::STATUS_APROVADO,
            'observacoes_secretaria' => 'Tentativa de sobrescrever.',
        ])
            ->assertOk()
            ->assertJsonPath('data.matricula.id', $matricula->id);

        $this->assertSame($matricula->id, Matricula::query()->sole()->id);
        $this->assertSame($aluno->id, User::query()->where('nivel', 'aluno')->sole()->id);
        $inscricaoAtualizada = $inscricao->fresh();
        $this->assertSame($analisadoEm, $inscricaoAtualizada->analisado_em?->toISOString());
        $this->assertSame($operadorOriginal, $inscricaoAtualizada->analisado_por_id);
        $this->assertSame($observacaoOriginal, $inscricaoAtualizada->observacoes_secretaria);
        Notification::assertSentToTimes($aluno, ResetPassword::class, 1);
    }

    public function test_rejeicao_exige_justificativa_valida_sem_alterar_inscricao(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);
        $secretario = $this->criarUsuario('secretario');
        $url = "/api/v1/applications/{$inscricao->id}/status";

        $this->actingAs($secretario, 'sanctum')->patchJson($url, [
            'status' => Application::STATUS_REJEITADO,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('observacoes_secretaria');

        $this->actingAs($secretario, 'sanctum')->patchJson($url, [
            'status' => Application::STATUS_REJEITADO,
            'observacoes_secretaria' => '  a  ',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('observacoes_secretaria');

        $this->assertSame(Application::STATUS_EM_ANALISE, $inscricao->fresh()->status);
        $this->assertNull($inscricao->analisado_por_id);
        $this->assertSame(0, Matricula::query()->count());
    }

    public function test_rejeicao_grava_motivo_e_operador_sem_criar_aluno_ou_matricula(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);
        $secretario = $this->criarUsuario('secretario');
        $motivo = 'Documentação incompleta: faltou o histórico escolar.';

        $this->actingAs($secretario, 'sanctum')
            ->patchJson("/api/v1/applications/{$inscricao->id}/status", [
                'status' => Application::STATUS_REJEITADO,
                'observacoes_secretaria' => "  {$motivo}  ",
            ])
            ->assertOk()
            ->assertJsonPath('data.status', Application::STATUS_REJEITADO);

        $inscricao->refresh();
        $this->assertSame($motivo, $inscricao->observacoes_secretaria);
        $this->assertSame($secretario->id, (int) $inscricao->analisado_por_id);
        $this->assertNotNull($inscricao->analisado_em);
        $this->assertSame(0, Matricula::query()->count());
        $this->assertSame(0, User::query()->where('nivel', 'aluno')->count());
        Notification::assertNothingSent();
    }

    public function test_status_nao_suportados_sao_rejeitados_sem_efeitos_colaterais(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $secretario = $this->criarUsuario('secretario');

        foreach ([
            Application::STATUS_EM_ANALISE,
            Application::STATUS_MATRICULADO,
            Application::STATUS_CANCELADO,
        ] as $indice => $status) {
            $inscricao = $this->criarInscricao($curso, $modalidade, [
                'cpf' => '390533447'.str_pad((string) ($indice + 10), 2, '0', STR_PAD_LEFT),
            ]);

            $this->actingAs($secretario, 'sanctum')
                ->patchJson("/api/v1/applications/{$inscricao->id}/status", [
                    'status' => $status,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('status');

            $this->assertSame(Application::STATUS_EM_ANALISE, $inscricao->fresh()->status);
        }

        $this->assertSame(0, Matricula::query()->count());
    }

    public function test_conflito_de_cpf_e_email_retorna_codigo_de_dominio_sem_gravar(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $secretario = $this->criarUsuario('secretario');
        $this->criarUsuario('aluno', [
            'cpf' => '52998224725',
            'email' => 'outra-pessoa@example.test',
        ]);
        $inscricao = $this->criarInscricao($curso, $modalidade, [
            'email' => 'candidata@example.test',
        ]);

        $response = $this->actingAs($secretario, 'sanctum')
            ->patchJson("/api/v1/applications/{$inscricao->id}/status", [
                'status' => Application::STATUS_APROVADO,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'CPF_EMAIL_CONFLITO');

        $this->assertArrayNotHasKey('exception', $response->json());
        $this->assertArrayNotHasKey('trace', $response->json());
        $this->assertSame(Application::STATUS_EM_ANALISE, $inscricao->fresh()->status);
        $this->assertSame(0, Matricula::query()->count());
    }

    public function test_listagem_protegida_usa_relacao_documents_existente(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $this->criarInscricao($curso, $modalidade);
        $secretario = $this->criarUsuario('secretario');

        $this->actingAs($secretario, 'sanctum')
            ->getJson('/api/v1/applications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [['documents']],
                'pagination',
            ]);
    }
}
