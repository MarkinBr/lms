<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\ConfirmacoesTelegram;
use App\Services\Telegram\TelegramOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramSegurancaRegressaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'token-ficticio');
        Http::swap(new Factory);
        Http::preventStrayRequests();
    }

    private function registrarSaida(): int
    {
        app(TelegramOutbox::class)->registrar(9001, '707', 'Resposta técnica', null, null, [
            'id' => 'callback-ficticio', 'message' => ['message_id' => 31],
        ], false);

        return (int) DB::table('telegram_outbox')->where('update_id', 9001)->value('id');
    }

    public function test_falha_de_conexao_no_callback_nao_reativa_saida_entregue(): void
    {
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/answerCallbackQuery')) {
                throw new ConnectionException('Falha simulada no callback');
            }

            return Http::response(['ok' => true]);
        });
        $id = $this->registrarSaida();
        app(TelegramOutbox::class)->entregar($id);
        $this->assertDatabaseHas('telegram_outbox', [
            'id' => $id, 'status' => 'entregue', 'payload' => null,
            'lease_ate' => null, 'proxima_tentativa_em' => null, 'tentativas' => 1,
        ]);
        $this->travel(1)->minutes();
        app(TelegramOutbox::class)->drenarPendentes();
        $this->assertDatabaseHas('telegram_outbox', ['id' => $id, 'status' => 'entregue', 'tentativas' => 1]);
        Http::assertSentCount(1);
    }

    public function test_falha_http_no_callback_nao_reativa_saida_entregue(): void
    {
        Http::fake(function ($request) {
            return str_ends_with($request->url(), '/answerCallbackQuery')
                ? Http::response(['ok' => false], 503)
                : Http::response(['ok' => true]);
        });
        $id = $this->registrarSaida();
        app(TelegramOutbox::class)->entregar($id);
        $this->assertDatabaseHas('telegram_outbox', [
            'id' => $id, 'status' => 'entregue', 'payload' => null, 'proxima_tentativa_em' => null,
        ]);
        Http::assertSentCount(2);
    }

    public function test_falha_de_conexao_no_envio_principal_preserva_payload(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Falha simulada no envio principal');
        });
        $id = $this->registrarSaida();
        app(TelegramOutbox::class)->entregar($id);
        $saida = DB::table('telegram_outbox')->where('id', $id)->first();
        $this->assertSame('pendente', $saida->status);
        $this->assertNotNull($saida->payload);
        $this->assertNotNull($saida->proxima_tentativa_em);
        $this->assertSame(1, (int) $saida->tentativas);
        $this->assertNull($saida->lease_ate);
    }

    public function test_uuid_malformado_e_rejeitado_antes_da_consulta_de_inscricao(): void
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $admin->id, 'chat_id' => '707', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $consultas = [];
        DB::listen(function ($query) use (&$consultas) {
            if (str_contains(strtolower($query->sql), 'applications')) {
                $consultas[] = $query->sql;
            }
        });
        $resposta = app(ConfirmacoesTelegram::class)->prepararRevisao('707', str_repeat('-', 36), 9002);
        $this->assertSame('Identificador de inscrição inválido.', $resposta['text']);
        $this->assertSame([], $consultas);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
    }

    public function test_callback_bem_sucedido_mantem_saida_entregue(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $id = $this->registrarSaida();
        app(TelegramOutbox::class)->entregar($id);
        $this->assertDatabaseHas('telegram_outbox', ['id' => $id, 'status' => 'entregue', 'payload' => null]);
        Http::assertSentCount(2);
    }
}
