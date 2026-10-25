<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAdministrativoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'teste-ficticio');
        config()->set('services.telegram.webhook_secret', 'segredo-teste');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function updateTelegram(int $id, string $texto, int $chat = 123, string $type = 'private'): array
    {
        return ['update_id' => $id, 'message' => [
            'chat' => ['id' => $chat, 'type' => $type],
            'from' => ['id' => $chat],
            'text' => $texto,
        ]];
    }

    private function enviar(array $update, ?string $secret = 'segredo-teste')
    {
        return $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => $secret ?? ''])
            ->postJson('/api/telegram/cetsadp/webhook', $update);
    }

    public function test_rejeita_webhook_sem_segredo_e_nao_envia_resposta(): void
    {
        $this->enviar($this->updateTelegram(1, '/start'), 'errado')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_impede_consultas_sem_vinculacao(): void
    {
        $this->enviar($this->updateTelegram(1, '/resumo'))->assertOk();
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Acesso restrito'));
    }

    public function test_nao_processa_grupos_nem_updates_repetidos(): void
    {
        $this->enviar($this->updateTelegram(1, '/start', 123, 'group'))->assertOk();
        $this->enviar($this->updateTelegram(1, '/start', 123, 'group'))->assertOk();
        $this->assertSame(1, DB::table('telegram_updates')->count());
        Http::assertNothingSent();
    }

    public function test_pareamento_admin_consulta_e_revogacao(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $this->actingAs($usuario)->post('/admin/telegram/gerar')->assertRedirect();
        $hash = DB::table('telegram_vinculos')->first()->codigo_hash;
        $this->assertSame(64, strlen($hash));

        // Os códigos são entregues pela sessão ao administrador, não persistidos em claro.
        $codigo = session('telegram_codigo');
        $this->assertSame(hash('sha256', $codigo), $hash);
        $this->enviar($this->updateTelegram(2, '/vincular '.$codigo))->assertOk();
        $this->assertNull(DB::table('telegram_vinculos')->first()->codigo_hash);
        $this->enviar($this->updateTelegram(3, '/resumo'))->assertOk();
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Resumo acadêmico'));
        $this->actingAs($usuario)->post('/admin/telegram/revogar')->assertRedirect();
        $this->assertSame(0, DB::table('telegram_vinculos')->count());
    }

    public function test_codigo_expirado_ou_usuario_inativo_nao_vincula(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $codigo = app(\App\Services\Telegram\TelegramAdministrativo::class)->criarCodigo($usuario);
        DB::table('telegram_vinculos')->where('user_id', $usuario->id)
            ->update(['codigo_expira_em' => now()->subMinute()]);
        $this->enviar($this->updateTelegram(4, '/vincular '.$codigo))->assertOk();
        $this->assertNull(DB::table('telegram_vinculos')->first()->chat_id);
    }

    public function test_nao_admin_nao_gera_codigo(): void
    {
        $usuario = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->actingAs($usuario)->post('/admin/telegram/gerar')->assertForbidden();
    }
}
