<?php

namespace Tests\Feature;

use App\Models\ChamadaFrequencia;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOperacoesAcademicasTest extends TestCase
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

    private function enviar(int $updateId, string $texto): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-de-teste')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $updateId,
                'message' => ['chat' => ['id' => 707, 'type' => 'private'], 'from' => ['id' => 707], 'text' => $texto],
            ])->assertOk();
    }

    private function clicar(int $updateId, string $codigo): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-de-teste')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $updateId,
                'callback_query' => ['id' => 'cb-'.$updateId, 'from' => ['id' => 707],
                    'message' => ['message_id' => 31, 'chat' => ['id' => 707, 'type' => 'private']],
                    'data' => $codigo],
            ])->assertOk();
    }

    private function codigo(): string
    {
        return Http::recorded()->last()[0]['reply_markup']['inline_keyboard'][0][0]['callback_data'];
    }

    private function vincular(): User
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($admin);
        $this->enviar(1, '/vincular '.$codigo);

        return $admin;
    }

    private function ambienteAcademico(): array
    {
        $curso = Curso::create(['titulo' => 'Teologia', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'ordem' => 1, 'titulo' => 'Primeiro']);
        $disciplina = Disciplina::create(['curso_id' => $curso->id, 'modulo_id' => $modulo->id,
            'codigo' => 'TG-1', 'nome' => 'Hermenêutica', 'carga_horaria' => 40, 'ordem' => 1]);
        $turma = Turma::create(['curso_id' => $curso->id, 'nome' => 'Turma A', 'ano_letivo' => 2026,
            'valor_total_curso' => 1200, 'qtd_parcelas' => 12, 'status' => Turma::STATUS_ABERTA]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $matricula = Matricula::create(['user_id' => $aluno->id, 'curso_id' => $curso->id,
            'turma_id' => $turma->id, 'data_matricula' => now()->toDateString(),
            'status' => 'ativo', 'numero_matricula' => 'TG-001', 'dia_vencimento' => 10]);

        return [$turma, $disciplina, $matricula];
    }

    public function test_evento_so_e_publicado_apos_confirmacao_sem_duplicidade(): void
    {
        $this->vincular();
        $this->enviar(2, '/novoevento Culto de abertura | 2026-11-20 19:00 | Auditório central');
        $codigo = $this->codigo();
        $this->assertDatabaseCount('eventos_calendario', 0);

        $this->clicar(3, $codigo);
        $this->clicar(4, $codigo);
        $this->assertDatabaseCount('eventos_calendario', 1);
        $this->assertDatabaseHas('eventos_calendario', [
            'titulo' => 'Culto de abertura', 'descricao' => 'Auditório central', 'tipo' => 'academico', 'ativo' => 1,
        ]);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'criar_evento', 'status' => 'confirmado']);
    }

    public function test_chamada_e_presenca_exigem_confirmacao_e_respeitam_vinculo(): void
    {
        $this->vincular();
        [$turma, $disciplina, $matricula] = $this->ambienteAcademico();
        $this->enviar(2, "/novachamada {$turma->id} {$disciplina->id} 2026-10-26");
        $codigo = $this->codigo();
        $this->assertDatabaseCount('chamadas_frequencia', 0);
        $this->clicar(3, $codigo);
        $chamada = ChamadaFrequencia::firstOrFail();
        $this->assertSame(ChamadaFrequencia::STATUS_RASCUNHO, $chamada->status);

        $this->enviar(4, "/registrarpresenca {$chamada->id} {$matricula->id} presente");
        $codigo = $this->codigo();
        $this->assertDatabaseCount('frequencias', 0);
        $this->clicar(5, $codigo);
        $this->clicar(6, $codigo);
        $this->assertDatabaseHas('frequencias', [
            'chamada_frequencia_id' => $chamada->id, 'matricula_id' => $matricula->id,
            'situacao' => 'presente',
        ]);
        $this->assertDatabaseCount('frequencias', 1);

        $outroAluno = User::factory()->create(['nivel' => 'aluno']);
        $curso = Curso::create(['titulo' => 'Outro curso']);
        $outra = Matricula::create(['user_id' => $outroAluno->id, 'curso_id' => $curso->id,
            'data_matricula' => now()->toDateString(), 'status' => 'ativo',
            'numero_matricula' => 'TG-002', 'dia_vencimento' => 10]);
        $this->enviar(7, "/registrarpresenca {$chamada->id} {$outra->id} presente");
        $this->assertDatabaseCount('telegram_confirmacoes', 2);
    }

    public function test_entrada_invalida_ou_cancelada_nao_altera_o_calendario(): void
    {
        $this->vincular();
        $this->enviar(2, '/novoevento Teste | data inválida | descrição');
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        $this->enviar(3, '/novoevento Evento | 2026-11-25 19:00 | Teste');
        $codigo = $this->codigo();
        $this->clicar(4, str_replace('confirmar:', 'cancelar:', $codigo));
        $this->assertDatabaseCount('eventos_calendario', 0);
    }
}
