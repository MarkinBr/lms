<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramPublicacoesTest extends TestCase
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

    private function mensagem(int $id, string $texto, string $tipo = 'private'): array
    {
        return ['update_id' => $id, 'message' => [
            'chat' => ['id' => 707, 'type' => $tipo],
            'from' => ['id' => 707], 'text' => $texto,
        ]];
    }

    private function telegramCallback(int $id, string $codigo): array
    {
        return ['update_id' => $id, 'callback_query' => [
            'id' => 'callback-'.$id, 'from' => ['id' => 707],
            'message' => ['message_id' => 31, 'chat' => ['id' => 707, 'type' => 'private']],
            'data' => $codigo,
        ]];
    }

    private function enviar(array $update): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-de-teste')
            ->postJson('/api/telegram/cetsadp/webhook', $update)->assertOk();
    }

    private function vincular(): User
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($admin);
        $this->enviar($this->mensagem(1, '/vincular '.$codigo));

        return $admin;
    }

    private function preparar(): string
    {
        $this->enviar($this->mensagem(2, '/novoaviso Matrículas abertas | Inscrições até sexta-feira.'));
        $requisicao = Http::recorded()->last()[0];
        $this->assertStringContainsString('Matrículas abertas', $requisicao['text']);
        $this->assertDatabaseCount('avisos', 0);

        return $requisicao['reply_markup']['inline_keyboard'][0][0]['callback_data'];
    }

    public function test_publica_somente_apos_confirmacao_e_uma_unica_vez(): void
    {
        $this->vincular();
        $codigo = $this->preparar();
        $this->enviar($this->telegramCallback(3, $codigo));
        $this->enviar($this->telegramCallback(4, $codigo));

        $this->assertDatabaseCount('avisos', 1);
        $this->assertDatabaseHas('avisos', [
            'titulo' => 'Matrículas abertas', 'mensagem' => 'Inscrições até sexta-feira.',
            'ativo' => true, 'tipo' => 'info',
        ]);
        $this->assertNotNull(Aviso::first()->data_publicacao);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'publicar_aviso', 'status' => 'confirmado']);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'publicar_aviso',
            'recurso_tipo' => 'avisos', 'recurso_id' => Aviso::firstOrFail()->id]);
        $this->assertDatabaseCount('telegram_auditoria', 2);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
    }

    public function test_cancelamento_e_expiracao_nao_publicam(): void
    {
        $this->vincular();
        $codigo = $this->preparar();
        $this->enviar($this->telegramCallback(3, str_replace('confirmar:', 'cancelar:', $codigo)));
        $this->enviar($this->telegramCallback(4, $codigo));
        $this->assertDatabaseCount('avisos', 0);

        $this->enviar($this->mensagem(5, '/novoaviso Comunicado | Conteúdo futuro'));
        $novoCodigo = Http::recorded()->last()[0]['reply_markup']['inline_keyboard'][0][0]['callback_data'];
        DB::table('telegram_confirmacoes')->where('status', 'pendente')->update(['expira_em' => now()->subMinute()]);
        $this->enviar($this->telegramCallback(6, $novoCodigo));
        $this->assertDatabaseCount('avisos', 0);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
    }

    public function test_aviso_confirmado_exibe_mensagem_no_mural_e_detalhe_sem_executar_html(): void
    {
        $this->vincular();
        $codigo = $this->preparar();
        $this->enviar($this->telegramCallback(3, $codigo));
        $aviso = Aviso::firstOrFail();
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);

        $this->actingAs($aluno)->get(route('aluno.comunicacao'))
            ->assertOk()->assertSee('Inscrições até sexta-feira.');
        $this->actingAs($aluno)->get(route('aluno.comunicacao.show', $aviso->id))
            ->assertOk()->assertSee('Inscrições até sexta-feira.');

        $aviso->update(['mensagem' => '<script>alert("teste")</script> Conteúdo em texto']);
        $this->get(route('aluno.comunicacao.show', $aviso->id))
            ->assertOk()->assertSee('Conteúdo em texto')->assertDontSee('<script>alert("teste")</script>', false);
    }

    public function test_revogacao_ou_perda_de_permissao_bloqueia_publicacao(): void
    {
        $admin = $this->vincular();
        $codigo = $this->preparar();
        app(TelegramAdministrativo::class)->revogar($admin);
        $this->enviar($this->telegramCallback(3, $codigo));
        $this->assertDatabaseCount('avisos', 0);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
    }

    public function test_grupo_e_entrada_invalida_nao_criam_proposta(): void
    {
        $this->vincular();
        $this->enviar($this->mensagem(2, '/novoaviso Título | Mensagem', 'group'));
        $this->enviar($this->mensagem(3, '/novoaviso sem separador'));
        $this->enviar($this->mensagem(4, '/novoaviso  | Mensagem'));
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        $this->assertDatabaseCount('avisos', 0);
    }
}
