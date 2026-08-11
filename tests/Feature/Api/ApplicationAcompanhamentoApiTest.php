<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationAcompanhamentoApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria uma inscrição real e retorna [Application, Curso, CourseModality].
     *
     * @return array{0: Application, 1: Curso, 2: CourseModality}
     */
    private function criarInscricao(): array
    {
        $curso = Curso::query()->create([
            'titulo' => 'Curso Médio em Teologia',
            'ativo' => true,
        ]);

        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'EAD',
            'ativo' => true,
        ]);

        $application = Application::create([
            'protocol' => 'INSC-TESTE123-20260101',
            'nome_completo' => 'Maria da Silva',
            'email' => 'maria@example.test',
            'telefone' => '11999999999',
            'cpf' => '12345678901',
            'rg' => '123456789',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'escolaridade' => 'medio_completo',
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'numero' => '1',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);

        return [$application, $curso, $modalidade];
    }

    public function test_consulta_inscricao_com_protocolo_e_email_corretos(): void
    {
        [$application, $curso, $modalidade] = $this->criarInscricao();

        $response = $this->postJson('/api/v1/applications/acompanhamento', [
            'protocol' => $application->protocol,
            'email' => 'maria@example.test',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.protocol', $application->protocol)
            ->assertJsonPath('data.curso.id', $curso->id)
            ->assertJsonPath('data.curso.titulo', 'Curso Médio em Teologia')
            ->assertJsonPath('data.modalidade.id', $modalidade->id)
            ->assertJsonPath('data.modalidade.nome', 'EAD')
            ->assertJsonPath('data.status', 'em_analise')
            ->assertJsonPath('data.status_label', 'Em análise pela secretaria');

        $this->assertNotEmpty($response->json('data.data_inscricao'));
    }

    public function test_rejeita_email_incorreto_sem_vazar_dados(): void
    {
        [$application] = $this->criarInscricao();

        $response = $this->postJson('/api/v1/applications/acompanhamento', [
            'protocol' => $application->protocol,
            'email' => 'outra.pessoa@example.test',
        ]);

        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'INSCRICAO_NAO_ENCONTRADA')
            ->assertJsonPath(
                'error.message',
                'Não foi possível localizar a inscrição. Verifique o protocolo e o e-mail informados.',
            );

        // A resposta não pode conter nenhum dado pessoal
        $response->assertJsonMissingPath('data');
        $corpo = $response->getContent();
        $this->assertStringNotContainsString('12345678901', $corpo);
        $this->assertStringNotContainsString('Maria da Silva', $corpo);
        $this->assertStringNotContainsString('maria@example.test', $corpo);
    }

    public function test_protocolo_inexistente_retorna_mesma_mensagem_generica(): void
    {
        $this->criarInscricao();

        $response = $this->postJson('/api/v1/applications/acompanhamento', [
            'protocol' => 'INSC-NAOEXISTE-20260101',
            'email' => 'maria@example.test',
        ]);

        // Mesma resposta de quando só o e-mail está errado: não revela se o protocolo existe
        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'INSCRICAO_NAO_ENCONTRADA')
            ->assertJsonPath(
                'error.message',
                'Não foi possível localizar a inscrição. Verifique o protocolo e o e-mail informados.',
            );
    }

    public function test_resposta_de_acompanhamento_nao_contem_dados_pessoais(): void
    {
        [$application] = $this->criarInscricao();

        $response = $this->postJson('/api/v1/applications/acompanhamento', [
            'protocol' => $application->protocol,
            'email' => 'maria@example.test',
        ]);

        $response->assertOk();

        // Campos sensíveis não podem existir na resposta
        $dados = $response->json('data');
        $this->assertArrayNotHasKey('cpf', $dados);
        $this->assertArrayNotHasKey('rg', $dados);
        $this->assertArrayNotHasKey('email', $dados);
        $this->assertArrayNotHasKey('telefone', $dados);
        $this->assertArrayNotHasKey('cep', $dados);
        $this->assertArrayNotHasKey('logradouro', $dados);
        $this->assertArrayNotHasKey('endereco', $dados);
        $this->assertArrayNotHasKey('documentos', $dados);
        $this->assertArrayNotHasKey('documents', $dados);
        $this->assertArrayNotHasKey('observacoes_secretaria', $dados);

        // Conferência extra no corpo bruto da resposta
        $corpo = $response->getContent();
        $this->assertStringNotContainsString('12345678901', $corpo);
        $this->assertStringNotContainsString('123456789', $corpo); // RG
        $this->assertStringNotContainsString('11999999999', $corpo); // telefone
        $this->assertStringNotContainsString('01001-000', $corpo); // CEP
        $this->assertStringNotContainsString('Praça da Sé', $corpo); // logradouro
    }

    public function test_antiga_rota_por_protocolo_nao_existe_mais(): void
    {
        [$application] = $this->criarInscricao();

        $this->getJson('/api/v1/applications/by-protocol/'.$application->protocol)
            ->assertStatus(404);
    }
}
