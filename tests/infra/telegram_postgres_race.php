<?php

// Execução exclusivamente em PostgreSQL descartável, fora do bootstrap SQLite do PHPUnit.
use App\Models\Aviso;
use App\Models\User;
use App\Services\Telegram\ConfirmacoesTelegram;
use App\Services\Telegram\TelegramAdministrativo;
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
    $start = microtime(true);
    while (file_exists($gate) === false) {
        if (microtime(true) - $start > 25) {
            fwrite(STDERR, "Barreira expirada.\n");
            exit(3);
        }
        usleep(10000);
    }
    try {
        if ($operation === 'revogar') {
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
echo "Concorrência PostgreSQL validada no banco efêmero.\n";
