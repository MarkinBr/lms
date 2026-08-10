<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationDocumentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_owner_can_upload_document_to_application_under_review(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['email' => 'candidata@example.com']);
        $application = $this->createApplicationEmAnalise(['email' => $user->email]);

        $response = $this->actingAs($user, 'sanctum')->post(
            "/api/v1/applications/{$application->id}/documents",
            [
                'tipo' => 'rg',
                'arquivo' => UploadedFile::fake()->image('rg.jpg'),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('application_documents', [
            'application_id' => $application->id,
            'tipo' => 'rg',
            'nome_original' => 'rg.jpg',
        ]);

        $document = ApplicationDocument::query()
            ->where('application_id', $application->id)
            ->sole();

        $this->assertGreaterThan(0, $document->tamanho);
        Storage::disk('public')->assertExists($document->caminho_arquivo);
    }

    public function test_upload_is_rejected_when_authenticated_user_does_not_own_the_application(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['email' => 'dona-da-conta@example.com']);
        $application = $this->createApplicationEmAnalise(['email' => 'outra-pessoa@example.com']);

        $response = $this->actingAs($user, 'sanctum')->post(
            "/api/v1/applications/{$application->id}/documents",
            [
                'tipo' => 'rg',
                'arquivo' => UploadedFile::fake()->image('rg.jpg'),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('application');
        $this->assertDatabaseCount('application_documents', 0);
    }

    private function createApplicationEmAnalise(array $overrides = []): Application
    {
        $curso = Curso::query()->create(['titulo' => 'Curso de Teste', 'ativo' => true]);
        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'Modalidade Teste',
            'ativo' => true,
        ]);

        return Application::query()->create(array_merge([
            'protocol' => 'INSC-'.Str::upper(Str::random(8)),
            'nome_completo' => 'Maria da Silva',
            'cpf' => Str::random(11),
            'data_nascimento' => '1995-05-10',
            'sexo' => 'F',
            'email' => 'candidata@example.com',
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
