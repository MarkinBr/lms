<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Matricula;
use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAdministrativoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'teste-ficticio');
        config()->set('services.telegram.webhook_secret', 'segredo-teste');
        config()->set('services.telegram.admin_user_id', 1);
        $this->simularTelegram(Http::response(['ok' => true]));
    }

    private function simularTelegram($resposta): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => $resposta]);
    }

    private function updateTelegram(int $id, string $texto, int $chat = 123, string $type = 'private'): array
    {
        return ['update_id' => $id, 'message' => [
            'chat' => ['id' => $chat, 'type' => $type],
            'from' => ['id' => $chat],
            'text' => $texto,
        ]];
    }

    private function enviar(array $update, ?string $secret = 'segredo-teste')
    {
        return $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => $secret ?? ''])
            ->postJson('/api/telegram/cetsadp/webhook', $update);
    }

    public function test_rejeita_webhook_sem_segredo_e_nao_envia_resposta(): void
    {
        $this->enviar($this->updateTelegram(1, '/start'), 'errado')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_impede_consultas_sem_vinculacao(): void
    {
        $this->enviar($this->updateTelegram(1, '/resumo'))->assertOk();
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Acesso restrito'));
    }

    public function test_nao_processa_grupos_nem_updates_repetidos(): void
    {
        $this->enviar($this->updateTelegram(1, '/start', 123, 'group'))->assertOk();
        $this->enviar($this->updateTelegram(1, '/start', 123, 'group'))->assertOk();
        $this->assertSame(1, DB::table('telegram_updates')->count());
        Http::assertNothingSent();
    }

    public function test_pareamento_admin_consulta_e_revogacao(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $this->actingAs($usuario)->post('/admin/telegram/gerar')->assertRedirect();
        $hash = DB::table('telegram_vinculos')->first()->codigo_hash;
        $this->assertSame(64, strlen($hash));

        // Os códigos são entregues pela sessão ao administrador, não persistidos em claro.
        $codigo = session('telegram_codigo');
        $this->assertSame(hash('sha256', $codigo), $hash);
        $this->enviar($this->updateTelegram(2, '/vincular '.$codigo))->assertOk();
        $this->assertNull(DB::table('telegram_vinculos')->first()->codigo_hash);
        $this->enviar($this->updateTelegram(3, '/resumo'))->assertOk();
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Resumo acadêmico'));
        $this->actingAs($usuario)->post('/admin/telegram/revogar')->assertRedirect();
        $this->assertSame(0, DB::table('telegram_vinculos')->count());
    }

    public function test_codigo_expirado_ou_usuario_inativo_nao_vincula(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($usuario);
        DB::table('telegram_vinculos')->where('user_id', $usuario->id)
            ->update(['codigo_expira_em' => now()->subMinute()]);
        $this->enviar($this->updateTelegram(4, '/vincular '.$codigo))->assertOk();
        $this->assertNull(DB::table('telegram_vinculos')->first()->chat_id);
    }

    public function test_nao_admin_nao_gera_codigo(): void
    {
        $usuario = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->actingAs($usuario)->post('/admin/telegram/gerar')->assertForbidden();
    }

    private function vincularDono(): User
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($usuario);
        $this->enviar($this->updateTelegram(10, '/vincular '.$codigo))->assertOk();

        return $usuario;
    }

    public function test_outro_admin_nao_pode_acessar_pagina_gerar_ou_revogar(): void
    {
        $dono = $this->vincularDono();
        $outro = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $this->actingAs($outro)->get('/admin/telegram')->assertForbidden();
        $this->actingAs($outro)->post('/admin/telegram/gerar')->assertForbidden();
        $this->actingAs($outro)->post('/admin/telegram/revogar')->assertForbidden();
        $this->assertDatabaseHas('telegram_vinculos', ['user_id' => $dono->id, 'chat_id' => '123']);
    }

    public function test_sem_dono_configurado_nega_acesso_mesmo_para_admin(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', null);
        $this->actingAs($usuario)->get('/admin/telegram')->assertForbidden();
        $this->actingAs($usuario)->post('/admin/telegram/gerar')->assertForbidden();
        $this->assertDatabaseCount('telegram_vinculos', 0);
    }

    public function test_configuracao_invalida_nao_e_convertida_em_id_autorizado(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id.'abc');
        $this->actingAs($usuario)->post('/admin/telegram/gerar')->assertForbidden();
        $this->assertDatabaseCount('telegram_vinculos', 0);
    }

    public function test_configuracao_booleana_nao_autoriza_o_primeiro_admin(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', true);
        $this->actingAs($usuario)->post('/admin/telegram/gerar')->assertForbidden();
        $this->assertDatabaseCount('telegram_vinculos', 0);
    }

    public function test_vinculo_anterior_perde_acesso_ao_trocar_dono_configurado(): void
    {
        $this->vincularDono();
        $outro = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $outro->id);
        $this->enviar($this->updateTelegram(11, '/resumo'))->assertOk();
        $ultimo = Http::recorded()->last()[0];
        $this->assertStringContainsString('Acesso restrito', $ultimo['text']);
        $this->assertStringNotContainsString('Alunos cadastrados:', $ultimo['text']);
    }

    public function test_codigo_de_admin_anterior_nao_pode_ser_consumido(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($usuario);
        config()->set('services.telegram.admin_user_id', 999);
        $this->enviar($this->updateTelegram(1, '/vincular '.$codigo))->assertOk();
        $this->assertDatabaseHas('telegram_vinculos', ['user_id' => $usuario->id, 'chat_id' => null]);
    }

    public function test_novo_dono_recupera_o_mesmo_telegram_sem_intervencao_no_banco(): void
    {
        $anterior = $this->vincularDono();
        $novo = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $novo->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($novo);
        $this->enviar($this->updateTelegram(11, '/vincular '.$codigo))->assertOk();
        $this->assertDatabaseHas('telegram_vinculos', ['user_id' => $novo->id, 'chat_id' => '123']);
        $this->assertDatabaseMissing('telegram_vinculos', ['user_id' => $anterior->id, 'chat_id' => '123']);
        $this->enviar($this->updateTelegram(12, '/resumo'))->assertOk();
        $this->assertStringContainsString('Resumo acadêmico', Http::recorded()->last()[0]['text']);
    }

    public function test_falha_ao_notificar_recuperacao_preserva_novo_vinculo_e_nao_repete_acao(): void
    {
        $anterior = $this->vincularDono();
        $novo = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $novo->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($novo);
        $this->simularTelegram(Http::response([], 503));
        $this->enviar($this->updateTelegram(11, '/vincular '.$codigo))->assertOk();
        $this->assertDatabaseMissing('telegram_vinculos', ['user_id' => $anterior->id, 'chat_id' => '123']);
        $this->assertDatabaseHas('telegram_vinculos', [
            'user_id' => $novo->id, 'chat_id' => '123', 'codigo_hash' => null,
        ]);
        $this->assertDatabaseHas('telegram_updates', ['update_id' => 11]);
        $this->assertDatabaseHas('telegram_outbox', ['update_id' => 11, 'status' => 'pendente']);
    }

    public function test_admin_inativo_ou_rebaixado_perde_acesso_imediatamente(): void
    {
        $usuario = $this->vincularDono();
        $usuario->update(['ativo' => false]);
        $this->enviar($this->updateTelegram(11, '/resumo'))->assertOk();
        $this->assertStringContainsString('Acesso restrito', Http::recorded()->last()[0]['text']);
        $usuario->update(['ativo' => true, 'nivel' => 'professor']);
        $this->enviar($this->updateTelegram(12, '/resumo'))->assertOk();
        $this->assertStringContainsString('Acesso restrito', Http::recorded()->last()[0]['text']);
    }

    public function test_resumo_conta_status_legados_e_canonicos_sem_alterar_matriculas(): void
    {
        $usuario = $this->vincularDono();
        $curso = Curso::create(['titulo' => 'Teologia', 'ativo' => true]);
        foreach (['ativa', 'ativo', 'Ativa', 'ATIVO', 'cancelada'] as $indice => $status) {
            Matricula::create([
                'user_id' => $usuario->id, 'curso_id' => $curso->id,
                'data_matricula' => '2026-10-25', 'status' => $status,
                'numero_matricula' => 'TELEGRAM-'.$indice, 'dia_vencimento' => 10,
            ]);
        }
        $antes = DB::table('matriculas')->orderBy('id')->get()->toJson();
        $this->enviar($this->updateTelegram(11, '/resumo'))->assertOk();
        $this->assertStringContainsString('Matrículas ativas: 4', Http::recorded()->last()[0]['text']);
        $this->assertSame($antes, DB::table('matriculas')->orderBy('id')->get()->toJson());
    }

    public function test_falha_externa_nao_reexecuta_update_nem_duplica_outbox(): void
    {
        $this->vincularDono();
        $this->simularTelegram(Http::sequence()
            ->push(['ok' => false, 'error_code' => 503], 503)
            ->push(['ok' => true]));
        $update = $this->updateTelegram(11, '/resumo');
        $this->enviar($update)->assertOk();
        $this->assertDatabaseHas('telegram_updates', ['update_id' => 11]);
        $this->assertDatabaseHas('telegram_outbox', ['update_id' => 11, 'status' => 'pendente']);
        $this->enviar($update)->assertOk();
        $this->assertDatabaseCount('telegram_outbox', 2);
        Http::assertSentCount(1);
    }

    public function test_falha_de_envio_nao_reverte_vinculo_e_codigo_e_consumido(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($usuario);
        $this->simularTelegram(Http::sequence()->push([], 503)->push(['ok' => true]));
        $update = $this->updateTelegram(1, '/vincular '.$codigo);
        $this->enviar($update)->assertOk();
        $this->assertDatabaseHas('telegram_vinculos', [
            'user_id' => $usuario->id, 'chat_id' => '123', 'codigo_hash' => null,
        ]);
        $this->assertDatabaseHas('telegram_outbox', ['update_id' => 1, 'status' => 'pendente']);
        $this->enviar($update)->assertOk();
        $this->assertDatabaseCount('telegram_outbox', 1);
    }

    public function test_resposta_ok_false_persiste_update_e_pende_envio(): void
    {
        $this->vincularDono();
        $this->simularTelegram(Http::response(['ok' => false, 'description' => 'Falha'], 200));
        $this->enviar($this->updateTelegram(11, '/resumo'))->assertOk();
        $this->assertDatabaseHas('telegram_updates', ['update_id' => 11]);
        $this->assertDatabaseHas('telegram_outbox', ['update_id' => 11, 'status' => 'pendente']);
    }

    public function test_falha_de_conexao_nao_expoe_token_e_mantem_outbox(): void
    {
        $this->vincularDono();
        $this->simularTelegram(Http::failedConnection('https://api.telegram.org/botteste-ficticio/sendMessage'));
        $resposta = $this->enviar($this->updateTelegram(11, '/status'));
        $resposta->assertOk();
        $this->assertStringNotContainsString('teste-ficticio', $resposta->getContent());
        $this->assertDatabaseHas('telegram_updates', ['update_id' => 11]);
        $this->assertDatabaseHas('telegram_outbox', ['update_id' => 11, 'status' => 'pendente']);
    }

    public function test_update_bem_sucedido_nao_envia_duas_respostas(): void
    {
        $this->vincularDono();
        $update = $this->updateTelegram(11, '/status');
        $this->enviar($update)->assertOk();
        $this->enviar($update)->assertOk();
        Http::assertSentCount(2); // Uma confirmação de vínculo e uma resposta ao comando.
    }

    public function test_codigo_usado_nao_vincula_outro_chat(): void
    {
        $usuario = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $usuario->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($usuario);
        $this->enviar($this->updateTelegram(1, '/vincular '.$codigo))->assertOk();
        $this->enviar($this->updateTelegram(2, '/vincular '.$codigo, 456))->assertOk();
        $this->assertDatabaseHas('telegram_vinculos', ['user_id' => $usuario->id, 'chat_id' => '123']);
        $this->assertDatabaseMissing('telegram_vinculos', ['chat_id' => '456']);
    }

    public function test_revogacao_bloqueia_novas_consultas(): void
    {
        $usuario = $this->vincularDono();
        $this->actingAs($usuario)->post('/admin/telegram/revogar')->assertRedirect();
        $this->enviar($this->updateTelegram(11, '/resumo'))->assertOk();
        $this->assertStringContainsString('Acesso restrito', Http::recorded()->last()[0]['text']);
    }

    public function test_segredo_ausente_ou_configuracao_incompleta_nao_processa_update(): void
    {
        $this->enviar($this->updateTelegram(1, '/resumo'), null)->assertForbidden();
        config()->set('services.telegram.bot_token', null);
        $this->enviar($this->updateTelegram(2, '/resumo'))->assertStatus(503);
        $this->assertDatabaseCount('telegram_updates', 0);
        Http::assertNothingSent();
    }

    public function test_remetente_diferente_do_chat_nao_recebe_resposta(): void
    {
        $update = $this->updateTelegram(1, '/resumo');
        $update['message']['from']['id'] = 456;
        $this->enviar($update)->assertOk();
        Http::assertNothingSent();
    }
}
