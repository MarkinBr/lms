<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\ConsultasAcademicasTelegram;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramRespostaProtegidaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'token-de-teste');
        config()->set('services.telegram.webhook_secret', 'segredo-de-teste');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function dono(): User
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $admin->id, 'chat_id' => '707',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $admin;
    }

    private function comando(int $updateId, string $texto): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-de-teste')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $updateId,
                'message' => [
                    'chat' => ['id' => 707, 'type' => 'private'],
                    'from' => ['id' => 707],
                    'text' => $texto,
                ],
            ])->assertOk();
    }

    public function test_revogacao_durante_consulta_nao_reclassifica_ficha_como_publica(): void
    {
        $admin = $this->dono();
        $this->app->instance(ConsultasAcademicasTelegram::class, new class($admin) extends ConsultasAcademicasTelegram
        {
            public function __construct(private User $admin) {}

            public function aluno(int $id): array
            {
                app(TelegramAdministrativo::class)->revogar($this->admin);

                return ['text' => 'FICHA CONFIDENCIAL: NOTA 9,5'];
            }
        });

        $this->comando(501, '/aluno 5');
        $this->assertDatabaseMissing('telegram_vinculos', ['user_id' => $admin->id]);
        $this->assertDatabaseHas('telegram_outbox', [
            'update_id' => 501, 'user_id' => null,
            'requer_autorizacao' => true, 'status' => 'descartado', 'payload' => null,
        ]);
        Http::assertNothingSent();
    }

    public function test_sem_revogacao_ficha_continua_protegida_e_entregue(): void
    {
        $admin = $this->dono();
        $this->app->instance(ConsultasAcademicasTelegram::class, new class extends ConsultasAcademicasTelegram
        {
            public function aluno(int $id): array
            {
                return ['text' => 'FICHA CONFIDENCIAL: NOTA 9,5'];
            }
        });
        $this->comando(502, '/aluno 5');
        $this->assertDatabaseHas('telegram_outbox', [
            'update_id' => 502, 'user_id' => $admin->id,
            'requer_autorizacao' => true, 'status' => 'entregue',
        ]);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'FICHA CONFIDENCIAL'));
    }

    public function test_resposta_generica_a_sem_vinculo_pode_ser_entregue(): void
    {
        $this->comando(503, '/status');
        $this->assertDatabaseHas('telegram_outbox', [
            'update_id' => 503, 'user_id' => null,
            'requer_autorizacao' => false, 'status' => 'entregue',
        ]);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Acesso restrito'));
    }

    public function test_link_invalido_pode_receber_apenas_mensagem_generica(): void
    {
        $this->comando(504, '/vincular '.str_repeat('a', 32));
        $this->assertDatabaseHas('telegram_outbox', [
            'update_id' => 504, 'user_id' => null,
            'requer_autorizacao' => false, 'status' => 'entregue',
        ]);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Código inválido'));
    }
}
