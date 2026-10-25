<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramPreAnaliseInscricaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'token-falso');
        config()->set('services.telegram.webhook_secret', 'segredo-falso');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function enviar(int $id, string $texto): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-falso')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $id, 'message' => [
                    'chat' => ['id' => 707, 'type' => 'private'],
                    'from' => ['id' => 707], 'text' => $texto,
                ],
            ])->assertOk();
    }

    private function clicar(int $id, string $callback): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-falso')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $id, 'callback_query' => [
                    'id' => 'cb-'.$id, 'from' => ['id' => 707],
                    'message' => ['chat' => ['id' => 707, 'type' => 'private'], 'message_id' => 31],
                    'data' => $callback,
                ],
            ])->assertOk();
    }

    private function prepararAmbiente(): Application
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($admin);
        $this->enviar(1, '/vincular '.$codigo);
        $curso = Curso::query()->create(['titulo' => 'Teologia', 'ativo' => true]);
        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id, 'nome' => 'EAD', 'ativo' => true,
        ]);

        return Application::query()->create([
            'protocol' => 'REVISAO-TESTE-1', 'nome_completo' => 'Candidata de Ensaio',
            'cpf' => '12345678901', 'data_nascimento' => '1990-01-15',
            'sexo' => 'F', 'nacionalidade' => 'Brasileira',
            'email' => 'candidata@exemplo.test', 'telefone' => '11999999999',
            'cep' => '01001000', 'logradouro' => 'Rua Exemplo',
            'numero' => '1', 'bairro' => 'Centro', 'cidade' => 'São Paulo',
            'estado' => 'SP', 'escolaridade' => 'medio_completo',
            'curso_id' => $curso->id, 'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);
    }

    private function comandoRevisao(Application $inscricao): string
    {
        $this->enviar(2, '/revisarinscricao '.$inscricao->id);
        $requisicao = Http::recorded()->last()[0];
        $this->assertStringContainsString('Pré-análise', $requisicao['text']);
        $this->assertStringNotContainsString('12345678901', $requisicao['text']);
        $this->assertStringNotContainsString('candidata@exemplo.test', $requisicao['text']);

        return $requisicao['reply_markup']['inline_keyboard'][0][0]['callback_data'];
    }

    public function test_registra_revisao_sem_aprovar_ou_matricular(): void
    {
        $inscricao = $this->prepararAmbiente();
        $botao = $this->comandoRevisao($inscricao);
        $this->clicar(3, $botao);
        $this->clicar(4, $botao);
        $this->assertDatabaseHas('telegram_confirmacoes', [
            'acao' => 'revisao_inscricao', 'status' => 'confirmado',
        ]);
        $this->assertDatabaseHas('applications', [
            'id' => $inscricao->id, 'status' => Application::STATUS_EM_ANALISE,
        ]);
        $this->assertDatabaseCount('matriculas', 0);
        $this->assertDatabaseCount('telegram_auditoria', 2);
    }

    public function test_alteracao_de_status_invalida_revisao_antiga(): void
    {
        $inscricao = $this->prepararAmbiente();
        $botao = $this->comandoRevisao($inscricao);
        $inscricao->update(['status' => Application::STATUS_CANCELADO]);
        $this->clicar(3, $botao);
        $this->assertDatabaseHas('telegram_confirmacoes', [
            'acao' => 'revisao_inscricao', 'status' => 'obsoleto',
        ]);
        $this->assertDatabaseHas('applications', [
            'id' => $inscricao->id, 'status' => Application::STATUS_CANCELADO,
        ]);
    }

    public function test_documento_adicionado_invalida_revisao(): void
    {
        $inscricao = $this->prepararAmbiente();
        $botao = $this->comandoRevisao($inscricao);
        ApplicationDocument::query()->create([
            'application_id' => $inscricao->id,
            'tipo' => 'outro', 'nome_original' => 'teste.pdf',
            'nome_armazenado' => 'teste.pdf', 'mime_type' => 'application/pdf',
            'tamanho' => 20, 'caminho_arquivo' => 'isolado/teste.pdf', 'validado' => false,
        ]);
        $this->clicar(3, $botao);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'revisao_inscricao', 'status' => 'obsoleto']);
        $this->assertDatabaseCount('matriculas', 0);
    }

    public function test_documento_removido_ou_validado_invalida_revisao(): void
    {
        $inscricao = $this->prepararAmbiente();
        $documento = ApplicationDocument::query()->create([
            'application_id' => $inscricao->id,
            'tipo' => 'outro', 'nome_original' => 'teste.pdf',
            'nome_armazenado' => 'teste.pdf', 'mime_type' => 'application/pdf',
            'tamanho' => 20, 'caminho_arquivo' => 'isolado/teste.pdf', 'validado' => false,
        ]);
        $botao = $this->comandoRevisao($inscricao);
        $documento->update(['validado' => true, 'validado_em' => now()]);
        $this->clicar(3, $botao);
        $this->assertDatabaseHas('telegram_confirmacoes', ['status' => 'obsoleto']);

        $this->enviar(4, '/revisarinscricao '.$inscricao->id);
        $botaoNovo = Http::recorded()->last()[0]['reply_markup']['inline_keyboard'][0][0]['callback_data'];
        $documento->delete();
        $this->clicar(5, $botaoNovo);
        $this->assertDatabaseHas('telegram_confirmacoes', [
            'acao' => 'revisao_inscricao', 'status' => 'obsoleto',
        ]);
        $this->assertDatabaseCount('matriculas', 0);
    }

    public function test_titulo_curso_e_nome_modalidade_invalidam_revisao(): void
    {
        $inscricao = $this->prepararAmbiente();
        $botao = $this->comandoRevisao($inscricao);
        $inscricao->curso->update(['titulo' => 'Novo título']);
        $this->clicar(3, $botao);
        $this->assertDatabaseHas('telegram_confirmacoes', ['status' => 'obsoleto']);

        $this->enviar(4, '/revisarinscricao '.$inscricao->id);
        $botaoNovo = Http::recorded()->last()[0]['reply_markup']['inline_keyboard'][0][0]['callback_data'];
        $inscricao->courseModality->update(['nome' => 'Outra modalidade']);
        $this->clicar(5, $botaoNovo);
        $this->assertSame(2, DB::table('telegram_confirmacoes')
            ->where('status', 'obsoleto')->count());
    }

    public function test_inscricao_ja_matriculada_nao_cria_proposta(): void
    {
        $inscricao = $this->prepararAmbiente();
        $inscricao->update(['status' => Application::STATUS_MATRICULADO]);
        $this->enviar(2, '/revisarinscricao '.$inscricao->id);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        $this->assertStringContainsString('não disponível', Http::recorded()->last()[0]['text']);
    }
}
