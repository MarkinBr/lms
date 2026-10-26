<?php

namespace Tests\Feature;

use App\Jobs\NotificarDecisaoTelegram;
use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\User;
use App\Services\NotificarDecisaoInscricaoService;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TelegramDecisaoInscricaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'ficticio');
        config()->set('services.telegram.webhook_secret', 'segredo');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Mail::fake();
        Notification::fake();
    }

    private function enviar(int $id, string $texto, bool $callback = false): void
    {
        $mensagem = ['chat' => ['id' => 770, 'type' => 'private'], 'from' => ['id' => 770], 'text' => $texto];
        $update = $callback
            ? ['callback_query' => ['id' => 'cb'.$id, 'from' => ['id' => 770], 'message' => $mensagem, 'data' => $texto]]
            : ['message' => $mensagem];
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo')
            ->postJson('/api/telegram/cetsadp/webhook', ['update_id' => $id] + $update)->assertOk();
    }

    private function ambiente(): Application
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        $this->enviar(1, '/vincular '.app(TelegramAdministrativo::class)->criarCodigo($admin));
        $curso = Curso::create(['titulo' => 'Curso Ensaio', 'ativo' => true]);
        $modalidade = CourseModality::create(['curso_id' => $curso->id, 'nome' => 'EAD', 'ativo' => true]);

        return Application::create([
            'protocol' => 'DECISAO-TESTE', 'nome_completo' => 'Candidato Ensaio', 'cpf' => '52998224725',
            'data_nascimento' => '1990-01-01', 'sexo' => 'M', 'nacionalidade' => 'Brasileiro',
            'email' => 'ensaio@example.test', 'telefone' => '11999999999', 'cep' => '01001000',
            'logradouro' => 'Rua Teste', 'numero' => '1', 'bairro' => 'Centro',
            'cidade' => 'São Paulo', 'estado' => 'SP', 'escolaridade' => 'medio_completo',
            'curso_id' => $curso->id, 'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);
    }

    private function proposta(int $update, string $texto): string
    {
        $this->enviar($update, $texto);
        $resposta = Http::recorded()->last()[0];
        $this->assertStringNotContainsString('52998224725', $resposta['text']);
        $this->assertStringNotContainsString('ensaio@example.test', $resposta['text']);
        $this->assertNotNull($resposta['reply_markup']);

        return $resposta['reply_markup']['inline_keyboard'][0][0]['callback_data'];
    }

    public function test_aprova_somente_apos_confirmacao_sem_email_na_transacao_e_sem_duplicar(): void
    {
        $inscricao = $this->ambiente();
        $botao = $this->proposta(2, '/aprovarinscricao '.$inscricao->id);
        $this->assertDatabaseCount('matriculas', 0);
        $this->enviar(3, $botao, true);
        $this->enviar(4, $botao, true);
        $this->assertDatabaseCount('matriculas', 1);
        $this->assertSame(Application::STATUS_MATRICULADO, $inscricao->fresh()->status);
        $this->assertDatabaseCount('jobs', 2);
        Mail::assertNothingSent();
        Notification::assertNothingSent();
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'aprovar_inscricao', 'status' => 'confirmado']);
    }

    public function test_rejeita_com_motivo_e_invalida_proposta_cujos_dados_mudaram(): void
    {
        $inscricao = $this->ambiente();
        $botao = $this->proposta(2, '/rejeitarinscricao '.$inscricao->id.' | Documentação incompleta');
        $inscricao->update(['email' => 'outro@example.test']);
        $this->enviar(3, $botao, true);
        $this->assertSame(Application::STATUS_EM_ANALISE, $inscricao->fresh()->status);
        $this->assertDatabaseHas('telegram_confirmacoes', ['status' => 'obsoleto']);
        $botao = $this->proposta(4, '/rejeitarinscricao '.$inscricao->id.' | Documentação incompleta');
        $this->enviar(5, $botao, true);
        $this->assertSame(Application::STATUS_REJEITADO, $inscricao->fresh()->status);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'rejeitar_inscricao',
            'recurso_tipo' => 'applications', 'recurso_id' => $inscricao->id]);
        $this->assertDatabaseCount('matriculas', 0);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_cancelamento_e_revogacao_impedem_decisao(): void
    {
        $inscricao = $this->ambiente();
        $botao = $this->proposta(2, '/aprovarinscricao '.$inscricao->id);
        $this->enviar(3, str_replace('confirmar:', 'cancelar:', $botao), true);
        $botao = $this->proposta(4, '/aprovarinscricao '.$inscricao->id);
        app(TelegramAdministrativo::class)->revogar(User::find(config('services.telegram.admin_user_id')));
        $this->enviar(5, $botao, true);
        $this->assertDatabaseCount('matriculas', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_nao_matricula_conta_administrativa_ou_aluno_inativo(): void
    {
        $inscricao = $this->ambiente();
        User::factory()->create(['email' => $inscricao->email, 'cpf' => $inscricao->cpf,
            'nivel' => 'admin', 'ativo' => true]);
        $botao = $this->proposta(2, '/aprovarinscricao '.$inscricao->id);
        $this->enviar(3, $botao, true);
        $this->assertSame(Application::STATUS_EM_ANALISE, $inscricao->fresh()->status);
        $this->assertDatabaseCount('matriculas', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_aluno_existente_pendente_recebe_ativacao_separada_da_decisao(): void
    {
        $inscricao = $this->ambiente();
        User::factory()->create(['email' => $inscricao->email, 'cpf' => $inscricao->cpf,
            'nivel' => 'aluno', 'ativo' => true, 'primeiro_acesso_pendente' => true, 'email_verified_at' => null]);
        $botao = $this->proposta(2, '/aprovarinscricao '.$inscricao->id);
        $this->enviar(3, $botao, true);
        $this->assertDatabaseCount('jobs', 2);
        $this->assertDatabaseCount('matriculas', 1);

        $notificador = $this->mock(NotificarDecisaoInscricaoService::class);
        $notificador->shouldReceive('notificarMatricula')->twice()->andReturn(false);
        $job = new NotificarDecisaoTelegram($inscricao->id, Application::STATUS_MATRICULADO,
            hash('sha256', $inscricao->email));
        for ($i = 0; $i < 2; $i++) {
            try {
                $job->handle($notificador);
                $this->fail('Falha de transporte deveria manter a notificação disponível para retry.');
            } catch (\RuntimeException $erro) {
                $this->assertStringContainsString('tentada novamente', $erro->getMessage());
            }
        }
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_notificacao_nao_usa_failover_com_log_que_poderia_expor_link(): void
    {
        $inscricao = $this->ambiente();
        $inscricao->update(['status' => Application::STATUS_REJEITADO,
            'observacoes_secretaria' => 'Documentação incompleta']);
        app()->instance('env', 'production');
        config()->set('mail.default', 'failover');
        config()->set('mail.mailers.failover', ['transport' => 'failover', 'mailers' => ['smtp', 'log']]);
        $notificador = $this->mock(NotificarDecisaoInscricaoService::class);
        $notificador->shouldNotReceive('notificarRejeicao');
        $job = new NotificarDecisaoTelegram($inscricao->id, Application::STATUS_REJEITADO,
            hash('sha256', $inscricao->email));
        $this->expectException(\RuntimeException::class);
        $job->handle($notificador);
    }
}
