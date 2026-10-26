<?php

// Execução exclusivamente em PostgreSQL descartável, fora do bootstrap SQLite do PHPUnit.
use App\Models\Aviso;
use App\Models\ChamadaFrequencia;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Frequencia;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use App\Services\Telegram\ConfirmacoesTelegram;
use App\Services\Telegram\TelegramAdministrativo;
use App\Services\VincularMatriculaTurmaService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (app()->environment() !== 'testing'
    || config('database.default') !== 'pgsql'
    || DB::getDatabaseName() !== 'cetsadp_telegram_ci') {
    fwrite(STDERR, "Bloqueado: exige APP_ENV=testing, pgsql e banco cetsadp_telegram_ci.\n");
    exit(2);
}

function verify(bool $condition, string $message): void
{
    if ($condition === false) {
        throw new RuntimeException($message);
    }
}

if (($argv[1] ?? '') === 'worker') {
    [, , $operation, $ownerId, $chatId, $callback, $updateId, $gate] = $argv;
    config()->set('services.telegram.admin_user_id', (int) $ownerId);
    if ($operation === 'vincular') {
        touch($gate.'.ready');
    }
    $start = microtime(true);
    while (file_exists($gate) === false) {
        if (microtime(true) - $start > 25) {
            fwrite(STDERR, "Barreira expirada.\n");
            exit(3);
        }
        usleep(10000);
    }
    try {
        if ($operation === 'vincular') {
            [$matriculaId, $turmaId] = array_map('intval', explode(':', $callback));
            DB::statement("SET lock_timeout = '10s'");
            app(VincularMatriculaTurmaService::class)->executar(
                Matricula::findOrFail($matriculaId), Turma::findOrFail($turmaId));
            touch($gate.'.done');
        } elseif ($operation === 'revogar') {
            app(TelegramAdministrativo::class)->revogar(User::findOrFail((int) $ownerId));
        } else {
            app(ConfirmacoesTelegram::class)->decidir($chatId, 'confirmar', $callback, (int) $updateId);
        }
        exit(0);
    } catch (HttpException $e) {
        // Um clique que chegou após a revogação deve ser negado.
        exit($e->getStatusCode() === 403 ? 0 : 4);
    } catch (Throwable $e) {
        fwrite(STDERR, get_class($e).' (sem payload sensível)'.PHP_EOL);
        exit(5);
    }
}

