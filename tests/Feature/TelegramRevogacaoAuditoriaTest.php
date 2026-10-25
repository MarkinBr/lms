<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\ConfirmacoesTelegram;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelegramRevogacaoAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private function administradorVinculado(): User
    {
        $administrador = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $administrador->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $administrador->id, 'chat_id' => '707',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $administrador;
    }

    public function test_revogacao_audita_cada_proposta_pendente_uma_unica_vez(): void
    {
        $admin = $this->administradorVinculado();
        $servico = app(ConfirmacoesTelegram::class);
        $servico->preparar('707', 301);
        $servico->preparar('707', 302);
        $this->assertDatabaseCount('telegram_auditoria_revogacoes', 0);

        app(TelegramAdministrativo::class)->revogar($admin);

        $this->assertSame(2, DB::table('telegram_confirmacoes')->where('status', 'cancelado')->count());
        $this->assertDatabaseCount('telegram_auditoria_revogacoes', 2);
        $this->assertSame(2, DB::table('telegram_auditoria_revogacoes')
            ->where('user_id', $admin->id)->where('antes', 'pendente')->where('depois', 'cancelado')->count());
        $this->assertDatabaseCount('telegram_auditoria', 2);
    }

    public function test_revogacao_sem_propostas_nao_cria_auditoria_falsa(): void
    {
        $admin = $this->administradorVinculado();
        app(TelegramAdministrativo::class)->revogar($admin);
        $this->assertDatabaseCount('telegram_auditoria_revogacoes', 0);
    }
}
