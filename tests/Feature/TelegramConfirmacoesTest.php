<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramConfirmacoesTest extends TestCase
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

    private function enviar(array $update)
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-falso')
            ->postJson('/api/telegram/cetsadp/webhook', $update);
    }

    private function mensagem(int $updateId, string $texto, string $tipo = 'private'): array
    {
        return ['update_id' => $updateId, 'message' => [
            'chat' => ['id' => 707, 'type' => $tipo],
            'from' => ['id' => 707], 'text' => $texto,
        ]];
    }

    private function telegramCallback(int $updateId, string $dado, string $tipo = 'private'): array
    {
        return ['update_id' => $updateId, 'callback_query' => [
            'id' => 'callback-'.$updateId, 'from' => ['id' => 707],
            'message' => ['message_id' => 31, 'chat' => ['id' => 707, 'type' => $tipo]],
            'data' => $dado,
        ]];
    }

    private function vincular(): User
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($admin);
        $this->enviar($this->mensagem(1, '/vincular '.$codigo))->assertOk();

        return $admin;
    }

    private function codigo(): string
    {
        $this->enviar($this->mensagem(2, '/testeconfirmacao'))->assertOk();
        $ultimo = Http::recorded()->last()[0];
        $this->assertStringContainsString('Simulação administrativa', $ultimo['text']);
        $teclado = $ultimo['reply_markup']['inline_keyboard'];

        return $teclado[0][0]['callback_data'];
    }

    public function test_confirma_uma_vez_mesmo_com_callbacks_diferentes(): void
    {
        $this->vincular();
        $codigo = $this->codigo();
        $this->assertDatabaseCount('telegram_confirmacoes', 1);
        $this->enviar($this->telegramCallback(3, $codigo))->assertOk();
        $this->enviar($this->telegramCallback(4, $codigo))->assertOk();
        $this->assertDatabaseHas('telegram_confirmacoes', ['status' => 'confirmado']);
        $this->assertDatabaseCount('telegram_auditoria', 2);
        $this->assertDatabaseCount('matriculas', 0);
    }

    public function test_cancelamento_impede_confirmacao_posterior(): void
    {
        $this->vincular();
        $confirmar = $this->codigo();
        $cancelar = str_replace('confirmar:', 'cancelar:', $confirmar);
        $this->enviar($this->telegramCallback(3, $cancelar))->assertOk();
        $this->enviar($this->telegramCallback(4, $confirmar))->assertOk();
        $this->assertDatabaseHas('telegram_confirmacoes', ['status' => 'cancelado']);
        $this->assertDatabaseCount('telegram_auditoria', 2);
    }

    public function test_expiracao_e_revogacao_bloqueiam_confirmacao(): void
    {
        $admin = $this->vincular();
        $codigo = $this->codigo();
        DB::table('telegram_confirmacoes')->update(['expira_em' => now()->subMinute()]);
        $this->enviar($this->telegramCallback(3, $codigo))->assertOk();
        $this->assertDatabaseHas('telegram_confirmacoes', ['status' => 'expirado']);

        $this->enviar($this->mensagem(4, '/testeconfirmacao'))->assertOk();
        app(TelegramAdministrativo::class)->revogar($admin);
        $this->assertDatabaseMissing('telegram_confirmacoes', ['status' => 'pendente']);
    }

    public function test_grupo_nao_cria_acao(): void
    {
        $this->vincular();
        $this->enviar($this->mensagem(2, '/testeconfirmacao', 'group'))->assertOk();
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
    }
}
