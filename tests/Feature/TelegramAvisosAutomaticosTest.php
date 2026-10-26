<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAvisosAutomaticosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.avisos_automaticos', true);
        config()->set('services.telegram.bot_token', 'token-simulado');

        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function vincularDono(bool $alertas = true): User
    {
        $dono = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $dono->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $dono->id,
            'chat_id' => '818',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('telegram_alerta_preferencias')->insert([
            'user_id' => $dono->id,
            'ativo' => $alertas,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $dono;
    }

    public function test_desativado_por_padrao_nao_acessa_telegram_nem_registra_envio(): void
    {
        $this->vincularDono();
        config()->set('services.telegram.avisos_automaticos', false);
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'diario'])->assertExitCode(0);
        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_avisos_programados', 0);
    }

    public function test_simulacao_nao_envia_nem_grava_mesmo_com_preferencia_ativa(): void
    {
        $this->vincularDono();
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'semanal', '--simular' => true])
            ->assertExitCode(0);
        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_avisos_programados', 0);
    }

    public function test_preferencia_desativada_nao_envia_alerta(): void
    {
        $this->vincularDono(false);
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'diario'])->assertExitCode(0);
        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_avisos_programados', 0);
    }

    public function test_envia_resumo_diario_agregado_uma_vez_para_chat_privado(): void
    {
        $this->vincularDono();
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'diario'])->assertExitCode(0);
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'diario'])->assertExitCode(0);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/sendMessage')
                && (string) $request['chat_id'] === '818'
                && str_contains($request['text'], 'Aviso diário')
                && str_contains($request['text'], 'Inscrições em análise')
                && ! str_contains($request['text'], 'CPF');
        });
        $this->assertDatabaseCount('telegram_avisos_programados', 1);
        $this->assertDatabaseHas('telegram_avisos_programados', ['tipo' => 'diario', 'status' => 'aceito']);
    }

    public function test_resumo_semanal_e_isolado_do_diario(): void
    {
        $this->vincularDono();
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'diario'])->assertExitCode(0);
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'semanal'])->assertExitCode(0);
        Http::assertSentCount(2);
        $this->assertDatabaseCount('telegram_avisos_programados', 2);
        Http::assertSent(fn ($request) => str_contains((string) ($request['text'] ?? ''), 'Resumo semanal'));
    }

    public function test_http_falhou_e_nao_repete_mesma_janela(): void
    {
        $this->vincularDono();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 503)]);
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'diario'])->assertExitCode(1);
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'diario'])->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('telegram_avisos_programados', ['tipo' => 'diario', 'status' => 'nao_confirmado']);
    }

    public function test_conta_sem_vinculo_ou_inativa_nao_recebe(): void
    {
        $usuario = $this->vincularDono();
        $usuario->update(['ativo' => false]);
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'diario'])->assertExitCode(0);
        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_avisos_programados', 0);
    }

    public function test_tipo_invalido_nao_envia(): void
    {
        $this->vincularDono();
        $this->artisan('telegram:avisos-automaticos', ['--tipo' => 'mensal'])->assertExitCode(1);
        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_avisos_programados', 0);
    }
}
