<?php

namespace Tests\Feature;

use App\Jobs\AtivarAlunoTelegram;
use App\Models\User;
use App\Notifications\AtivarAcessoAluno;
use App\Services\Auth\IdentificadorAluno;
use App\Services\Telegram\AlunosAdministrativosTelegram;
use App\Services\Telegram\ConfirmacoesTelegram;
use App\Services\Telegram\TelegramAdministrativo;
use App\Services\Telegram\TransporteEmailTelegram;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TelegramAlunosEscritaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake();
        Notification::fake();
        $this->admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $this->admin->id);
        config()->set('services.telegram.bot_token', 'token-ficticio');
        config()->set('services.telegram.webhook_secret', 'segredo-ficticio');
        $codigo = app(TelegramAdministrativo::class)->criarCodigo($this->admin);
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-ficticio')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => 1,
                'message' => ['chat' => ['id' => 707, 'type' => 'private'], 'from' => ['id' => 707], 'text' => '/vincular '.$codigo],
            ])->assertOk();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake();
    }

    private function preparar(string $comando, string $conteudo, int $updateId = 10): array
    {
        return app(AlunosAdministrativosTelegram::class)->preparar('707', $comando, $conteudo, $updateId);
    }

    private function dados(): array
    {
        return json_decode(Crypt::decryptString(DB::table('telegram_publicacoes')->latest('id')->value('dados_criptografados')), true, 512, JSON_THROW_ON_ERROR);
    }

    private function executar(string $acao, array $dados, ?User $usuario = null): ?Model
    {
        return DB::transaction(fn () => app(AlunosAdministrativosTelegram::class)->executar($acao, $dados, $usuario ?? $this->admin));
    }

    private function confirmar(array $previa, int $updateId = 11): array
    {
        $codigo = substr($previa['reply_markup']['inline_keyboard'][0][0]['callback_data'], strlen('confirmar:'));

        return app(ConfirmacoesTelegram::class)->decidir('707', 'confirmar', $codigo, $updateId);
    }

    public function test_cadastro_so_grava_apos_confirmacao_sem_duplicar_ou_expor_senha(): void
    {
        $previa = $this->preparar('/novoaluno', 'Maria Silva | MARIA@example.test | 529.982.247-25');
        $dados = $this->dados();
        $this->assertDatabaseCount('users', 1);
        $this->assertArrayNotHasKey('password', $dados);
        $this->assertStringNotContainsString('52998224725', json_encode($previa));
        $resposta = $this->confirmar($previa);
        $this->confirmar($previa, 12);

        $aluno = User::where('nivel', 'aluno')->sole();
        $this->assertSame('maria@example.test', $aluno->email);
        $this->assertSame('52998224725', $aluno->cpf);
        $this->assertTrue($aluno->ativo);
        $this->assertNull($aluno->email_verified_at);
        $this->assertTrue($aluno->primeiro_acesso_pendente);
        $this->assertNotEmpty($aluno->matricula_login);
        $this->assertStringContainsString((string) $aluno->id, $resposta['text']);
        $this->assertStringContainsString($aluno->matricula_login, $resposta['text']);
        $this->assertStringNotContainsString($aluno->password, $resposta['text']);
        $this->assertSame($aluno->id, app(IdentificadorAluno::class)->resolver($aluno->matricula_login)?->id);
        $this->assertFalse(Hash::check('52998224725', $aluno->password));
        $this->assertFalse(Hash::check($aluno->matricula_login, $aluno->password));
        $this->assertNotSame('unknown', password_get_info($aluno->password)['algoName']);
        $this->assertStringNotContainsString($aluno->password, json_encode($previa));
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('jobs', 1);
        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_cadastro_minimo_sem_email_ou_cpf_preserva_login_e_nao_aceita_reexecucao(): void
    {
        $previa = $this->preparar('/novoaluno', 'Aluno sem contato | - | -');
        $this->assertStringContainsString('pendente', $previa['text']);
        $this->assertStringContainsString('secretaria', $previa['text']);
        $dados = $this->dados();
        $aluno = $this->executar('criar_aluno', $dados);
        $this->assertInstanceOf(User::class, $aluno);
        $this->assertNull($aluno->email);
        $this->assertNull($aluno->cpf);
        $this->assertNotNull(app(IdentificadorAluno::class)->resolver($aluno->matricula_login));
        $this->assertNull($this->executar('criar_aluno', $dados));
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_validacao_de_limites_cpf_email_e_comandos_nao_cria_propostas(): void
    {
        foreach ([
            ['/novoaluno', 'A | - | -'],
            ['/novoaluno', str_repeat('a', 256).' | - | -'],
            ['/novoaluno', "Nome\nInjetado | - | -"],
            ['/novoaluno', 'Aluno | email-invalido | -'],
            ['/novoaluno', 'Aluno | | -'],
            ['/novoaluno', 'Aluno | - | '],
            ['/novoaluno', 'Aluno | '.str_repeat('a', 250).'@x.test | -'],
            ['/novoaluno', 'Aluno | - | 11111111111'],
            ['/novoaluno', 'Aluno | - | 52998224724'],
            ['/novoaluno', 'Aluno | - | abc52998224725'],
            ['/novoaluno', 'Aluno | - | - | admin'],
            ['/editaraluno', '0 | Aluno | -'],
            ['/editaraluno', '999999999999999999999 | Aluno | -'],
            ['/editaraluno', '1 | Aluno | javascript:alert(1)'],
            ['/recuperaracesso', '1 extra'],
            ['/inexistente', 'qualquer'],
        ] as [$comando, $conteudo]) {
            $this->assertArrayNotHasKey('reply_markup', $this->preparar($comando, $conteudo));
        }
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
    }

    public function test_duplicatas_normalizadas_sao_bloqueadas_antes_e_depois_da_previa(): void
    {
        User::factory()->create(['email' => ' EXISTENTE@EXAMPLE.TEST ', 'cpf' => '529.982.247-25']);
        foreach (['Aluno | existente@example.test | -', 'Aluno | - | 52998224725'] as $conteudo) {
            $this->assertArrayNotHasKey('reply_markup', $this->preparar('/novoaluno', $conteudo));
        }
        $previa = $this->preparar('/novoaluno', 'Outro aluno | novo@example.test | 11144477735');
        User::factory()->create(['email' => 'NOVO@example.test']);
        $this->confirmar($previa);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'criar_aluno', 'status' => 'obsoleto']);
        $this->assertDatabaseMissing('users', ['name' => 'Outro aluno']);
    }

    public function test_edicao_altera_apenas_nome_telefone_e_rejeita_snapshot_antigo(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true, 'cpf' => '52998224725', 'matricula_login' => 'legado-001']);
        $antes = $aluno->fresh()->getAttributes();
        $previa = $this->preparar('/editaraluno', "{$aluno->id} | Novo Nome | (11) 99999-8888");
        $this->confirmar($previa);
        $aluno->refresh();
        $this->assertSame('Novo Nome', $aluno->name);
        $this->assertSame('11999998888', $aluno->telefone);
        foreach (['email', 'cpf', 'password', 'nivel', 'ativo', 'matricula_login'] as $campo) {
            $this->assertSame($antes[$campo], $aluno->getAttributes()[$campo]);
        }
        $this->preparar('/editaraluno', "{$aluno->id} | Não aplicar | -", 20);
        $dados = $this->dados();
        $aluno->update(['telefone' => '21999999999']);
        $this->assertNull($this->executar('editar_aluno', $dados));
        $this->assertSame('Novo Nome', $aluno->fresh()->name);
    }

    public function test_edicao_nao_promove_papel_mesmo_com_dados_adicionais_e_recusa_nao_aluno(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno']);
        $this->preparar('/editaraluno', "{$aluno->id} | Nome seguro | -");
        $dados = $this->dados() + ['nivel' => 'admin', 'password' => 'invadida', 'email' => 'outro@example.test', 'cpf' => '11144477735'];
        $senha = $aluno->password;
        $this->executar('editar_aluno', $dados);
        $this->assertSame('aluno', $aluno->fresh()->nivel);
        $this->assertSame($senha, $aluno->fresh()->password);
        $this->assertNotSame('outro@example.test', $aluno->fresh()->email);
        $this->assertNotSame('11144477735', $aluno->fresh()->cpf);
        $this->assertArrayNotHasKey('reply_markup', $this->preparar('/editaraluno', "{$this->admin->id} | Admin | -", 20));
    }

    public function test_recuperacao_requer_email_confirmado_aluno_ativo_e_email_sem_ambiguidade(): void
    {
        foreach ([['email_verified_at' => null], ['ativo' => false], ['nivel' => 'professor'], ['email' => null]] as $atributos) {
            $aluno = User::factory()->create(array_merge(['nivel' => 'aluno', 'ativo' => true, 'email_verified_at' => now()], $atributos));
            $this->assertArrayNotHasKey('reply_markup', $this->preparar('/recuperaracesso', (string) $aluno->id));
        }
        $aluno = User::factory()->create(['nivel' => 'aluno', 'email' => 'duplicado@example.test']);
        User::factory()->create(['email' => 'DUPLICADO@example.test']);
        $this->assertArrayNotHasKey('reply_markup', $this->preparar('/recuperaracesso', (string) $aluno->id));
        $this->assertDatabaseCount('jobs', 0);
        Notification::assertNothingSent();
    }

    public function test_recuperacao_enfileira_uma_vez_sem_rede_token_ou_senha_na_transacao(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $senha = $aluno->password;
        $previa = $this->preparar('/recuperaracesso', (string) $aluno->id);
        $this->assertDatabaseCount('jobs', 0);
        $this->confirmar($previa);
        $this->confirmar($previa, 12);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertSame($senha, $aluno->fresh()->password);
        Notification::assertNothingSent();
        Http::assertNothingSent();
        $this->assertStringNotContainsString($senha, DB::table('jobs')->value('payload'));
        $this->assertStringNotContainsString($senha, json_encode($previa));
    }

    public function test_fila_e_atomica_com_rollback_e_worker_usa_recuperacao_existente(): void
    {
        config()->set('queue.connections.database.queue', 'fila-alunos-teste');
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->preparar('/recuperaracesso', (string) $aluno->id);
        $dados = $this->dados();
        DB::beginTransaction();
        $this->executar('recuperar_acesso', $dados);
        $this->assertDatabaseCount('jobs', 1);
        DB::rollBack();
        $this->assertDatabaseCount('jobs', 0);
        $this->executar('recuperar_acesso', $dados);
        $fila = new DatabaseQueue(DB::connection(), 'jobs');
        $fila->setContainer(app());
        $job = $fila->pop(config('queue.connections.database.queue', 'default'));
        // O RefreshDatabase envolve cada teste em transação; simula o worker em outro processo.
        DB::commit();
        $job->fire();
        Notification::assertSentTo($aluno, ResetPassword::class);
        $this->assertDatabaseCount('password_reset_tokens', 1);
        Http::assertNothingSent();
    }

    public function test_recuperacao_revalida_estado_antes_de_enfileirar_e_antes_do_envio(): void
    {
        config()->set('queue.connections.database.queue', 'fila-alunos-teste');
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->preparar('/recuperaracesso', (string) $aluno->id);
        $dados = $this->dados();
        $aluno->forceFill(['email_verified_at' => null])->save();
        $this->assertNull($this->executar('recuperar_acesso', $dados));
        $this->assertDatabaseCount('jobs', 0);
        $aluno->forceFill(['email_verified_at' => now()])->save();
        $this->preparar('/recuperaracesso', (string) $aluno->id, 20);
        $this->executar('recuperar_acesso', $this->dados());
        $aluno->update(['ativo' => false]);
        $fila = new DatabaseQueue(DB::connection(), 'jobs');
        $fila->setContainer(app());
        $job = $fila->pop(config('queue.connections.database.queue', 'default'));
        DB::commit();
        $job->fire();
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_executor_revalida_privilegios_e_proposta_cancelada_nao_grava(): void
    {
        $previa = $this->preparar('/novoaluno', 'Aluno | - | -');
        $dados = $this->dados();
        $outro = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $this->assertNull($this->executar('criar_aluno', $dados, $outro));
        $codigo = substr($previa['reply_markup']['inline_keyboard'][0][0]['callback_data'], strlen('confirmar:'));
        app(ConfirmacoesTelegram::class)->decidir('707', 'cancelar', $codigo, 11);
        $this->confirmar($previa, 12);
        $this->assertDatabaseMissing('users', ['name' => 'Aluno']);
        $this->admin->update(['ativo' => false]);
        $this->assertNull($this->executar('criar_aluno', $dados));
    }

    public function test_limites_validos_e_telefone_removido_sao_aceitos(): void
    {
        $this->preparar('/novoaluno', str_repeat('á', 255).' | - | -');
        $aluno = $this->executar('criar_aluno', $this->dados());
        $this->assertNotNull($aluno);
        $this->preparar('/editaraluno', "{$aluno->id} | AB | +123456789012345", 20);
        $this->executar('editar_aluno', $this->dados());
        $this->assertSame('123456789012345', $aluno->fresh()->telefone);
        $this->assertArrayNotHasKey('reply_markup', $this->preparar('/editaraluno', "{$aluno->id} | AB | +1234567890123456", 21));
        $this->preparar('/editaraluno', "{$aluno->id} | AB | -", 22);
        $this->executar('editar_aluno', $this->dados());
        $this->assertNull($aluno->fresh()->telefone);
    }

    public function test_falha_de_email_permanece_duravel_na_fila_de_falhas(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->preparar('/recuperaracesso', (string) $aluno->id);
        $this->executar('recuperar_acesso', $this->dados());
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Falha simulada no transporte de e-mail.'));
        DB::commit();
        $this->artisan('queue:work', ['connection' => 'database', '--queue' => config('queue.connections.database.queue', 'default'), '--once' => true, '--tries' => 1, '--sleep' => 0])->assertExitCode(0);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 1);
        Http::assertNothingSent();
    }

    public function test_revogacao_apos_previa_impede_confirmacao_e_nenhuma_senha_vai_ao_bot(): void
    {
        $previa = $this->preparar('/novoaluno', 'Aluno bloqueado | - | -');
        $this->admin->update(['ativo' => false]);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'criar_aluno', 'status' => 'cancelado']);
        $this->assertDatabaseMissing('users', ['name' => 'Aluno bloqueado']);
        $this->assertStringNotContainsString('password', json_encode($previa));
        $this->assertStringNotContainsString('token', json_encode($previa));
        $this->expectException(HttpException::class);
        $this->confirmar($previa);
    }

    public function test_producao_nao_confunde_array_ou_log_com_entrega_de_recuperacao(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->preparar('/recuperaracesso', (string) $aluno->id);
        $dados = $this->dados();
        $this->app->instance('env', 'production');
        foreach (['array', 'log'] as $mailer) {
            config()->set('mail.default', $mailer);
            $previa = $this->preparar('/recuperaracesso', (string) $aluno->id, 20);
            $this->assertArrayNotHasKey('reply_markup', $previa);
            $this->assertStringContainsString('e-mail', $previa['text']);
            $this->assertNull($this->executar('recuperar_acesso', $dados));
        }
        $this->assertDatabaseCount('jobs', 0);
        Notification::assertNothingSent();
        DB::commit();
        $this->expectException(RuntimeException::class);
        app(AlunosAdministrativosTelegram::class)->enviarRecuperacao($aluno->id, $dados['estado_hash']);
    }

    public function test_recuperacao_respeita_fila_database_configurada_no_worker(): void
    {
        config()->set('queue.connections.database.queue', 'fila-alunos-teste');
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->preparar('/recuperaracesso', (string) $aluno->id);
        $this->executar('recuperar_acesso', $this->dados());
        $this->assertDatabaseHas('jobs', ['queue' => 'fila-alunos-teste']);
        $this->assertDatabaseMissing('jobs', ['queue' => 'default']);
    }

    public function test_falha_transitoria_aguarda_throttle_e_reenvia_no_worker(): void
    {
        config()->set('queue.connections.database.queue', 'fila-alunos-teste');
        config()->set('auth.passwords.users.throttle', 60);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->preparar('/recuperaracesso', (string) $aluno->id);
        $this->executar('recuperar_acesso', $this->dados());
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Falha transitória de transporte.'));
        DB::commit();
        $argumentos = ['connection' => 'database', '--queue' => 'fila-alunos-teste', '--once' => true, '--tries' => 3, '--sleep' => 0];
        $this->artisan('queue:work', $argumentos)->assertExitCode(0);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('failed_jobs', 0);
        $this->assertGreaterThanOrEqual(now()->addSeconds(60)->timestamp, (int) DB::table('jobs')->value('available_at'));
        Notification::fake();
        $this->travel(120)->seconds();
        $this->artisan('queue:work', $argumentos)->assertExitCode(0);
        Notification::assertSentTo($aluno, ResetPassword::class);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
        Http::assertNothingSent();
    }

    private function alunoPendente(array $atributos = []): User
    {
        $aluno = User::factory()->create(array_merge(['nivel' => 'aluno', 'ativo' => true, 'matricula_login' => 'permanente-'.(User::max('id') + 1)], $atributos));
        $aluno->forceFill(['primeiro_acesso_pendente' => true, 'email_verified_at' => null])->save();

        return $aluno;
    }

    public function test_ativacao_enfileirada_apos_cadastro_permite_definir_senha_e_login_por_matricula(): void
    {
        config()->set('queue.connections.database.queue', 'fila-alunos-teste');
        $this->preparar('/novoaluno', 'Primeiro Acesso | primeiro@example.test | -');
        $aluno = $this->executar('criar_aluno', $this->dados());
        $this->assertDatabaseHas('jobs', ['queue' => 'fila-alunos-teste']);
        $payload = DB::table('jobs')->value('payload');
        $this->assertStringNotContainsString($aluno->email, $payload);
        $this->assertStringNotContainsString($aluno->password, $payload);
        $fila = new DatabaseQueue(DB::connection(), 'jobs');
        $fila->setContainer(app());
        $job = $fila->pop('fila-alunos-teste');
        DB::commit();
        $job->fire();
        Notification::assertSentTo($aluno, AtivarAcessoAluno::class);
        $notificacao = Notification::sent($aluno, AtivarAcessoAluno::class)->sole();
        $url = $notificacao->toMail($aluno)->actionUrl;
        $senha = 'SenhaInicial#2026';
        $this->post($url, ['password' => $senha, 'password_confirmation' => $senha])->assertRedirect(route('aluno.login'));
        $aluno->refresh();
        $this->assertNotNull($aluno->email_verified_at);
        $this->assertFalse($aluno->primeiro_acesso_pendente);
        $this->assertTrue(Hash::check($senha, $aluno->password));
        $this->post(route('aluno.login.submit'), ['email' => $aluno->matricula_login, 'password' => $senha])->assertRedirect(route('aluno.dashboard'));
        $this->assertAuthenticatedAs($aluno);
        Http::assertNothingSent();
    }

    public function test_reenvio_ativacao_exige_confirmacao_unica_e_snapshot_atual(): void
    {
        $aluno = $this->alunoPendente();
        $previa = $this->preparar('/ativaracesso', (string) $aluno->id);
        $this->assertArrayHasKey('reply_markup', $previa);
        $this->assertDatabaseCount('jobs', 0);
        $this->confirmar($previa);
        $this->confirmar($previa, 12);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'ativar_acesso', 'status' => 'confirmado']);
        $this->preparar('/ativaracesso', (string) $aluno->id, 20);
        $dados = $this->dados();
        $aluno->update(['name' => 'Nome mudou']);
        $this->assertNull($this->executar('ativar_acesso', $dados));
        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_ativacao_rejeita_contas_inelegiveis_email_ambiguo_e_job_destinatario_alterado(): void
    {
        foreach ([['ativo' => false], ['nivel' => 'admin'], ['email' => null], ['email' => 'invalido'], ['primeiro_acesso_pendente' => false], ['email_verified_at' => now()]] as $mudanca) {
            $aluno = $this->alunoPendente();
            $aluno->forceFill($mudanca)->save();
            $this->assertArrayNotHasKey('reply_markup', $this->preparar('/ativaracesso', (string) $aluno->id));
        }
        $aluno = $this->alunoPendente(['email' => 'ambiguo@example.test']);
        User::factory()->create(['email' => 'AMBIGUO@example.test']);
        $this->assertArrayNotHasKey('reply_markup', $this->preparar('/ativaracesso', (string) $aluno->id));
        $aluno = $this->alunoPendente(['email' => 'destino@example.test']);
        $job = new AtivarAlunoTelegram($aluno->id, hash('sha256', $aluno->email));
        $aluno->update(['email' => 'novo@example.test']);
        DB::commit();
        $job->handle();
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_enfileirar_ativacao_existente_e_atomico_e_smtp_nao_bloqueia_cadastro(): void
    {
        config()->set('queue.connections.database.queue', 'fila-alunos-teste');
        $aluno = $this->alunoPendente();
        DB::beginTransaction();
        app(AlunosAdministrativosTelegram::class)->enfileirarAtivacao($aluno);
        $this->assertDatabaseCount('jobs', 1);
        DB::rollBack();
        $this->assertDatabaseCount('jobs', 0);
        $this->app->instance('env', 'production');
        config()->set('mail.default', 'array');
        $this->preparar('/novoaluno', 'Aluno com envio pendente | futuro@example.test | -');
        $novo = $this->executar('criar_aluno', $this->dados());
        $this->assertNotNull($novo);
        $this->assertTrue($novo->primeiro_acesso_pendente);
        $this->assertDatabaseHas('jobs', ['queue' => 'fila-alunos-teste']);
        $this->assertArrayNotHasKey('reply_markup', $this->preparar('/ativaracesso', (string) $novo->id, 20));
        DB::commit();
        $this->expectException(RuntimeException::class);
        (new AtivarAlunoTelegram($novo->id, hash('sha256', $novo->email)))->handle();
    }

    public function test_transporte_compartilhado_bloqueia_array_log_aninhados_e_ciclos(): void
    {
        $this->assertTrue(app(TransporteEmailTelegram::class)->disponivel());
        $this->app->instance('env', 'production');
        foreach ([
            ['array', ['transport' => 'array']], ['log', ['transport' => 'log']],
            ['apelido', ['transport' => 'log']],
            ['alternativo', ['transport' => 'failover', 'mailers' => ['smtp', 'log']]],
            ['ciclo', ['transport' => 'roundrobin', 'mailers' => ['ciclo']]],
        ] as [$nome, $config]) {
            config()->set('mail.default', $nome);
            config()->set('mail.mailers.'.$nome, $config);
            $this->assertFalse(app(TransporteEmailTelegram::class)->disponivel());
        }
        config()->set('mail.default', 'smtp');
        $this->assertTrue(app(TransporteEmailTelegram::class)->disponivel());
    }

    public function test_job_ativacao_retem_falha_e_reenvia_apos_throttle_com_tries_proprios(): void
    {
        config()->set('queue.connections.database.queue', 'fila-alunos-teste');
        config()->set('auth.passwords.users.throttle', 60);
        $aluno = $this->alunoPendente();
        app(AlunosAdministrativosTelegram::class)->enfileirarAtivacao($aluno);
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Falha transitória simulada.'));
        DB::commit();
        $argumentos = ['connection' => 'database', '--queue' => 'fila-alunos-teste', '--once' => true, '--tries' => 1, '--sleep' => 0];
        $this->artisan('queue:work', $argumentos)->assertExitCode(0);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('failed_jobs', 0);
        $this->assertGreaterThan(now()->addSeconds(60)->timestamp, (int) DB::table('jobs')->value('available_at'));
        Notification::fake();
        $this->travel(120)->seconds();
        $this->artisan('queue:work', $argumentos)->assertExitCode(0);
        Notification::assertSentTo($aluno, AtivarAcessoAluno::class);
        $this->assertDatabaseCount('jobs', 0);
        Http::assertNothingSent();
    }

    public function test_job_ativacao_revalida_papel_atividade_confirmacao_e_pendencia_antes_do_broker(): void
    {
        $jobs = [];
        foreach ([['ativo' => false], ['nivel' => 'professor'], ['primeiro_acesso_pendente' => false], ['email_verified_at' => now()]] as $mudanca) {
            $aluno = $this->alunoPendente();
            $jobs[] = new AtivarAlunoTelegram($aluno->id, hash('sha256', mb_strtolower(trim($aluno->email))));
            $aluno->forceFill($mudanca)->save();
        }
        DB::commit();
        foreach ($jobs as $job) {
            $job->handle();
        }
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
        Http::assertNothingSent();
    }
}
