<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNavegacao35Test extends TestCase
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

    private function webhook(array $dados)
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-ficticio')
            ->postJson('/api/telegram/cetsadp/webhook', $dados);
    }

    private function vincular(): User
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($usuario);
        $this->webhook(['update_id' => 1, 'message' => [
            'chat' => ['id' => 733, 'type' => 'private'],
            'from' => ['id' => 733], 'text' => '/vincular '.$codigo,
        ]])->assertOk();

        return $usuario;
    }

    public function test_callback_do_painel_atualiza_mensagem_e_confirma_clique(): void
    {
        $this->vincular();
        $this->webhook(['update_id' => 2, 'callback_query' => [
            'id' => 'callback-2', 'from' => ['id' => 733],
            'message' => ['message_id' => 42, 'chat' => ['id' => 733, 'type' => 'private']],
            'data' => 'hoje',
        ]])->assertOk();

        Http::assertSent(fn ($r) => str_contains($r->url(), '/editMessageText')
            && ($r['chat_id'] ?? null) === '733'
            && ($r['message_id'] ?? null) === 42
            && str_contains($r['text'] ?? '', 'Painel do dia'));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/answerCallbackQuery')
            && ($r['callback_query_id'] ?? null) === 'callback-2');
    }

    public function test_callback_revogado_nao_consulta_dados(): void
    {
        $usuario = $this->vincular();
        app(TelegramAdministrativo::class)->revogar($usuario);
        $this->webhook(['update_id' => 2, 'callback_query' => [
            'id' => 'callback-2', 'from' => ['id' => 733],
            'message' => ['message_id' => 42, 'chat' => ['id' => 733, 'type' => 'private']],
            'data' => 'hoje',
        ]])->assertOk();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/editMessageText'));
    }
}
