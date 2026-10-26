<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\ChamadaFrequencia;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Frequencia;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramConteudoTest extends TestCase
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

    private function enviar(int $id, string $texto, string $tipo = 'private'): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-de-teste')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $id, 'message' => [
                    'chat' => ['id' => 707, 'type' => $tipo], 'from' => ['id' => 707], 'text' => $texto,
                ],
            ])->assertOk();
    }

    private function clicar(int $id, string $codigo): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-de-teste')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $id, 'callback_query' => [
                    'id' => 'cb-'.$id, 'from' => ['id' => 707],
                    'message' => ['message_id' => 31, 'chat' => ['id' => 707, 'type' => 'private']],
                    'data' => $codigo,
                ],
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
        $this->enviar(1, '/vincular '.app(TelegramAdministrativo::class)->criarCodigo($admin));

        return $admin;
    }

    private function disciplina(): Disciplina
    {
        $curso = Curso::create(['titulo' => 'Teologia', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'ordem' => 1, 'titulo' => 'Primeiro']);

        return Disciplina::create(['curso_id' => $curso->id, 'modulo_id' => $modulo->id,
            'codigo' => 'TG-1', 'nome' => 'Hermenêutica', 'carga_horaria' => 40, 'ordem' => 1]);
    }

    public function test_cria_aula_em_rascunho_com_video_e_pdf_somente_apos_confirmacao(): void
    {
        $this->vincular();
        $disciplina = $this->disciplina();
        $this->enviar(2, "/novaaula {$disciplina->id} | Interpretação Bíblica | https://youtu.be/S7s27o1xXps | https://exemplo.org/material.pdf");
        $codigo = $this->codigo();
        $this->assertDatabaseCount('aulas', 0);
        $this->clicar(3, $codigo);
        $this->clicar(4, $codigo);

        $this->assertDatabaseCount('aulas', 1);
        $this->assertDatabaseHas('aulas', ['disciplina_id' => $disciplina->id,
            'modulo_id' => $disciplina->modulo_id, 'titulo' => 'Interpretação Bíblica',
            'tipo_conteudo' => 'video', 'video_url' => 'https://youtu.be/S7s27o1xXps',
            'pdf_url' => 'https://exemplo.org/material.pdf', 'liberada' => false]);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
    }

    public function test_liberacao_de_aula_exige_confirmacao_e_estado_ainda_pendente(): void
    {
        $this->vincular();
        $disciplina = $this->disciplina();
        $aula = Aula::create(['disciplina_id' => $disciplina->id, 'titulo' => 'Teste', 'liberada' => false]);
        $this->enviar(2, "/liberaraula {$aula->id}");
        $codigo = $this->codigo();
        $this->assertFalse($aula->fresh()->liberada);
        $this->clicar(3, $codigo);
        $this->clicar(4, $codigo);
        $this->assertTrue($aula->fresh()->liberada);

        $this->enviar(5, "/liberaraula {$aula->id}");
        $this->assertDatabaseCount('telegram_confirmacoes', 1);
    }

    public function test_bloqueia_url_insegura_disciplina_invalida_e_chat_de_grupo(): void
    {
        $this->vincular();
        $disciplina = $this->disciplina();
        $this->enviar(2, "/novaaula {$disciplina->id} | Teste | http://example.org/video | -");
        $this->enviar(3, '/novaaula 999999 | Teste | - | https://example.org/material.pdf');
        $this->enviar(4, "/novaaula {$disciplina->id} | Teste | - | https://example.org/material.pdf", 'group');
        $this->enviar(5, "/novaaula {$disciplina->id} | Teste | - | -");
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
    }

    public function test_nao_libera_aula_alterada_depois_da_previa(): void
    {
        $this->vincular();
        $disciplina = $this->disciplina();
        $aula = Aula::create(['disciplina_id' => $disciplina->id, 'titulo' => 'Conteúdo revisado', 'liberada' => false]);
        $this->enviar(2, "/liberaraula {$aula->id}");
        $codigo = $this->codigo();
        $aula->update(['titulo' => 'Outro conteúdo', 'video_url' => 'https://example.org/novo-video']);
        $this->clicar(3, $codigo);

        $this->assertFalse($aula->fresh()->liberada);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'liberar_aula', 'status' => 'obsoleto']);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
    }

    public function test_cria_aula_somente_pdf_e_cancelamento_nao_cria_outra(): void
    {
        $this->vincular();
        $disciplina = $this->disciplina();
        $this->enviar(2, "/novaaula {$disciplina->id} | Leitura | - | https://example.org/material.pdf");
        $this->clicar(3, $this->codigo());
        $this->assertDatabaseHas('aulas', ['titulo' => 'Leitura', 'pdf_url' => 'https://example.org/material.pdf', 'video_url' => null, 'liberada' => false]);
        $this->enviar(4, "/novaaula {$disciplina->id} | Cancelada | https://example.org/video | -");
        $this->clicar(5, str_replace('confirmar:', 'cancelar:', $this->codigo()));
        $this->assertDatabaseCount('aulas', 1);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
    }

    public function test_menu_administrar_orienta_as_operacoes_sem_executar_escrita(): void
    {
        $this->vincular();
        $this->enviar(2, '/administrar');
        $resposta = Http::recorded()->last()[0];
        $this->assertStringContainsString('confirmação', $resposta['text']);
        $botoes = collect($resposta['reply_markup']['inline_keyboard'])->flatten(1)->pluck('callback_data');
        $this->assertContains('operacao:novaaula', $botoes);
        $this->assertContains('operacao:registrarpresenca', $botoes);
        $this->clicar(3, 'operacao:novaaula');
        $this->assertStringContainsString('/novaaula DISCIPLINA_ID', Http::recorded()->last()[0]['text']);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        $this->assertDatabaseCount('aulas', 0);
    }

    public function test_conclui_chamada_apenas_com_frequencia_completa_e_confirmada(): void
    {
        $admin = $this->vincular();
        $disciplina = $this->disciplina();
        $turma = Turma::create(['curso_id' => $disciplina->curso_id, 'nome' => 'Turma A', 'ano_letivo' => 2026,
            'valor_total_curso' => 1200, 'qtd_parcelas' => 12, 'status' => Turma::STATUS_ABERTA]);
        $chamada = ChamadaFrequencia::create(['turma_id' => $turma->id, 'disciplina_id' => $disciplina->id,
            'data_aula' => '2026-10-26', 'status' => ChamadaFrequencia::STATUS_RASCUNHO,
            'criada_por' => $admin->id]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $matricula = Matricula::create(['user_id' => $aluno->id, 'curso_id' => $disciplina->curso_id,
            'turma_id' => $turma->id, 'data_matricula' => now()->toDateString(),
            'status' => 'ativo', 'numero_matricula' => 'TG-001', 'dia_vencimento' => 10]);
        $this->enviar(2, "/concluirchamada {$chamada->id}");
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        Frequencia::create(['chamada_frequencia_id' => $chamada->id, 'matricula_id' => $matricula->id,
            'situacao' => 'presente', 'registrado_por' => $admin->id]);
        $this->enviar(3, "/concluirchamada {$chamada->id}");
        $codigo = $this->codigo();
        $this->assertSame(ChamadaFrequencia::STATUS_RASCUNHO, $chamada->fresh()->status);
        $this->clicar(4, $codigo);
        $this->clicar(5, $codigo);
        $this->assertSame(ChamadaFrequencia::STATUS_CONCLUIDA, $chamada->fresh()->status);
        $this->assertDatabaseHas('chamadas_frequencia', ['id' => $chamada->id]);
    }

    public function test_conclusao_recusa_chamada_ou_frequencia_alterada_apos_previa(): void
    {
        $admin = $this->vincular();
        $disciplina = $this->disciplina();
        $turma = Turma::create(['curso_id' => $disciplina->curso_id, 'nome' => 'Turma A', 'ano_letivo' => 2026,
            'valor_total_curso' => 1200, 'qtd_parcelas' => 12, 'status' => Turma::STATUS_ABERTA]);
        $chamada = ChamadaFrequencia::create(['turma_id' => $turma->id, 'disciplina_id' => $disciplina->id,
            'data_aula' => '2026-10-26', 'status' => ChamadaFrequencia::STATUS_RASCUNHO, 'criada_por' => $admin->id]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $matricula = Matricula::create(['user_id' => $aluno->id, 'curso_id' => $disciplina->curso_id,
            'turma_id' => $turma->id, 'data_matricula' => now()->toDateString(),
            'status' => 'ativo', 'numero_matricula' => 'TG-001', 'dia_vencimento' => 10]);
        $frequencia = Frequencia::create(['chamada_frequencia_id' => $chamada->id, 'matricula_id' => $matricula->id,
            'situacao' => 'presente', 'registrado_por' => $admin->id]);
        $this->enviar(2, "/concluirchamada {$chamada->id}");
        $codigo = $this->codigo();
        $chamada->update(['data_aula' => '2026-11-03']);
        $this->clicar(3, $codigo);
        $this->assertSame(ChamadaFrequencia::STATUS_RASCUNHO, $chamada->fresh()->status);

        $this->enviar(4, "/concluirchamada {$chamada->id}");
        $codigo = $this->codigo();
        $frequencia->update(['situacao' => 'falta']);
        $this->clicar(5, $codigo);
        $this->assertSame(ChamadaFrequencia::STATUS_RASCUNHO, $chamada->fresh()->status);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
    }
}
