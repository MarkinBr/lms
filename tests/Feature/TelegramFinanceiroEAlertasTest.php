<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramFinanceiroEAlertasTest extends TestCase
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

    private function enviar(int $id, string $mensagem, int $chatId = 444): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-ficticio')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $id,
                'message' => ['chat' => ['id' => $chatId, 'type' => 'private'],
                    'from' => ['id' => $chatId], 'text' => $mensagem],
            ])->assertOk();
    }

    private function vincular(): User
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($admin);
        $this->enviar(1, '/vincular '.$codigo);

        return $admin;
    }

    public function test_financeiro_exige_vinculo_e_fornece_apenas_totais(): void
    {
        $this->enviar(10, '/financeiro');
        $this->assertStringContainsString('Acesso restrito', Http::recorded()->last()[0]['text']);

        $this->vincular();
        $this->enviar(2, '/financeiro');
        $mensagem = Http::recorded()->last()[0]['text'];
        $this->assertStringContainsString('Resumo financeiro', $mensagem);
        $this->assertStringContainsString('Pendentes', $mensagem);
        $this->assertStringNotContainsString('pix_copia_cola', $mensagem);
    }

    public function test_alerta_diario_opt_in_e_idempotente_e_revogavel(): void
    {
        $admin = $this->vincular();
        $this->enviar(2, '/alertas sim');
        Artisan::call('telegram:alertas-diarios');
        $this->assertDatabaseHas('telegram_outbox', ['user_id' => $admin->id, 'status' => 'pendente']);
        $this->assertSame(1, DB::table('telegram_outbox')->whereNotNull('evento_chave')->count());
        Artisan::call('telegram:alertas-diarios');
        $this->assertSame(1, DB::table('telegram_outbox')->whereNotNull('evento_chave')->count());

        $this->enviar(3, '/alertas nao');
        Artisan::call('telegram:outbox-drenar');
        $this->assertSame('descartado', DB::table('telegram_outbox')->whereNotNull('evento_chave')->value('status'));
        $this->assertDatabaseHas('telegram_alerta_preferencias', ['user_id' => $admin->id, 'ativo' => false]);
    }

    public function test_rotina_executa_apenas_telegram_e_respeita_horario(): void
    {
        $this->vincular();
        $this->enviar(2, '/alertas sim');
        $this->travelTo(Carbon::parse('2026-10-27 07:59', 'America/Sao_Paulo'));
        $this->artisan('telegram:rotinas', ['--uma-vez' => true])->assertExitCode(0);
        $this->assertSame(0, DB::table('telegram_outbox')->whereNotNull('evento_chave')->count());
        $this->travelTo(Carbon::parse('2026-10-27 08:01', 'America/Sao_Paulo'));
        $this->artisan('telegram:rotinas', ['--uma-vez' => true])->assertExitCode(0);
        $this->assertSame(1, DB::table('telegram_outbox')->whereNotNull('evento_chave')->where('status', 'entregue')->count());
        $this->artisan('telegram:rotinas', ['--uma-vez' => true])->assertExitCode(0);
        $this->assertSame(1, DB::table('telegram_outbox')->whereNotNull('evento_chave')->count());
    }
}
