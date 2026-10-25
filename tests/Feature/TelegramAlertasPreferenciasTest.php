<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAlertasPreferenciasTest extends TestCase
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

    private function enviar(int $id, string $texto, int $chat = 444, string $tipo = 'private')
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-ficticio')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $id,
                'message' => ['chat' => ['id' => $chat, 'type' => $tipo],
                    'from' => ['id' => $chat], 'text' => $texto],
            ]);
    }

    public function test_preferencia_requer_dono_vinculado_e_opt_in_explicito(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);

        $this->enviar(1, '/alertas sim')->assertOk();
        $this->assertDatabaseCount('telegram_alerta_preferencias', 0);

        $codigo = app(TelegramAdministrativo::class)->criarCodigo($usuario);
        $this->enviar(2, '/vincular '.$codigo)->assertOk();
        $this->enviar(3, '/alertas')->assertOk();
        $this->assertStringContainsString('desativados', Http::recorded()->last()[0]['text']);

        $this->enviar(4, '/alertas sim')->assertOk();
        $this->assertDatabaseHas('telegram_alerta_preferencias', ['user_id' => $usuario->id, 'ativo' => true]);
        $this->assertStringContainsString('envio automático ainda não está habilitado', mb_strtolower(Http::recorded()->last()[0]['text']));

        $this->enviar(5, '/alertas nao')->assertOk();
        $this->assertDatabaseHas('telegram_alerta_preferencias', ['user_id' => $usuario->id, 'ativo' => false]);
    }

    public function test_grupo_e_revogacao_nao_podem_alterar_preferencias(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($usuario);
        $this->enviar(1, '/vincular '.$codigo)->assertOk();

        $this->enviar(2, '/alertas sim', 444, 'group')->assertOk();
        $this->assertDatabaseCount('telegram_alerta_preferencias', 0);

        app(TelegramAdministrativo::class)->revogar($usuario);
        $this->enviar(3, '/alertas sim')->assertOk();
        $this->assertDatabaseCount('telegram_alerta_preferencias', 0);
    }
}
