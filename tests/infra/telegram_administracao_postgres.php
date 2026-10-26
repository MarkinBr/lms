<?php

/**
 * Integração destrutível: exige PostgreSQL efêmero já migrado, sem queue workers.
 * APP_ENV=testing DB_CONNECTION=pgsql DB_DATABASE=cetsadp_telegram_ci \
 *   php tests/infra/telegram_administracao_postgres.php
 * DB_HOST, DB_PORT, DB_USERNAME e DB_PASSWORD devem apontar para o banco descartável.
 * Não migra, não limpa tabelas e não executa jobs; o chamador descarta o banco ao final.
 * Funciona com composer --no-dev: sem Faker, PHPUnit, Mockery ou bootstrap de Tests\TestCase.
 */

namespace Tests\Infra\TelegramAdministracaoPostgres;

use App\Jobs\AtivarAlunoTelegram;
use App\Jobs\NotificarDecisaoTelegram;
use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Nota;
use App\Models\User;
use App\Services\Telegram\ConfirmacoesTelegram;
use App\Services\Telegram\ConsultasAcademicasTelegram;
use App\Services\Telegram\InscricoesAdministrativasTelegram;
use App\Services\Telegram\NotasAdministrativasTelegram;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Http\Client\Factory;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

// Antes até do bootstrap: nunca abrir conexão usando configuração de produção.
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'pgsql'
    || getenv('DB_DATABASE') !== 'cetsadp_telegram_ci') {
    fwrite(STDERR, "Bloqueado: exige APP_ENV=testing, DB_CONNECTION=pgsql e DB_DATABASE=cetsadp_telegram_ci.\n");
    exit(2);
}

