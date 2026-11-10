<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\User;
use App\Policies\ApplicationDocumentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationDocumentDownloadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disco privado em memória; o público fica apenas para asserts de ausência.
        config()->set('filesystems.applications_disk', 'applications');
        Storage::fake('applications');
        Storage::fake('public');
    }

    public function test_upload_grava_no_disco_privado_e_nao_toca_no_public(): void
    {
        $user = User::factory()->create([
            'email' => 'dona@example.test',
            'ativo' => true,
            'nivel' => 'aluno',
        ]);
        $application = $this->criarInscricao(['email' => $user->email]);

        $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/applications/{$application->id}/documents",
            [
                'tipo' => 'rg',
                'arquivo' => UploadedFile::fake()->image('rg.jpg'),
            ],
        )->assertCreated();

        $document = ApplicationDocument::sole();

        Storage::disk('applications')->assertExists($document->caminho_arquivo);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_json_de_upload_nao_expoe_caminho_nem_url_publica(): void
    {
        $user = User::factory()->create([
            'email' => 'dona@example.test',
            'ativo' => true,
            'nivel' => 'aluno',
        ]);
        $application = $this->criarInscricao(['email' => $user->email]);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/applications/{$application->id}/documents",
            [
                'tipo' => 'rg',
                'arquivo' => UploadedFile::fake()->image('rg.jpg'),
            ],
        )->assertCreated();

        $dados = $response->json('data');
        $this->assertArrayNotHasKey('caminho_arquivo', $dados);
        $this->assertArrayNotHasKey('url_publica', $dados);
        $this->assertStringNotContainsString('/storage/applications/', $response->getContent());
    }

    public function test_dono_ativo_baixa_o_proprio_documento(): void
    {
        [$user, $application, $document] = $this->prepararDocumento();

        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertOk();
    }

    public function test_outro_candidato_nao_baixa(): void
    {
        [, $application, $document] = $this->prepararDocumento();
        $terceiro = User::factory()->create([
            'email' => 'terceiro@example.test',
            'ativo' => true,
            'nivel' => 'aluno',
        ]);

        $this->actingAs($terceiro, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertForbidden();
    }

    public function test_usuario_inativo_nao_baixa_mesmo_sendo_dono(): void
    {
        [$user, $application, $document] = $this->prepararDocumento();
        $user->update(['ativo' => false]);

        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertForbidden();
    }

    public function test_visitante_nao_baixa(): void
    {
        // Monta o documento direto no banco/storage, sem nunca autenticar
        // (actingAs persistiria entre requests do mesmo teste e mascararia o 401).
        [$application, $document] = $this->prepararDocumentoSemAutenticar();

        $this->getJson("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertUnauthorized();
    }

    public function test_secretario_ativo_baixa(): void
    {
        [, $application, $document] = $this->prepararDocumento();
        $secretario = User::factory()->create([
            'email' => 'sec@cetsadp.test',
            'ativo' => true,
            'nivel' => 'secretario',
        ]);

        $this->actingAs($secretario, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertOk();
    }

    public function test_admin_ativo_baixa(): void
    {
        [, $application, $document] = $this->prepararDocumento();
        $admin = User::factory()->create(['ativo' => true, 'nivel' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertOk();
    }

    public function test_secretario_inativo_nao_baixa(): void
    {
        [, $application, $document] = $this->prepararDocumento();
        $secretario = User::factory()->create([
            'ativo' => false,
            'nivel' => 'secretario',
        ]);

        $this->actingAs($secretario, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertForbidden();
    }

    public function test_documento_de_outra_inscricao_na_mesma_url_retorna_404_sem_metadados(): void
    {
        [$user, $application] = $this->prepararDocumento();

        $outraApp = $this->criarInscricao(['email' => 'outra@example.test']);
        $docDoOutro = ApplicationDocument::create([
            'application_id' => $outraApp->id,
            'tipo' => 'rg',
            'nome_original' => 'rg.jpg',
            'nome_armazenado' => 'rg.jpg',
            'mime_type' => 'image/jpeg',
            'tamanho' => 100,
            'caminho_arquivo' => "applications/{$outraApp->id}/rg_".Str::uuid().'.jpg',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$docDoOutro->id}");

        $response->assertNotFound();
        $this->assertStringNotContainsString($docDoOutro->caminho_arquivo, $response->getContent());
        $this->assertStringNotContainsString('storage/', $response->getContent());
    }

    public function test_documento_inexistente_retorna_404(): void
    {
        [$user, $application] = $this->prepararDocumento();

        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/".Str::uuid())
            ->assertNotFound();
    }

    public function test_caminho_adulterado_com_traversal_retorna_404(): void
    {
        [$user, $application, $document] = $this->prepararDocumento();

        // Simula estado corrompido no banco (não deveria ocorrer via upload).
        $document->update([
            'caminho_arquivo' => 'applications/'.$application->id.'/../../../etc/passwd',
        ]);

        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/applications/{$application->id}/documents/{$document->id}")
            ->assertNotFound();
    }

    public function test_show_da_secretaria_nao_expoe_caminho_arquivo_nem_url_publica(): void
    {
        [, $application, $document] = $this->prepararDocumento();
        $secretario = User::factory()->create([
            'ativo' => true,
            'nivel' => 'secretario',
        ]);

        $response = $this->actingAs($secretario, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}")
            ->assertOk();

        $corpo = $response->getContent();
        $this->assertStringNotContainsString($document->caminho_arquivo, $corpo);
        $this->assertStringNotContainsString('url_publica', $corpo);
        $this->assertStringNotContainsString('/storage/applications/', $corpo);
    }

    public function test_config_invalida_nao_cai_silenciosamente_em_public(): void
    {
        // Configuração propositalmente errada: aponta para o disco público.
        config()->set('filesystems.applications_disk', 'public');

        $user = User::factory()->create([
            'email' => 'dona@example.test',
            'ativo' => true,
            'nivel' => 'aluno',
        ]);
        $application = $this->criarInscricao(['email' => $user->email]);

        $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/applications/{$application->id}/documents",
            [
                'tipo' => 'rg',
                'arquivo' => UploadedFile::fake()->image('rg.jpg'),
            ],
        )->assertServerError();

        $this->assertEmpty(Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('application_documents', 0);
    }

    public function test_policy_e_resolvida_por_autodiscovery(): void
    {
        // Garante que NÃO precisamos de AuthServiceProvider: o Laravel resolve
        // App\Models\ApplicationDocument -> App\Policies\ApplicationDocumentPolicy
        // por autodiscovery, e o Gate decide via o método download().
        $policy = Gate::getPolicyFor(ApplicationDocument::class);

        $this->assertNotNull($policy);
        $this->assertInstanceOf(ApplicationDocumentPolicy::class, $policy);

        // Decisão ponta-a-ponta via Gate (sem registro manual de ability):
        $dono = User::factory()->create(['email' => 'dona@example.test', 'ativo' => true, 'nivel' => 'aluno']);
        $application = $this->criarInscricao(['email' => $dono->email]);
        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'tipo' => 'rg',
            'nome_original' => 'rg.jpg',
            'nome_armazenado' => 'rg.jpg',
            'mime_type' => 'image/jpeg',
            'tamanho' => 100,
            'caminho_arquivo' => "applications/{$application->id}/rg_x.jpg",
        ]);

        $this->assertTrue($dono->can('download', $document));

        $terceiro = User::factory()->create(['email' => 'terceiro@example.test', 'ativo' => true, 'nivel' => 'aluno']);
        $this->assertFalse($terceiro->can('download', $document));
    }

    /** @return array{0: User, 1: Application, 2: ApplicationDocument} */
    private function prepararDocumento(): array
    {
        $user = User::factory()->create([
            'email' => 'dona@example.test',
            'ativo' => true,
            'nivel' => 'aluno',
        ]);
        $application = $this->criarInscricao(['email' => $user->email]);

        $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/applications/{$application->id}/documents",
            [
                'tipo' => 'rg',
                'arquivo' => UploadedFile::fake()->image('rg.jpg'),
            ],
        )->assertCreated();

        return [$user, $application->fresh(), ApplicationDocument::sole()];
    }

    /** @return array{0: Application, 1: ApplicationDocument} */
    private function prepararDocumentoSemAutenticar(): array
    {
        $application = $this->criarInscricao(['email' => 'dona@example.test']);
        $caminho = "applications/{$application->id}/rg_".Str::uuid().'.jpg';
        Storage::disk('applications')->put($caminho, 'conteudo-fake');

        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'tipo' => 'rg',
            'nome_original' => 'rg.jpg',
            'nome_armazenado' => basename($caminho),
            'mime_type' => 'image/jpeg',
            'tamanho' => 100,
            'caminho_arquivo' => $caminho,
        ]);

        return [$application, $document];
    }

    private function criarInscricao(array $overrides = []): Application
    {
        $curso = Curso::query()->create(['titulo' => 'Curso de Teste', 'ativo' => true]);
        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'Modalidade',
            'ativo' => true,
        ]);

        return Application::query()->create(array_merge([
            'protocol' => 'INSC-'.Str::upper(Str::random(8)),
            'nome_completo' => 'Maria da Silva',
            'cpf' => Str::random(11),
            'data_nascimento' => '1995-05-10',
            'sexo' => 'F',
            'email' => 'dona@example.test',
            'telefone' => '21999999999',
            'cep' => '23890-000',
            'logradouro' => 'Rua Teste',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'Seropédica',
            'estado' => 'RJ',
            'escolaridade' => 'medio_completo',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ], $overrides));
    }
}
