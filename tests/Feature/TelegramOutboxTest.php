<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use App\Services\Telegram\TelegramOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOutboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'token-ficticio');
        config()->set('services.telegram.webhook_secret', 'segredo-ficticio');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function update(int $id, string $texto): array
    {
        return ['update_id' => $id, 'message' => [
            'chat' => ['id' => 551, 'type' => 'private'],
            'from' => ['id' => 551], 'text' => $texto,
        ]];
    }

    private function enviar(array $dados)
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-ficticio')
            ->postJson('/api/telegram/cetsadp/webhook', $dados);
    }

    public function test_falha_externa_nao_desfaz_vinculacao_nem_duplica_update(): void
    {
        $dono = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $dono->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($dono);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 503)]);
        $update = $this->update(1, '/vincular '.$codigo);
        $this->enviar($update)->assertOk();
        $this->assertDatabaseHas('telegram_vinculos', ['user_id' => $dono->id, 'chat_id' => '551', 'codigo_hash' => null]);
        $this->assertDatabaseHas('telegram_updates', ['update_id' => 1]);
        $this->assertDatabaseCount('telegram_outbox', 1);
        $this->enviar($update)->assertOk();
        $this->assertDatabaseCount('telegram_outbox', 1);
    }

    public function test_envio_bem_sucedido_limpa_texto_persistido(): void
    {
        $this->enviar($this->update(1, '/resumo'))->assertOk();
        $this->assertDatabaseHas('telegram_outbox', ['update_id' => 1, 'status' => 'entregue', 'payload' => null]);
    }

    public function test_revogacao_descarta_mensagem_academica_pendente(): void
    {
        $dono = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $dono->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($dono);
        $this->enviar($this->update(1, '/vincular '.$codigo))->assertOk();

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 503)]);
        $this->enviar($this->update(2, '/resumo'))->assertOk();
        app(TelegramAdministrativo::class)->revogar($dono);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        app(TelegramOutbox::class)->drenarPendentes();
        $this->assertDatabaseHas('telegram_outbox', ['update_id' => 2, 'status' => 'descartado', 'payload' => null]);
    }
}
