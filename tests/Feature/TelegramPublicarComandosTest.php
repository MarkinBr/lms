<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\CatalogoComandosTelegram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramPublicarComandosTest extends TestCase
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

    private function vincularAdministrador(): User
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $usuario->id,
            'chat_id' => '707',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $usuario;
    }

    public function test_catalogo_nao_tem_comandos_repetidos_ou_invalidos(): void
    {
        $comandos = CatalogoComandosTelegram::listar();
        $nomes = array_column($comandos, 'command');
        $this->assertCount(count(array_unique($nomes)), $nomes);
        $this->assertLessThanOrEqual(100, count($comandos));
        foreach ($comandos as $comando) {
            $this->assertMatchesRegularExpression('/^[a-z0-9_]{1,32}$/D', $comando['command']);
            $this->assertGreaterThan(0, strlen($comando['description']));
            $this->assertLessThanOrEqual(256, strlen($comando['description']));
        }
        foreach (['menu', 'buscar', 'pendencias', 'revisarinscricao', 'filtros', 'inscricao'] as $nome) {
            $this->assertContains($nome, $nomes);
        }
    }

    public function test_publica_apenas_para_chat_privado_vinculado_ao_dono(): void
    {
        $this->vincularAdministrador();
        $this->artisan('telegram:comandos-publicar')->assertExitCode(0);
        Http::assertSentCount(1);
        Http::assertSent(function ($requisicao) {
            return str_ends_with($requisicao->url(), '/setMyCommands')
                && $requisicao['scope']['type'] === 'chat'
                && $requisicao['scope']['chat_id'] === 707
                && $requisicao['commands'] === CatalogoComandosTelegram::listar();
        });
        $this->assertStringNotContainsString('token-ficticio', Artisan::output());
    }

    public function test_simulacao_nao_chama_api(): void
    {
        $this->vincularAdministrador();
        $this->artisan('telegram:comandos-publicar', ['--simular' => true])->assertExitCode(0);
        Http::assertNothingSent();
    }

    public function test_sem_vinculo_ou_dono_ativo_nao_publica(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $this->artisan('telegram:comandos-publicar')->assertExitCode(1);

        config()->set('services.telegram.admin_user_id', null);
        $this->artisan('telegram:comandos-publicar')->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_resposta_de_erro_da_api_nao_exibe_token(): void
    {
        $this->vincularAdministrador();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 503)]);
        $this->artisan('telegram:comandos-publicar')->assertExitCode(1);
        $this->assertStringNotContainsString('token-ficticio', Artisan::output());
    }

    public function test_comandos_sem_parametros_orientam_sem_consultar_alunos(): void
    {
        $this->vincularAdministrador();

        foreach ([
            'buscar' => 'Uso: /buscar NOME',
            'aluno' => 'Uso: /aluno ID',
            'notas' => 'Uso: /notas ID',
            'frequencia' => 'Uso: /frequencia ID',
            'turma' => 'Uso: /turma ID',
            'matricula' => 'Uso: /matricula ID',
            'inscricao' => 'Uso: /inscricao UUID',
            'revisarinscricao' => 'Uso: /revisarinscricao UUID',
            'filtro' => 'Uso: /filtro status ativo',
        ] as $comando => $textoEsperado) {
            static $id = 800;
            $id++;
            $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-ficticio')
                ->postJson('/api/telegram/cetsadp/webhook', [
                    'update_id' => $id,
                    'message' => [
                        'chat' => ['id' => 707, 'type' => 'private'],
                        'from' => ['id' => 707],
                        'text' => '/'.$comando,
                    ],
                ])->assertOk();
            $this->assertStringContainsString($textoEsperado, Http::recorded()->last()[0]['text']);
        }
    }
}