$worker = ($argv[1] ?? '') === 'worker';
$ambiente = [
    'APP_CONFIG_CACHE' => 'bootstrap/cache/telegram-administracao-ci-config-inexistente.php',
    'APP_DEBUG' => 'false', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'database',
    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'LOG_CHANNEL' => 'null',
    'BCRYPT_ROUNDS' => '4', 'TELEGRAM_ENABLED' => 'false',
];
if (! $worker) {
    // Chave exclusivamente sintética herdada pelos workers para abrir as propostas.
    $ambiente['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
    $ambiente['DB_QUEUE'] = 'telegram-admin-ci-'.bin2hex(random_bytes(6));
}
foreach ($ambiente as $nome => $valor) {
    putenv($nome.'='.$valor);
    $_ENV[$nome] = $_SERVER[$nome] = $valor;
}

class FalhaIntegracao extends RuntimeException {}

function verificar(bool $condicao, string $mensagem): void
{
    if (! $condicao) {
        throw new FalhaIntegracao($mensagem);
    }
}

function semEnvios(): void
{
    global $tentativasHttp;

    // Os métodos assert* dos fakes dependem de PHPUnit; as leituras abaixo não dependem.
    verificar($tentativasHttp === 0 && Http::recorded()->isEmpty(), 'Houve tentativa de HTTP externo.');
    verificar(Mail::sent(Mailable::class)->isEmpty(), 'Houve tentativa de envio de e-mail.');
    verificar(Mail::queued(Mailable::class)->isEmpty(), 'Houve tentativa de enfileirar mailable.');
    verificar(Notification::sentNotifications() === [], 'Houve tentativa de notificação externa.');
}

function codigoConfirmacao(array $previa): string
{
    $callback = $previa['reply_markup']['inline_keyboard'][0][0]['callback_data'] ?? '';
    verificar(is_string($callback) && preg_match('/^confirmar:[0-9a-f]{32}$/D', $callback) === 1,
        'Preparação não produziu confirmação válida.');

    return substr($callback, strlen('confirmar:'));
}

function aguardar(callable $pronto, string $erro, float $segundos = 15): void
{
    $inicio = microtime(true);
    while (! $pronto()) {
        verificar(microtime(true) - $inicio < $segundos, $erro);
        usleep(25000);
        clearstatcache();
    }
}

function usuarioSintetico(string $identificador, string $nivel = 'aluno'): User
{
    return User::create([
        'name' => 'Fixture PostgreSQL '.$identificador,
        'email' => $identificador.'@example.test',
        'password' => Str::random(48), 'nivel' => $nivel, 'ativo' => true,
    ]);
}

function inscricaoSintetica(Curso $curso, CourseModality $modalidade, string $identificador): Application
{
    do {
        $cpf = '900'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    } while (User::where('cpf', $cpf)->exists() || Application::withTrashed()->where('cpf', $cpf)->exists());

    return Application::create([
        'protocol' => $identificador, 'nome_completo' => 'Candidato sintético '.$identificador,
        'cpf' => $cpf, 'data_nascimento' => '1990-01-01', 'sexo' => 'M',
        'nacionalidade' => 'Brasileira', 'email' => $identificador.'@example.test',
        'telefone' => '11000000000', 'cep' => '00000000', 'logradouro' => 'Rua Sintética CI',
        'numero' => '0', 'bairro' => 'Fixture', 'cidade' => 'Cidade Sintética', 'estado' => 'SP',
        'escolaridade' => 'medio_completo', 'curso_id' => $curso->id,
        'course_modality_id' => $modalidade->id, 'status' => Application::STATUS_EM_ANALISE,
    ]);
}

/** Dois processos devem disputar o vínculo bloqueado pelo coordenador antes do commit. */
function clicarConcorrentemente(User $dono, string $chat, string $codigo, int $updateBase): void
{
    $diretorio = sys_get_temp_dir().'/telegram-admin-pg-'.bin2hex(random_bytes(12));
    verificar(mkdir($diretorio, 0700), 'Não foi possível criar barreira temporária.');
    $processos = [];
    $nomes = [];

    try {
        DB::beginTransaction();
        verificar(DB::table('telegram_vinculos')->where('chat_id', $chat)->lockForUpdate()->first() !== null,
            'Vínculo da fixture ausente.');
        foreach ([0, 1] as $indice) {
            $nomes[] = 'tgadmin-'.basename($diretorio).'-'.$indice;
            $processo = new Process([
                PHP_BINARY, __FILE__, 'worker', (string) $dono->id, $chat, $codigo,
                (string) ($updateBase + $indice), $diretorio, (string) $indice, $nomes[$indice],
            ], dirname(__DIR__, 2), timeout: 45);
            $processo->start();
            $processos[] = $processo;
        }
        aguardar(function () use ($diretorio, $processos): bool {
            foreach ($processos as $processo) {
                verificar($processo->isRunning(), 'Worker terminou antes de alcançar a barreira.');
            }

            return is_file($diretorio.'/ready-0') && is_file($diretorio.'/ready-1');
        }, 'Workers não inicializaram dentro do prazo.');
        verificar(touch($diretorio.'/go'), 'Não foi possível liberar a barreira.');

        aguardar(function () use ($nomes, $processos): bool {
            foreach ($processos as $processo) {
                verificar($processo->isRunning(), 'Worker terminou antes da disputa por lock.');
            }
            DB::select('SELECT pg_stat_clear_snapshot()');
            $atividade = DB::selectOne(
                "SELECT COUNT(*) AS bloqueados FROM pg_stat_activity WHERE datname = current_database()
                    AND application_name IN (?, ?) AND state = 'active' AND wait_event_type = 'Lock'",
                $nomes,
            );

            return (int) $atividade->bloqueados === 2;
        }, 'PostgreSQL não confirmou os dois cliques concorrentes bloqueados.');
        DB::commit();

        foreach ($processos as $processo) {
            verificar($processo->wait() === 0, 'Worker de confirmação falhou; saída omitida para não revelar payload.');
            verificar(trim($processo->getOutput()) === 'OK worker', 'Worker não confirmou conclusão segura.');
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($processos as $processo) {
            if ($processo->isRunning()) {
                $processo->stop(1);
            }
        }
        foreach (['ready-0', 'ready-1', 'go'] as $arquivo) {
            if (is_file($diretorio.'/'.$arquivo)) {
                unlink($diretorio.'/'.$arquivo);
            }
        }
        rmdir($diretorio);
    }
}

try {
    require dirname(__DIR__, 2).'/vendor/autoload.php';
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $app->loadEnvironmentFrom('.env.telegram-administracao-ci-inexistente');
    $app->make(Kernel::class)->bootstrap();

    verificar(app()->environment('testing') && config('database.default') === 'pgsql'
        && DB::getDatabaseName() === 'cetsadp_telegram_ci', 'Configuração efetiva fora do banco descartável.');
    verificar(DB::selectOne('SELECT current_database() AS nome')->nome === 'cetsadp_telegram_ci',
        'Servidor conectado a outro banco.');
    DB::statement("SET lock_timeout = '25s'");
    DB::statement("SET statement_timeout = '35s'");

    config()->set('queue.default', 'database');
    config()->set('queue.connections.database', [
        'driver' => 'database', 'connection' => 'pgsql', 'table' => 'jobs',
        'queue' => getenv('DB_QUEUE'), 'retry_after' => 90, 'after_commit' => false,
    ]);
    verificar(Queue::connection('database') instanceof DatabaseQueue, 'A fila precisa ser database real.');
    Http::swap(new Factory);
    Http::preventStrayRequests();
    $tentativasHttp = 0;
    Http::fake(function () use (&$tentativasHttp) {
        $tentativasHttp++;
        throw new FalhaIntegracao('HTTP proibido nesta integração.');
    });
    Mail::fake();
    Notification::fake();

    if ($worker) {
        verificar(count($argv) === 9, 'Argumentos do worker inválidos.');
        [, , $donoId, $chat, $codigo, $updateId, $diretorio, $indice, $nome] = $argv;
        verificar(ctype_digit($donoId) && ctype_digit($updateId) && in_array($indice, ['0', '1'], true)
            && preg_match('/^[0-9a-f]{32}$/D', $codigo) === 1, 'Identificadores do worker inválidos.');
        verificar(realpath(dirname($diretorio)) === realpath(sys_get_temp_dir())
            && preg_match('/^telegram-admin-pg-[0-9a-f]{24}$/D', basename($diretorio)) === 1
            && is_dir($diretorio), 'Barreira do worker fora do diretório temporário.');
        config()->set('services.telegram.admin_user_id', (int) $donoId);
        DB::selectOne("SELECT set_config('application_name', ?, false)", [$nome]);
        verificar(touch($diretorio.'/ready-'.$indice), 'Worker não conseguiu sinalizar início.');
        aguardar(fn () => is_file($diretorio.'/go'), 'Barreira do worker expirou.');
        app(ConfirmacoesTelegram::class)->decidir($chat, 'confirmar', $codigo, (int) $updateId);
        semEnvios();
        echo "OK worker\n";
        exit(0);
    }

    verificar(($argv[1] ?? '') === '', 'Use o runner sem argumentos.');
    verificar(DB::transactionLevel() === 0, 'Runner precisa de commits reais.');
    foreach (['applications', 'application_documents', 'telegram_vinculos', 'telegram_confirmacoes',
        'telegram_publicacoes', 'telegram_auditoria', 'matriculas', 'jobs', 'notas', 'password_reset_tokens'] as $tabela) {
        verificar(Schema::hasTable($tabela), 'Aplique as migrations antes da integração: tabela '.$tabela.' ausente.');
    }
    verificar(Schema::hasColumn('notas', 'user_id'), 'Schema canônico de notas ausente.');
    $execucao = bin2hex(random_bytes(6));
    $dono = usuarioSintetico('admin-'.$execucao, 'admin');
    config()->set('services.telegram.admin_user_id', $dono->id);
    $chat = (string) (800000000 + $dono->id);
    DB::table('telegram_vinculos')->insert([
        'user_id' => $dono->id, 'chat_id' => $chat, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $curso = Curso::create(['titulo' => 'Curso sintético PG '.$execucao, 'ativo' => true]);
    $modalidade = CourseModality::create(['curso_id' => $curso->id, 'nome' => 'CI sintético', 'ativo' => true]);
    $fila = (string) getenv('DB_QUEUE');
    $updateBase = random_int(1000000000000, 9000000000000);

    foreach (range(1, 4) as $rodada) {
        foreach (['aprovar', 'rejeitar'] as $indice => $decisao) {
            $inscricao = inscricaoSintetica($curso, $modalidade, 'PG-'.$execucao.'-'.$rodada.'-'.$indice);
            $usuariosAntes = User::count();
            $jobsAntes = DB::table('jobs')->where('queue', $fila)->count();
            $ultimoJobAntes = (int) (DB::table('jobs')->where('queue', $fila)->max('id') ?? 0);
            $tokensAntes = DB::table('password_reset_tokens')->count();
            $update = $updateBase + $rodada * 100 + $indice * 10;
            $rejeitar = $decisao === 'rejeitar';
            $motivo = 'Documentação sintética incompleta para teste CI';
            $codigo = codigoConfirmacao(app(InscricoesAdministrativasTelegram::class)->preparar(
                $chat, $rejeitar ? 'rejeitarinscricao' : 'aprovarinscricao',
                $inscricao->id.($rejeitar ? ' | '.$motivo : ''), $update,
            ));
            $confirmacao = DB::table('telegram_confirmacoes')->where('codigo_hash', hash('sha256', $codigo))->first();
            verificar($confirmacao !== null && $confirmacao->status === 'pendente', 'Proposta não ficou pendente.');
            verificar($inscricao->fresh()->status === Application::STATUS_EM_ANALISE, 'Preparação alterou a inscrição.');
            verificar(Matricula::where('application_id', $inscricao->id)->count() === 0, 'Preparação criou matrícula.');
            verificar(DB::table('jobs')->where('queue', $fila)->count() === $jobsAntes, 'Preparação enfileirou job.');
            clicarConcorrentemente($dono, $chat, $codigo, $update + 1);

            $atual = DB::table('telegram_confirmacoes')->where('id', $confirmacao->id)->first();
            $inscricao->refresh();
            verificar($atual->status === 'confirmado', 'Confirmação concorrente não terminou confirmada.');
            verificar(DB::table('telegram_auditoria')->where('confirmacao_id', $atual->id)
                ->where('antes', 'pendente')->where('depois', 'confirmado')->count() === 1,
                'Duplo clique não produziu exatamente uma decisão auditada.');
            verificar(! DB::table('telegram_publicacoes')->where('confirmacao_id', $atual->id)->exists(),
                'Payload criptografado não foi consumido.');
            verificar((int) $inscricao->analisado_por_id === (int) $dono->id && $inscricao->analisado_em !== null,
                'Autoria da decisão ausente.');
            verificar($inscricao->status === ($rejeitar ? Application::STATUS_REJEITADO : Application::STATUS_MATRICULADO),
                'Estado acadêmico incompatível com a decisão.');
            verificar(Matricula::where('application_id', $inscricao->id)->count() === ($rejeitar ? 0 : 1),
                'Quantidade de matrículas incompatível com a decisão.');
            verificar(User::count() === $usuariosAntes + ($rejeitar ? 0 : 1), 'Conta de aluno duplicada ou criada na rejeição.');
            if ($rejeitar) {
                verificar($inscricao->observacoes_secretaria === $motivo, 'Motivo da rejeição não preservado.');
                verificar($atual->recurso_tipo === 'applications' && $atual->recurso_id === $inscricao->id,
                    'Auditoria da rejeição não aponta para inscrição.');
            } else {
                $matricula = Matricula::where('application_id', $inscricao->id)->firstOrFail();
                verificar($matricula->user->email === mb_strtolower(trim($inscricao->email)) && $matricula->user->cpf === $inscricao->cpf
                    && $matricula->user->nivel === 'aluno' && $matricula->user->ativo,
                    'Matrícula não pertence à identidade sintética esperada.');
                verificar($atual->recurso_tipo === 'matriculas' && (int) $atual->recurso_id === (int) $matricula->id,
                    'Auditoria da aprovação não aponta para matrícula.');
            }
            $classesEsperadas = $rejeitar
                ? [NotificarDecisaoTelegram::class]
                : [AtivarAlunoTelegram::class, NotificarDecisaoTelegram::class];
            $quantidadeEsperada = count($classesEsperadas);
            verificar(DB::table('jobs')->where('queue', $fila)->count() === $jobsAntes + $quantidadeEsperada,
                'Duplo clique não gerou a quantidade esperada de jobs database para a decisão.');
            $novosJobs = DB::table('jobs')->where('queue', $fila)->where('id', '>', $ultimoJobAntes)->get();
            verificar($novosJobs->count() === $quantidadeEsperada, 'Jobs novos não correspondem à decisão atual.');
            $classesRecebidas = [];
            foreach ($novosJobs as $job) {
                verificar((int) $job->attempts === 0 && $job->reserved_at === null, 'Job foi executado ou reservado.');
                $payload = json_decode($job->payload, true, 512, JSON_THROW_ON_ERROR);
                $classe = $payload['displayName'] ?? '';
                verificar(in_array($classe, $classesEsperadas, true)
                    && ($payload['data']['commandName'] ?? '') === $classe,
                    'Job não corresponde à ativação ou notificação esperada.');
                $classesRecebidas[] = $classe;
                if ($classe === NotificarDecisaoTelegram::class) {
                    verificar(str_contains($payload['data']['command'] ?? '', $inscricao->id),
                        'Notificação não pertence à inscrição decidida.');
                }
            }
            sort($classesEsperadas);
            sort($classesRecebidas);
            verificar($classesRecebidas === $classesEsperadas,
                'Ativação ou decisão duplicada/ausente nos jobs da confirmação.');
            verificar(DB::table('password_reset_tokens')->count() === $tokensAntes,
                'Foi gerado token de acesso antes de executar a fila.');
            semEnvios();
            echo "OK {$decisao}: duplo clique concorrente, uma decisão e {$quantidadeEsperada} job(s) sem processamento; rodada {$rodada}\n";
        }
    }

    // Escreve pela confirmação real, sem aluno_id legado e sem contornar o CHECK PostgreSQL.
    $aluno = usuarioSintetico('notas-'.$execucao);
    $outro = usuarioSintetico('notas-outro-'.$execucao);
    $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo CI', 'ordem' => 1]);
    $disciplina = Disciplina::create([
        'curso_id' => $curso->id, 'modulo_id' => $modulo->id, 'codigo' => 'PG-'.$execucao,
        'nome' => 'Hermenêutica sintética '.$execucao, 'carga_horaria' => 40, 'ordem' => 1, 'ativa' => true,
    ]);
    Matricula::create([
        'user_id' => $aluno->id, 'curso_id' => $curso->id, 'status' => Matricula::STATUS_ATIVA,
        'data_matricula' => now()->toDateString(), 'numero_matricula' => 'PG-N-'.$execucao, 'dia_vencimento' => 10,
    ]);
    $jobsAntes = DB::table('jobs')->where('queue', $fila)->count();
    $codigo = codigoConfirmacao(app(NotasAdministrativasTelegram::class)->preparar(
        $chat, 'lancarnota', "{$aluno->id} | {$disciplina->id} | 2026.2 | 6 | 6 | 0 | 0", $updateBase + 900,
    ));
    verificar(Nota::where('user_id', $aluno->id)->count() === 0, 'Prévia já escreveu nota.');
    app(ConfirmacoesTelegram::class)->decidir($chat, 'confirmar', $codigo, $updateBase + 901);
    app(ConfirmacoesTelegram::class)->decidir($chat, 'confirmar', $codigo, $updateBase + 902);
    verificar(Nota::where('user_id', $aluno->id)->count() === 1, 'Confirmação de nota ausente ou duplicada.');
    $nota = Nota::where('user_id', $aluno->id)->firstOrFail();
    verificar($nota->media_final === '6.00' && $nota->situacao === 'Recuperação', 'Média/situação não persistiu no schema novo.');
    if (Schema::hasColumn('notas', 'aluno_id')) {
        verificar($nota->getAttribute('aluno_id') === null, 'Escrita nova preencheu vínculo legado inesperadamente.');
    }
    Nota::create([
        'user_id' => $outro->id, 'disciplina' => 'SENTINELA OUTRO ALUNO', 'periodo' => '2026.2',
        'nota_av1' => 9, 'nota_av2' => 9, 'nota_av3' => 0, 'faltas' => 0,
    ]);
    $consulta = app(ConsultasAcademicasTelegram::class)->notas((int) $aluno->id);
    verificar(str_contains($consulta['text'], $disciplina->nome) && str_contains($consulta['text'], '6.00')
        && str_contains($consulta['text'], 'Recuperação'), 'Consulta não retornou a nota em recuperação.');
    verificar(! str_contains($consulta['text'], 'SENTINELA OUTRO ALUNO'), 'Consulta vazou nota de outro aluno.');
    verificar(DB::table('jobs')->where('queue', $fila)->count() === $jobsAntes, 'Consulta/notas geraram job externo.');
    verificar(! DB::table('jobs')->where('queue', $fila)->where(fn ($q) => $q
        ->where('attempts', '!=', 0)->orWhereNotNull('reserved_at'))->exists(), 'Algum job da execução foi consumido.');
    semEnvios();
    echo "OK notas: user_id, Recuperação, média, leitura isolada e confirmação idempotente\n";
    echo "OK integração PostgreSQL: dados sintéticos preservados para inspeção; descarte o banco efêmero.\n";
} catch (Throwable $erro) {
    // Não imprimir SQL, argumentos, credenciais, callback nem conteúdo dos jobs.
    $mensagem = $erro instanceof FalhaIntegracao ? $erro->getMessage() : get_class($erro).' (detalhes omitidos)';
    fwrite(STDERR, 'FALHA: '.$mensagem.PHP_EOL);
    exit(1);
}
