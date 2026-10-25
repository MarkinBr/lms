<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOutboxAutorizacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'token-ficticio');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    public function test_exclusao_do_administrador_descarta_mensagem_protegida(): void
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $admin->id, 'chat_id' => '707', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $outbox = app(TelegramOutbox::class);
        $outbox->registrar(99001, '707', 'Informação acadêmica', null, $admin->id);
        $id = DB::table('telegram_outbox')->where('update_id', 99001)->value('id');
        $this->assertDatabaseHas('telegram_outbox', ['id' => $id, 'requer_autorizacao' => true]);
        $admin->delete();

        $outbox->entregar((int) $id);
        $this->assertDatabaseHas('telegram_outbox', [
            'id' => $id, 'user_id' => null, 'requer_autorizacao' => true,
            'status' => 'descartado', 'payload' => null,
        ]);
        Http::assertNothingSent();
    }

    public function test_mensagem_generica_nao_requer_vinculo(): void
    {
        $outbox = app(TelegramOutbox::class);
        $outbox->registrar(99002, '707', 'Acesso restrito.');
        $id = DB::table('telegram_outbox')->where('update_id', 99002)->value('id');
        $this->assertDatabaseHas('telegram_outbox', ['id' => $id, 'requer_autorizacao' => false]);
        $outbox->entregar((int) $id);
        $this->assertDatabaseHas('telegram_outbox', ['id' => $id, 'status' => 'entregue']);
    }

    public function test_registro_herdado_com_usuario_nulo_e_protecao_ativa_nao_envia(): void
    {
        DB::table('telegram_outbox')->insert([
            'update_id' => 99003, 'chat_id' => '707', 'user_id' => null,
            'status' => 'pendente', 'payload' => json_encode(['text' => 'Dados acadêmicos']),
            'expira_em' => now()->addMinutes(5), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $outbox = app(TelegramOutbox::class);
        $outbox->drenarPendentes();
        $this->assertDatabaseHas('telegram_outbox', [
            'update_id' => 99003, 'status' => 'descartado', 'payload' => null,
        ]);
        Http::assertNothingSent();
    }
}
