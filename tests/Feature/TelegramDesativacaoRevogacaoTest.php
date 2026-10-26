<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\ConfirmacoesTelegram;
use App\Services\Telegram\TelegramAdministrativo;
use App\Services\Telegram\TelegramOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelegramDesativacaoRevogacaoTest extends TestCase
{
    use RefreshDatabase;

    private function donoVinculado(): User
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $admin->id,
            'chat_id' => '707',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $admin;
    }

    public function test_inativar_cancela_propostas_audita_e_impede_reativacao_de_reusar_chat(): void
    {
        $admin = $this->donoVinculado();
        app(ConfirmacoesTelegram::class)->preparar('707', 601);
        app(ConfirmacoesTelegram::class)->preparar('707', 602);
        app(TelegramOutbox::class)->registrar(603, '707', 'Ficha protegida', null, $admin->id);

        $admin->update(['ativo' => false]);

        $this->assertDatabaseMissing('telegram_vinculos', ['user_id' => $admin->id]);
        $this->assertSame(2, DB::table('telegram_confirmacoes')->where('status', 'cancelado')->count());
        $this->assertDatabaseCount('telegram_auditoria_revogacoes', 2);
        $this->assertDatabaseHas('telegram_outbox', [
            'update_id' => 603,
            'status' => 'descartado',
            'payload' => null,
        ]);

        $admin->update(['ativo' => true]);
        $this->assertTrue(app(TelegramAdministrativo::class)->podeOperar($admin));
        $this->assertDatabaseMissing('telegram_vinculos', ['user_id' => $admin->id]);
        $this->assertDatabaseCount('telegram_auditoria_revogacoes', 2);
    }

    public function test_rebaixamento_revoga_chat_e_repromocao_nao_restaura_pareamento(): void
    {
        $admin = $this->donoVinculado();
        app(ConfirmacoesTelegram::class)->preparar('707', 611);

        $admin->update(['nivel' => 'professor']);
        $this->assertDatabaseMissing('telegram_vinculos', ['user_id' => $admin->id]);
        $this->assertDatabaseHas('telegram_confirmacoes', ['status' => 'cancelado']);
        $this->assertDatabaseCount('telegram_auditoria_revogacoes', 1);

        $admin->update(['nivel' => 'admin']);
        $this->assertTrue(app(TelegramAdministrativo::class)->podeOperar($admin));
        $this->assertDatabaseMissing('telegram_vinculos', ['user_id' => $admin->id]);
    }

    public function test_mudanca_apenas_no_nome_nao_revoga_o_administrador(): void
    {
        $admin = $this->donoVinculado();
        $admin->update(['name' => 'Nome Atualizado']);

        $this->assertDatabaseHas('telegram_vinculos', ['user_id' => $admin->id, 'chat_id' => '707']);
        $this->assertDatabaseCount('telegram_auditoria_revogacoes', 0);
    }

    public function test_conta_nao_proprietaria_nao_interfere_no_vinculo_do_dono(): void
    {
        $admin = $this->donoVinculado();
        $outro = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $outro->update(['ativo' => false]);

        $this->assertDatabaseHas('telegram_vinculos', ['user_id' => $admin->id, 'chat_id' => '707']);
        $this->assertDatabaseCount('telegram_auditoria_revogacoes', 0);
    }

    public function test_exclusao_do_administrador_sem_confirmacoes_revoga_vinculo_e_saida_pendente(): void
    {
        $admin = $this->donoVinculado();
        app(TelegramOutbox::class)->registrar(620, '707', 'Ficha protegida', null, $admin->id);

        $admin->delete();

        $this->assertDatabaseMissing('telegram_vinculos', ['user_id' => $admin->id]);
        $this->assertDatabaseHas('telegram_outbox', [
            'update_id' => 620,
            'user_id' => null,
            'requer_autorizacao' => true,
            'status' => 'descartado',
            'payload' => null,
        ]);
    }
}
