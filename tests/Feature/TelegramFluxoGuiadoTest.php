<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramFluxoGuiadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'token-ficticio');
        config()->set('services.telegram.webhook_secret', 'segredo-teste');
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function mensagem(int $updateId, string $texto): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-teste')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $updateId,
                'message' => [
                    'chat' => ['id' => 123, 'type' => 'private'],
                    'from' => ['id' => 123],
                    'text' => $texto,
                ],
            ])->assertOk();
    }

    private function botao(int $updateId, string $codigo): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-teste')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $updateId,
                'callback_query' => [
                    'id' => 'cb-'.$updateId,
                    'from' => ['id' => 123],
                    'message' => ['message_id' => 200, 'chat' => ['id' => 123, 'type' => 'private']],
                    'data' => $codigo,
                ],
            ])->assertOk();
    }

    private function vincular(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($usuario);
        $this->mensagem(1, '/vincular '.$codigo);
    }

    private function saida(int $updateId): array
    {
        $texto = DB::table('telegram_outbox')->where('update_id', $updateId)->value('payload');

        return json_decode($texto, true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_menu_mostra_areas_e_cadastro_guiado_sem_exigir_comando(): void
    {
        $this->vincular();
        $this->mensagem(2, '/menu');
        $menu = $this->saida(2);

        $this->assertStringContainsString('Painel administrativo', $menu['text']);
        $this->assertSame('area:alunos', $menu['reply_markup']['inline_keyboard'][0][0]['callback_data']);

        $this->botao(3, 'area:alunos');
        $this->assertStringContainsString('Gestão de alunos', $this->saida(3)['text']);
        $this->botao(4, 'fluxo:novoaluno');
        $this->assertStringContainsString('1 de 3', $this->saida(4)['text']);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
    }

    public function test_cadastro_por_etapas_so_cria_aluno_apos_confirmacao(): void
    {
        $this->vincular();
        $this->botao(2, 'fluxo:novoaluno');
        $this->mensagem(3, 'Aluno Teste CETSADP');
        $this->assertStringContainsString('2 de 3', $this->saida(3)['text']);

        $this->botao(4, 'fluxo:pular');
        $this->assertStringContainsString('3 de 3', $this->saida(4)['text']);
        $this->botao(5, 'fluxo:pular');
        $this->assertStringContainsString('Revisar cadastro', $this->saida(5)['text']);

        $this->assertDatabaseMissing('users', ['name' => 'Aluno Teste CETSADP']);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        $this->botao(6, 'fluxo:preparar');
        $previa = $this->saida(6);
        $this->assertStringContainsString('Cadastrar aluno ativo', $previa['text']);
        $this->assertDatabaseCount('telegram_confirmacoes', 1);
        $this->assertDatabaseMissing('users', ['name' => 'Aluno Teste CETSADP']);

        $confirmar = $previa['reply_markup']['inline_keyboard'][0][0]['callback_data'];
        $this->assertStringStartsWith('confirmar:', $confirmar);
        $this->botao(7, $confirmar);
        $this->assertDatabaseHas('users', [
            'name' => 'Aluno Teste CETSADP',
            'nivel' => 'aluno',
            'ativo' => true,
        ]);
    }

    public function test_voltar_cancelar_e_dados_invalidos_nao_geram_confirmacao(): void
    {
        $this->vincular();
        $this->botao(2, 'fluxo:novoaluno');
        $this->mensagem(3, 'X');
        $this->assertStringContainsString('2 a 255 caracteres', $this->saida(3)['text']);
        $this->mensagem(4, 'Aluna Exemplo');
        $this->mensagem(5, 'invalido');
        $this->assertStringContainsString('e-mail válido', $this->saida(5)['text']);

        $this->botao(6, 'fluxo:voltar');
        $this->assertStringContainsString('1 de 3', $this->saida(6)['text']);
        $this->botao(7, 'fluxo:cancelar');
        $this->assertStringContainsString('Nenhum aluno foi criado', $this->saida(7)['text']);
        $this->mensagem(8, 'Outro nome');
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        $this->assertDatabaseMissing('users', ['name' => 'Aluna Exemplo']);
    }

    public function test_rascunho_expirado_nao_permite_criar_aluno(): void
    {
        $this->vincular();
        $this->botao(2, 'fluxo:novoaluno');
        $this->mensagem(3, 'Aluno Expirado');
        $this->botao(4, 'fluxo:pular');
        $this->botao(5, 'fluxo:pular');

        $this->travel(16)->minutes();
        $this->botao(6, 'fluxo:preparar');
        $this->assertStringContainsString('expirou', $this->saida(6)['text']);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
    }

    public function test_chat_sem_vinculo_nao_abre_fluxo(): void
    {
        $this->botao(2, 'fluxo:novoaluno');
        $this->assertStringContainsString('Acesso restrito', $this->saida(2)['text']);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
    }
}
