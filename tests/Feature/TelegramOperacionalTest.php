<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOperacionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'token-falso');
        config()->set('services.telegram.webhook_secret', 'segredo-falso');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function iniciar(): User
    {
        $user = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $user->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($user);
        $this->enviar(1, '/vincular '.$codigo)->assertOk();

        return $user;
    }

    private function enviar(int $id, string $texto)
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-falso')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $id,
                'message' => [
                    'chat' => ['id' => 733, 'type' => 'private'],
                    'from' => ['id' => 733], 'text' => $texto,
                ],
            ]);
    }

    public function test_pendencias_mostra_resumo_sem_dados_pessoais(): void
    {
        $this->iniciar();
        $this->enviar(2, '/pendencias')->assertOk();
        $texto = Http::recorded()->last()[0]['text'];
        $this->assertStringContainsString('Pendências', $texto);
        $this->assertStringContainsString('Inscrições em análise', $texto);
        $this->assertStringNotContainsString('cpf', mb_strtolower($texto));
    }

    public function test_agenda_e_cursos_respondem_sem_dados_pessoais(): void
    {
        $this->iniciar();
        $this->enviar(2, '/agenda')->assertOk();
        $this->assertStringContainsString('Próximos encontros', Http::recorded()->last()[0]['text']);
        $this->enviar(3, '/cursos')->assertOk();
        $this->assertStringContainsString('Cursos ativos', Http::recorded()->last()[0]['text']);
    }

    public function test_fichas_exigem_identificadores_e_nao_inventam_registros(): void
    {
        $this->iniciar();
        foreach (['/turma 9999', '/matricula 9999', '/inscricao 00000000-0000-0000-0000-000000000000'] as $i => $command) {
            $this->enviar($i + 2, $command)->assertOk();
            $this->assertStringContainsString('não encontrad', mb_strtolower(Http::recorded()->last()[0]['text']));
        }
    }

    public function test_menu_inclui_botoes_operacionais(): void
    {
        $this->iniciar();
        $this->enviar(2, '/menu')->assertOk();
        $request = Http::recorded()->last()[0];
        $teclado = json_encode($request['reply_markup']['inline_keyboard']);
        $this->assertStringContainsString('pendencias', $teclado);
        $this->assertStringContainsString('agenda', $teclado);
        $this->assertStringContainsString('cursos', $teclado);
    }
}