$runner = realpath(__FILE__);
verify($runner !== false, 'Runner indisponível');
$scenarios = ['confirmar-confirmar', 'confirmar-revogar', 'aviso-confirmar-confirmar', 'aviso-confirmar-revogar'];
for ($round = 1; $round <= 4; $round++) {
    foreach ($scenarios as $scenario) {
        $owner = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $owner->id);
        $chat = (string) (900000 + $owner->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $owner->id, 'chat_id' => $chat,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $avisoReal = str_starts_with($scenario, 'aviso-');
        $revogar = str_ends_with($scenario, '-revogar');
        $titulo = 'Concorrência descartável '.$owner->id;
        $prepared = $avisoReal
            ? app(ConfirmacoesTelegram::class)->prepararAviso($chat, $titulo.' | Material sintético do CI.', $owner->id * 1000 + 1)
            : app(ConfirmacoesTelegram::class)->preparar($chat, $owner->id * 1000 + 1);
        $callback = substr($prepared['reply_markup']['inline_keyboard'][0][0]['callback_data'], strlen('confirmar:'));
        $confirmation = DB::table('telegram_confirmacoes')->where('user_id', $owner->id)->first();
        verify($confirmation !== null, 'Proposta não criada');
        $gate = sys_get_temp_dir().'/telegram-race-'.bin2hex(random_bytes(12));
        $commands = [
            ['confirmar', $owner->id * 1000 + 2],
            [$revogar ? 'revogar' : 'confirmar', $owner->id * 1000 + 3],
        ];
        $workers = [];
        try {
            foreach ($commands as [$operation, $updateId]) {
                $command = [PHP_BINARY, $runner, 'worker', $operation, (string) $owner->id, $chat,
                    $callback, (string) $updateId, $gate];
                $pipes = [];
                $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                verify(is_resource($process), 'Falha ao criar processo');
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            touch($gate);
            foreach ($workers as [$process, $pipes]) {
                stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $code = proc_close($process);
                verify($code === 0, 'Worker falhou: '.$error);
            }
            $workers = [];
        } finally {
            @unlink($gate);
            foreach ($workers as [$process, $pipes]) {
                proc_terminate($process);
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
                proc_close($process);
            }
        }

        $current = DB::table('telegram_confirmacoes')->where('id', $confirmation->id)->first();
        verify(in_array($current->status, ['confirmado', 'cancelado'], true), 'Proposta ficou pendente');
        $decisionCount = DB::table('telegram_auditoria')->where('confirmacao_id', $confirmation->id)
            ->where('antes', 'pendente')->count();
        verify($decisionCount <= 1, 'Duas decisões na auditoria');
        if (! $revogar) {
            verify($current->status === 'confirmado' && $decisionCount === 1,
                'Confirmação dupla não consumida exatamente uma vez');
        } else {
            verify(DB::table('telegram_vinculos')->where('user_id', $owner->id)->exists() === false,
                'Revogação não excluiu vínculo');
            $revocations = DB::table('telegram_auditoria_revogacoes')
                ->where('confirmacao_id', $confirmation->id)->count();
            verify($decisionCount + $revocations === 1,
                'Confirmação/revogação sem transição única auditada');
        }
        if ($avisoReal) {
            verify(Aviso::where('titulo', $titulo)->count() === ($current->status === 'confirmado' ? 1 : 0),
                'Publicação precisa acompanhar a decisão exatamente uma vez');
            verify(! DB::table('telegram_publicacoes')->where('confirmacao_id', $confirmation->id)->exists(),
                'Texto criptografado remanescente após confirmação ou revogação');
        }
        echo "OK {$scenario} rodada {$round}\n";
    }
}
// A lista de alunos não pode mudar entre a validação de completude e o commit.
$curso = Curso::create(['titulo' => 'Concorrência de chamada', 'ativo' => true]);
$modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo', 'ordem' => 1]);
$disciplina = Disciplina::create(['curso_id' => $curso->id, 'modulo_id' => $modulo->id,
    'codigo' => 'CI-CH-1', 'nome' => 'Disciplina', 'carga_horaria' => 40, 'ordem' => 1]);
$turma = Turma::create(['curso_id' => $curso->id, 'nome' => 'Turma CI', 'ano_letivo' => 2026,
    'valor_total_curso' => 1200, 'qtd_parcelas' => 12, 'status' => Turma::STATUS_ABERTA]);
$chamada = ChamadaFrequencia::create(['turma_id' => $turma->id, 'disciplina_id' => $disciplina->id,
    'data_aula' => '2026-10-26', 'status' => ChamadaFrequencia::STATUS_RASCUNHO, 'criada_por' => $owner->id]);
$matriculas = [];
foreach ([1, 2] as $numero) {
    $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
    $matriculas[] = Matricula::create(['user_id' => $aluno->id, 'curso_id' => $curso->id,
        'turma_id' => $numero === 1 ? $turma->id : null, 'data_matricula' => '2026-10-26',
        'status' => 'ativo', 'numero_matricula' => 'CI-CH-'.$numero, 'dia_vencimento' => 10]);
}
Frequencia::create(['chamada_frequencia_id' => $chamada->id, 'matricula_id' => $matriculas[0]->id,
    'situacao' => 'presente', 'registrado_por' => $owner->id]);
// O último cenário revogou o vínculo; restaura somente a fixture descartável.
DB::table('telegram_vinculos')->insert(['user_id' => $owner->id, 'chat_id' => $chat,
    'created_at' => now(), 'updated_at' => now()]);
$prepared = app(ConfirmacoesTelegram::class)->prepararConclusaoChamada($chat, (string) $chamada->id, 900001);
$callback = substr($prepared['reply_markup']['inline_keyboard'][0][0]['callback_data'], strlen('confirmar:'));
$gate = sys_get_temp_dir().'/telegram-chamada-race-'.bin2hex(random_bytes(12));
$command = [PHP_BINARY, $runner, 'worker', 'vincular', (string) $owner->id, $chat,
    $matriculas[1]->id.':'.$turma->id, '900002', $gate];
$pipes = [];
$process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
verify(is_resource($process), 'Falha ao criar processo de vínculo');
fclose($pipes[0]);
$checado = false;
$vinculoPrematuro = false;
try {
    $inicio = microtime(true);
    while (! file_exists($gate.'.ready')) {
        verify(microtime(true) - $inicio < 15, 'Worker de vínculo não iniciou');
        usleep(10000);
    }
    DB::listen(function ($query) use ($gate, &$checado, &$vinculoPrematuro) {
        if (! $checado && str_contains($query->sql, 'select "matricula_id" from "frequencias"')) {
            $checado = true;
            touch($gate);
            usleep(1000000);
            $vinculoPrematuro = file_exists($gate.'.done');
        }
    });
    app(ConfirmacoesTelegram::class)->decidir($chat, 'confirmar', $callback, 900003);
    stream_get_contents($pipes[1]);
    $erro = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    verify(proc_close($process) === 0, 'Vínculo concorrente falhou: '.$erro);
    $process = null;
    verify($checado && ! $vinculoPrematuro, 'Lista de alunos mudou antes do commit da conclusão');
    verify($chamada->fresh()->status === ChamadaFrequencia::STATUS_CONCLUIDA, 'Chamada não concluída');
    verify(DB::table('matricula_turma')->where('matricula_id', $matriculas[1]->id)
        ->where('turma_id', $turma->id)->exists(), 'Vínculo não continuou após a conclusão');
    echo "OK concluir chamada serializa novo vínculo\n";
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($process);
    }
    foreach ([$gate, $gate.'.ready', $gate.'.done'] as $arquivo) {
        @unlink($arquivo);
    }
}

echo "Concorrência PostgreSQL validada no banco efêmero.\n";
