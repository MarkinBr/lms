<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramMenuConsultaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'fake-token');
        config()->set('services.telegram.webhook_secret', 'fake-secret');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function donoVinculado(): User
    {
        $dono = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $dono->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($dono);
        $this->enviar(['update_id' => 1, 'message' => [
            'chat' => ['id' => 321, 'type' => 'private'], 'from' => ['id' => 321],
            'text' => '/vincular '.$codigo,
        ]])->assertOk();

        return $dono;
    }

    private function enviar(array $update)
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'fake-secret')
            ->postJson('/api/telegram/cetsadp/webhook', $update);
    }

    private function mensagem(int $id, string $texto): array
    {
        return ['update_id' => $id, 'message' => [
            'chat' => ['id' => 321, 'type' => 'private'],
            'from' => ['id' => 321], 'text' => $texto,
        ]];
    }

    private function callback(int $id, string $data, string $type = 'private', int $sender = 321): array
    {
        return ['update_id' => $id, 'callback_query' => [
            'id' => 'cb-'.$id, 'from' => ['id' => $sender],
            'message' => ['chat' => ['id' => 321, 'type' => $type], 'message_id' => 10],
            'data' => $data,
        ]];
    }

    public function test_menu_retorna_botoes(): void
    {
        $this->donoVinculado();
        $this->enviar($this->mensagem(2, '/menu'))->assertOk();
        Http::assertSent(fn ($r) => str_contains($r['text'], 'Menu CETSADP')
            && isset($r['reply_markup']['inline_keyboard']));
    }

    public function test_listagem_de_alunos_e_paginada_e_nao_expoe_cpf(): void
    {
        $this->donoVinculado();
        for ($i = 0; $i < 7; $i++) {
            User::factory()->create(['nivel' => 'aluno', 'ativo' => true,
                'name' => 'Estudante '.$i, 'cpf' => '11111111111']);
        }
        $this->enviar($this->callback(2, 'lista:alunos:1'))->assertOk();
        $msg = Http::recorded()->last()[0]['text'];
        $this->assertStringContainsString('Estudante 0', $msg);
        $this->assertStringNotContainsString('Estudante 6', $msg);
        $this->assertStringNotContainsString('11111111111', $msg);
        $this->enviar($this->callback(3, 'lista:alunos:2'))->assertOk();
        $this->assertStringContainsString('Estudante 6', Http::recorded()->last()[0]['text']);
    }

    public function test_revogacao_e_grupo_bloqueiam_callback(): void
    {
        $dono = $this->donoVinculado();
        $anterior = Http::recorded()->count();
        $this->enviar($this->callback(2, 'lista:alunos:1', 'group'))->assertOk();
        $this->assertCount($anterior, Http::recorded());
        app(TelegramAdministrativo::class)->revogar($dono);
        $this->enviar($this->callback(3, 'lista:alunos:1'))->assertOk();
        $this->assertStringContainsString('Acesso restrito', Http::recorded()->last()[0]['text']);
    }

    public function test_busca_limita_entrada_e_nao_aceita_categoria_arbitraria(): void
    {
        $this->donoVinculado();
        $this->enviar($this->mensagem(2, '/buscar '.str_repeat('x', 100)))->assertOk();
        $this->assertStringContainsString('até 60', Http::recorded()->last()[0]['text']);
        $this->enviar($this->callback(3, 'lista:users:1'))->assertOk();
        $this->assertStringContainsString('Opção inválida', Http::recorded()->last()[0]['text']);
    }

    public function test_repeticao_nao_reprocessa_callback(): void
    {
        $this->donoVinculado();
        $payload = $this->callback(2, 'lista:alunos:1');
        $this->enviar($payload)->assertOk();
        $n = Http::recorded()->count();
        $this->enviar($payload)->assertOk();
        $this->assertCount($n, Http::recorded());
        $this->assertDatabaseHas('telegram_updates', ['update_id' => 2]);
    }
}
