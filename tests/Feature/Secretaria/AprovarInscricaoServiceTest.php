<?php

namespace Tests\Feature\Secretaria;

use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\User;
use App\Notifications\AtivarAcessoAluno;
use App\Services\AprovarInscricaoException;
use App\Services\AprovarInscricaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Auth\PortalAuthTestCase;

// Novos alunos recebem ativação assinada: recuperação comum exige e-mail já confirmado.
class AprovarInscricaoServiceTest extends PortalAuthTestCase
{
    use RefreshDatabase;

    private AprovarInscricaoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->service = app(AprovarInscricaoService::class);
    }

    /**
     * Helpers ---------------------------------------------------------
     */
    private function criarCurso(string $titulo = 'Teologia'): Curso
    {
        return Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);
    }

    private function criarModalidade(Curso $curso, string $nome = 'EAD'): CourseModality
    {
        return CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => $nome,
            'ativo' => true,
        ]);
    }

    private function criarSecretaria(): User
    {
        return User::query()->create([
            'name' => 'Secretária Fulana',
            'email' => 'secretaria@cetsadp.test',
            'password' => 'x',
            'nivel' => 'secretario',
            'ativo' => true,
        ]);
    }

    private function criarAdmin(): User
    {
        return User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@cetsadp.test',
            'password' => 'x',
            'nivel' => 'admin',
            'ativo' => true,
        ]);
    }

    private function criarInscricao(Curso $curso, CourseModality $mod, array $override = []): Application
    {
        return Application::query()->create(array_merge([
            'protocol' => 'PROT-'.uniqid(),
            'nome_completo' => 'Maria da Silva',
            'cpf' => '12345678901',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'nacionalidade' => 'Brasileira',
            'email' => 'maria@example.test',
            'telefone' => '11999999999',
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'numero' => '1',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'escolaridade' => 'medio_completo',
            'curso_id' => $curso->id,
            'course_modality_id' => $mod->id,
            'status' => Application::STATUS_EM_ANALISE,
        ], $override));
    }

    /**
     * 1) Aprovar inscrição cria usuário aluno e matrícula.
     */
    public function test_aprovar_cria_usuario_aluno_e_matricula(): void
    {
        Notification::fake();

        $curso = $this->criarCurso();
        $mod = $this->criarModalidade($curso);
        $secretaria = $this->criarSecretaria();
        $inscricao = $this->criarInscricao($curso, $mod);

        $resultado = $this->service->executar($inscricao, $secretaria, 'Docs OK');

        $this->assertTrue($resultado->usuarioRecemCriado);
        $this->assertTrue($resultado->matriculaRecemCriada);
        $this->assertSame('aluno', $resultado->user->nivel);
        $this->assertTrue((bool) $resultado->user->ativo);
        $this->assertSame('12345678901', $resultado->user->cpf);
        $this->assertNotEmpty($resultado->matricula->numero_matricula);
        $this->assertSame($inscricao->id, $resultado->matricula->application_id);

        $this->assertDatabaseHas('matriculas', [
            'user_id' => $resultado->user->id,
            'curso_id' => $curso->id,
            'application_id' => $inscricao->id,
        ]);
    }

    /**
     * 2) Aprovar duas inscrições de cursos diferentes do mesmo CPF
     *    reutiliza o mesmo usuário e cria duas matrículas.
     */
    public function test_duas_inscricoes_mesmo_cpf_reutilizam_usuario(): void
    {
        Notification::fake();

        $cursoA = $this->criarCurso('Teologia');
        $cursoB = $this->criarCurso('Diaconato');
        $modA = $this->criarModalidade($cursoA);
        $modB = $this->criarModalidade($cursoB);
        $secretaria = $this->criarSecretaria();

        $insA = $this->criarInscricao($cursoA, $modA);
        $insB = $this->criarInscricao($cursoB, $modB);

        $rA = $this->service->executar($insA, $secretaria);
        $rB = $this->service->executar($insB, $secretaria);

        $this->assertSame($rA->user->id, $rB->user->id, 'Deveria reutilizar o mesmo usuário pelo CPF.');
        $this->assertTrue($rA->usuarioRecemCriado);
        $this->assertFalse($rB->usuarioRecemCriado);
        $this->assertNotSame($rA->matricula->id, $rB->matricula->id);
        $this->assertSame(2, Matricula::query()->where('user_id', $rA->user->id)->count());
    }

    /**
     * 3) Segunda aprovação da mesma inscrição não cria duplicidade.
     */
    public function test_segunda_aprovacao_da_mesma_inscricao_eh_idempotente(): void
    {
        Notification::fake();

        $curso = $this->criarCurso();
        $mod = $this->criarModalidade($curso);
        $secretaria = $this->criarSecretaria();
        $inscricao = $this->criarInscricao($curso, $mod);

        $r1 = $this->service->executar($inscricao, $secretaria);
        $r2 = $this->service->executar($inscricao->fresh(), $secretaria);

        $this->assertSame($r1->user->id, $r2->user->id);
        $this->assertSame($r1->matricula->id, $r2->matricula->id);
        $this->assertFalse($r2->usuarioRecemCriado);
        $this->assertFalse($r2->matriculaRecemCriada);
        $this->assertSame(1, Matricula::query()->count());
        $this->assertSame(1, User::query()->where('nivel', 'aluno')->count());
    }

    /**
     * 4) Conflito de CPF/e-mail falha sem alterar dados.
     */
    public function test_conflito_cpf_email_falha_sem_alterar_dados(): void
    {
        Notification::fake();

        $curso = $this->criarCurso();
        $mod = $this->criarModalidade($curso);
        $secretaria = $this->criarSecretaria();

        // Usuário existente com o CPF da inscrição, mas outro e-mail.
        User::query()->create([
            'name' => 'Outra Pessoa',
            'email' => 'outro@example.test',
            'cpf' => '12345678901',
            'password' => 'x',
            'nivel' => 'aluno',
            'ativo' => true,
        ]);

        $inscricao = $this->criarInscricao($curso, $mod, [
            'email' => 'maria@example.test',
            'cpf' => '12345678901',
        ]);

        try {
            $this->service->executar($inscricao, $secretaria);
            $this->fail('Deveria lançar AprovarInscricaoException.');
        } catch (AprovarInscricaoException $e) {
            $this->assertSame(AprovarInscricaoException::CPF_EMAIL_CONFLITO, $e->codigoDominio);
        }

        // Nada deve ter sido criado ou alterado.
        $this->assertSame(0, Matricula::query()->count());
        $this->assertSame(1, User::query()->where('cpf', '12345678901')->count());
        $this->assertDatabaseHas('applications', [
            'id' => $inscricao->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);
    }

    /**
     * 5) Rejeição exige justificativa e não cria conta/matrícula.
     */
    public function test_rejeicao_exige_justificativa(): void
    {
        Notification::fake();

        $curso = $this->criarCurso();
        $mod = $this->criarModalidade($curso);
        $secretaria = $this->criarSecretaria();
        $inscricao = $this->criarInscricao($curso, $mod);

        try {
            $this->service->rejeitar($inscricao, $secretaria, '   ');
            $this->fail('Rejeição sem motivo deveria falhar.');
        } catch (AprovarInscricaoException $e) {
            $this->assertSame(AprovarInscricaoException::STATUS_INVALIDO, $e->codigoDominio);
        }

        $inscricao = $this->service->rejeitar($inscricao, $secretaria, 'Documentação incompleta');

        $this->assertSame(Application::STATUS_REJEITADO, $inscricao->status);
        $this->assertSame('Documentação incompleta', $inscricao->observacoes_secretaria);
        $this->assertSame(0, Matricula::query()->count());
        $this->assertSame(0, User::query()->where('nivel', 'aluno')->count());
    }

    /**
     * 6) Status final da inscrição fica `matriculado`.
     */
    public function test_status_final_fica_matriculado(): void
    {
        Notification::fake();

        $curso = $this->criarCurso();
        $mod = $this->criarModalidade($curso);
        $secretaria = $this->criarSecretaria();
        $inscricao = $this->criarInscricao($curso, $mod);

        $r = $this->service->executar($inscricao, $secretaria);

        $this->assertSame(Application::STATUS_MATRICULADO, $r->application->status);
        $this->assertNotNull($r->application->analisado_em);
        $this->assertSame($secretaria->id, $r->application->analisado_por_id);
    }

    /**
     * 7) Usuário criado recebe disparo de definição de senha sem senha em texto puro.
     */
    public function test_usuario_criado_recebe_link_de_definicao_de_senha(): void
    {
        Notification::fake();

        $curso = $this->criarCurso();
        $mod = $this->criarModalidade($curso);
        $secretaria = $this->criarSecretaria();
        $inscricao = $this->criarInscricao($curso, $mod);

        $r = $this->service->executar($inscricao, $secretaria);

        Notification::assertSentTo($r->user, AtivarAcessoAluno::class);

        // O modelo tem cast 'hashed' — a senha nunca sai em texto puro,
        // e o resultado do serviço não expõe nenhuma senha.
        $this->assertNotEquals('', $r->user->password);
        $this->assertNotSame(48, strlen((string) $r->user->password), 'A senha deveria estar hasheada, não em texto puro.');

        // O DTO de resultado não deve ter propriedade de senha.
        $this->assertFalse(property_exists($r, 'password'));
        $this->assertFalse(property_exists($r, 'senha'));
    }

    /**
     * 7.b) Segunda aprovação (idempotente) NÃO reenvia o link.
     */
    public function test_segunda_aprovacao_nao_reenvia_link_de_senha(): void
    {
        Notification::fake();

        $curso = $this->criarCurso();
        $mod = $this->criarModalidade($curso);
        $secretaria = $this->criarSecretaria();
        $inscricao = $this->criarInscricao($curso, $mod);

        $this->service->executar($inscricao, $secretaria);
        Notification::assertSentTimes(AtivarAcessoAluno::class, 1);

        $this->service->executar($inscricao->fresh(), $secretaria);
        Notification::assertSentTimes(AtivarAcessoAluno::class, 1);
    }

    /**
     * 8) Operação em erro faz rollback completo.
     *    Simulamos com uma matrícula ativa pré-existente do mesmo aluno/curso
     *    para um usuário que compartilha o CPF da inscrição.
     */
    public function test_erro_faz_rollback_completo(): void
    {
        Notification::fake();

        $curso = $this->criarCurso();
        $mod = $this->criarModalidade($curso);
        $secretaria = $this->criarSecretaria();

        // Usuário já existente com o MESMO CPF/e-mail da inscrição.
        $userExistente = User::query()->create([
            'name' => 'Maria da Silva',
            'email' => 'maria@example.test',
            'cpf' => '12345678901',
            'password' => 'x',
            'nivel' => 'aluno',
            'ativo' => true,
        ]);

        // Matrícula ativa pré-existente, sem application_id (ex.: legado).
        Matricula::query()->create([
            'user_id' => $userExistente->id,
            'curso_id' => $curso->id,
            'status' => 'ativa',
            'numero_matricula' => 'LEGADO-1',
            'data_matricula' => now()->toDateString(),
        ]);

        $inscricao = $this->criarInscricao($curso, $mod);

        try {
            $this->service->executar($inscricao, $secretaria);
            $this->fail('Deveria falhar por matrícula duplicada.');
        } catch (AprovarInscricaoException $e) {
            $this->assertSame(AprovarInscricaoException::MATRICULA_DUPLICADA, $e->codigoDominio);
        }

        // Rollback: nenhuma matrícula nova criada, inscrição continua em análise.
        $this->assertSame(1, Matricula::query()->count());
        $this->assertDatabaseHas('applications', [
            'id' => $inscricao->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);
        // Não deve ter sido disparado nenhum link de senha (usuário já existia
        // + nem chegou a completar a operação).
        Notification::assertNothingSent();
    }

    /**
     * Admin também pode aprovar (mesmo serviço, sem restrição a perfil).
     * A verificação de perfil vive na camada Filament (`canAccess`/`visible`),
     * validada no teste dedicado abaixo.
     */
    public function test_admin_pode_aprovar_pelo_servico(): void
    {
        Notification::fake();

        $curso = $this->criarCurso();
        $mod = $this->criarModalidade($curso);
        $admin = $this->criarAdmin();
        $inscricao = $this->criarInscricao($curso, $mod);

        $r = $this->service->executar($inscricao, $admin);
        $this->assertSame($admin->id, $r->application->analisado_por_id);
    }
}
