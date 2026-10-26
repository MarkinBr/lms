<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramCentralGestaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'ficticio');
        config()->set('services.telegram.webhook_secret', 'segredo-isolado');

        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $usuario->id, 'chat_id' => '808',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function enviar(int $id, string $texto, int $chat = 808): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-isolado')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $id,
                'message' => [
                    'chat' => ['id' => $chat, 'type' => 'private'],
                    'from' => ['id' => $chat],
                    'text' => $texto,
                ],
            ])->assertOk();
    }

    private function ultimoTexto(): string
    {
        return (string) Http::recorded()->last()[0]['text'];
    }

    public function test_central_e_prioridades_exibem_indicadores_anonimos(): void
    {
        $this->enviar(1001, '/central');
        $this->assertStringContainsString('Central de gestão', $this->ultimoTexto());
        $this->assertStringContainsString('Inscrições', $this->ultimoTexto());

        $this->enviar(1002, '/prioridades');
        $this->assertStringContainsString('Prioridades', $this->ultimoTexto());
        $this->assertStringContainsString('Pendências', $this->ultimoTexto());
        $this->assertStringNotContainsString('CPF', $this->ultimoTexto());
    }

    public function test_pesquisa_guiada_aceita_nome_e_orienta_quando_faltar(): void
    {
        User::factory()->create(['nivel' => 'aluno', 'name' => 'Aluno de Pesquisa']);
        $this->enviar(1003, '/pesquisar Pesquisa');
        $this->assertStringContainsString('Aluno de Pesquisa', $this->ultimoTexto());
        $this->enviar(1004, '/pesquisar');
        $this->assertStringContainsString('/pesquisar NOME', $this->ultimoTexto());
    }

    public function test_desempenho_sem_turma_orienta_e_nao_faz_ranking(): void
    {
        $this->enviar(1005, '/desempenho');
        $this->assertStringContainsString('Desempenho', $this->ultimoTexto());
        $this->enviar(1006, '/desempenho 999999');
        $this->assertStringContainsString('Turma não encontrada', $this->ultimoTexto());
    }

    public function test_agenda_e_relatorio_semanal_explicam_janela(): void
    {
        $this->enviar(1007, '/agendainteligente');
        $this->assertStringContainsString('Agenda inteligente', $this->ultimoTexto());
        $this->assertStringContainsString('7 dias', $this->ultimoTexto());

        $this->enviar(1008, '/relatoriosemanal');
        $this->assertStringContainsString('Relatório semanal', $this->ultimoTexto());
        $this->assertStringContainsString('últimos 7 dias', $this->ultimoTexto());
    }

    public function test_notificacoes_guardam_preferencia_sem_enviar_alertas(): void
    {
        $this->enviar(1009, '/notificacoes');
        $this->assertStringContainsString('automático', $this->ultimoTexto());
        $this->enviar(1010, '/notificacoes sim');
        $this->assertStringContainsString('solicitados', $this->ultimoTexto());
        $this->assertDatabaseHas('telegram_alerta_preferencias', ['ativo' => true]);
        $this->enviar(1011, '/notificacoes nao');
        $this->assertDatabaseHas('telegram_alerta_preferencias', ['ativo' => false]);
    }

    public function test_nao_vinculado_nao_pode_consultar_central(): void
    {
        $this->enviar(1012, '/central', 909);
        $this->assertStringContainsString('Acesso restrito', $this->ultimoTexto());
        $this->assertStringNotContainsString('Central de gestão', $this->ultimoTexto());
    }

    public function test_menu_oferece_acesso_a_central_e_prioridades(): void
    {
        $this->enviar(1013, '/menu');
        $dados = Http::recorded()->last()[0]['reply_markup'];
        $this->assertStringContainsString('central', json_encode($dados));
        $this->assertStringContainsString('prioridades', json_encode($dados));
    }

    public function test_callback_central_e_controlado_pelo_mesmo_vinculo(): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-isolado')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => 1014,
                'callback_query' => [
                    'id' => 'callback-1', 'from' => ['id' => 808],
                    'message' => ['message_id' => 22, 'chat' => ['id' => 808, 'type' => 'private']],
                    'data' => 'central',
                ],
            ])->assertOk();
        $this->assertTrue(Http::recorded()->contains(
            fn ($par) => str_contains((string) ($par[0]['text'] ?? ''), 'Central de gestão')
        ));
    }
}
