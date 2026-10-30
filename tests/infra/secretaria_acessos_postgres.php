<?php

/**
 * Exige PostgreSQL descartável já migrado, sem workers de fila.
 * APP_ENV=testing DB_CONNECTION=pgsql DB_DATABASE=cetsadp_telegram_ci \
 *   php tests/infra/secretaria_acessos_postgres.php
 * Não migra nem limpa tabelas: deixa somente fixtures sintéticas para inspeção.
 * Compatível com composer --no-dev, sem Faker, PHPUnit, Mockery ou Tests\TestCase.
 */

namespace Tests\Infra\SecretariaAcessosPostgres;

use App\Models\User;
use App\Services\Acessos\AtendimentoAluno;
use App\Services\Acessos\CadastroAluno;
use App\Services\Acessos\CredenciaisAluno;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

// Guardar antes do autoload/bootstrap, inclusive em cada processo filho.
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'pgsql'
    || getenv('DB_DATABASE') !== 'cetsadp_telegram_ci') {
    fwrite(STDERR, "Bloqueado: exige APP_ENV=testing, DB_CONNECTION=pgsql e DB_DATABASE=cetsadp_telegram_ci.\n");
    exit(2);
}

$worker = ($argv[1] ?? '') === 'worker';
$ambiente = [
    'APP_CONFIG_CACHE' => 'bootstrap/cache/secretaria-acessos-ci-config-inexistente.php',
    'APP_URL' => 'https://example.test', 'APP_DEBUG' => 'false', 'DB_URL' => '',
    'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array', 'LOG_CHANNEL' => 'null', 'BCRYPT_ROUNDS' => '4',
    'TELEGRAM_ENABLED' => 'false',
];
if (! $worker) {
    $ambiente['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
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

    // Leituras dos fakes funcionam sem as assertions que dependem de PHPUnit.
    verificar($tentativasHttp === 0 && Http::recorded()->isEmpty(), 'Houve tentativa de HTTP externo.');
    verificar(Mail::sent(Mailable::class)->isEmpty() && Mail::queued(Mailable::class)->isEmpty(),
        'Houve tentativa de enviar ou enfileirar e-mail.');
    verificar(Notification::sentNotifications() === [], 'Houve tentativa de notificação externa.');
    verificar(Queue::pushedJobs() === [], 'Cadastro ou credencial disparou job inesperado.');
}

function aguardar(callable $pronto, string $erro): void
{
    $inicio = microtime(true);
    while (! $pronto()) {
        verificar(microtime(true) - $inicio < 15, $erro);
        usleep(25000);
        clearstatcache();
    }
}

function rejeitar(callable $operacao, string $campo): void
{
    try {
        $operacao();
    } catch (ValidationException $erro) {
        verificar(array_key_exists($campo, $erro->errors()), 'Rejeição ocorreu pelo motivo errado.');

        return;
    }
    throw new FalhaIntegracao('Operação inválida foi aceita.');
}

/** Ambos devem esperar o lock do aluno ou da reserva de e-mail antes da liberação. */
function concorrer(User $aluno, array $propostas, ?string $emailReserva = null): array
{
    verificar(count($propostas) === 2, 'A disputa exige dois workers.');
    $diretorio = sys_get_temp_dir().'/secretaria-acessos-pg-'.bin2hex(random_bytes(12));
    verificar(mkdir($diretorio, 0700), 'Não foi possível criar a barreira temporária.');
    $processos = [];
    $nomes = [];
    $resultados = [];

    try {
        DB::beginTransaction();
        if ($emailReserva === null) {
            verificar(User::whereKey($aluno->id)->lockForUpdate()->first() !== null, 'Fixture do aluno ausente.');
        } else {
            AtendimentoAluno::reservarEmail($emailReserva);
        }
        foreach ($propostas as $indice => $proposta) {
            $nomes[] = 'acessos-'.basename($diretorio).'-'.$indice;
            $processo = new Process([
                PHP_BINARY, __FILE__, 'worker', $diretorio, (string) $indice, $nomes[$indice],
            ], dirname(__DIR__, 2), timeout: 45);
            // O código temporário fica somente em memória/STDIN, fora de argv e da barreira.
            $processo->setInput(json_encode($proposta + ['aluno_id' => $aluno->id], JSON_THROW_ON_ERROR));
            $processo->start();
            $processos[] = $processo;
        }
        aguardar(function () use ($diretorio, $processos): bool {
            foreach ($processos as $processo) {
                verificar($processo->isRunning(), 'Worker encerrou antes de alcançar a barreira.');
            }

            return is_file($diretorio.'/ready-0') && is_file($diretorio.'/ready-1');
        }, 'Workers não inicializaram dentro do prazo.');
        verificar(touch($diretorio.'/go'), 'Não foi possível liberar a barreira.');
        aguardar(function () use ($nomes, $processos, $emailReserva): bool {
            foreach ($processos as $processo) {
                verificar($processo->isRunning(), 'Worker encerrou antes de disputar o lock.');
            }
            DB::select('SELECT pg_stat_clear_snapshot()');
            $reserva = $emailReserva === null ? '' : " AND EXISTS (SELECT 1 FROM pg_locks
                WHERE pg_locks.pid = pg_stat_activity.pid AND locktype = 'advisory' AND NOT granted)";
            $atividade = DB::selectOne(
                "SELECT COUNT(*) AS bloqueados FROM pg_stat_activity WHERE datname = current_database()
                    AND application_name IN (?, ?) AND state = 'active' AND wait_event_type = 'Lock'".$reserva,
                $nomes,
            );

            return (int) $atividade->bloqueados === 2;
        }, 'PostgreSQL não confirmou dois workers concorrentes aguardando locks.');
        DB::commit();

        foreach ($processos as $processo) {
            verificar($processo->wait() === 0, 'Worker falhou; saída omitida para não revelar dados.');
            verificar($processo->getErrorOutput() === '', 'Worker escreveu diagnóstico inesperado.');
            $resultado = trim($processo->getOutput());
            verificar(in_array($resultado, ['aceito', 'rejeitado'], true), 'Worker não retornou resultado válido.');
            $resultados[] = $resultado;
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
    $ordenados = $resultados;
    sort($ordenados);
    verificar($ordenados === ['aceito', 'rejeitado'], 'A disputa não produziu exatamente um sucesso e uma rejeição.');

    return $resultados;
}

try {
    verificar(! is_file(dirname(__DIR__, 2).'/'.$ambiente['APP_CONFIG_CACHE'])
        && ! is_file(dirname(__DIR__, 2).'/.env.secretaria-acessos-ci-inexistente'),
        'Arquivos reservados ao isolamento já existem.');
    require dirname(__DIR__, 2).'/vendor/autoload.php';
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $app->loadEnvironmentFrom('.env.secretaria-acessos-ci-inexistente');
    $app->make(Kernel::class)->bootstrap();

    // Conferir configuração efetiva antes da primeira consulta e o servidor conectado depois.
    verificar(app()->environment('testing') && config('database.default') === 'pgsql'
        && config('database.connections.pgsql.driver') === 'pgsql'
        && config('database.connections.pgsql.database') === 'cetsadp_telegram_ci'
        && empty(config('database.connections.pgsql.url')), 'Configuração efetiva fora do banco descartável.');
    verificar(DB::getDatabaseName() === 'cetsadp_telegram_ci'
        && DB::selectOne('SELECT current_database() AS nome')->nome === 'cetsadp_telegram_ci',
        'Servidor conectado a outro banco.');
    DB::statement("SET lock_timeout = '25s'");
    DB::statement("SET statement_timeout = '35s'");
    Http::swap(new Factory);
    Http::preventStrayRequests();
    $tentativasHttp = 0;
    Http::fake(function () use (&$tentativasHttp) {
        $tentativasHttp++;
        throw new FalhaIntegracao('HTTP proibido nesta integração.');
    });
    Mail::fake();
    Notification::fake();
    Queue::fake();

    if ($worker) {
        verificar(count($argv) === 5, 'Argumentos do worker inválidos.');
        [, , $diretorio, $indice, $nome] = $argv;
        verificar(in_array($indice, ['0', '1'], true)
            && realpath(dirname($diretorio)) === realpath(sys_get_temp_dir())
            && preg_match('/^secretaria-acessos-pg-[0-9a-f]{24}$/D', basename($diretorio)) === 1
            && is_dir($diretorio), 'Barreira do worker inválida.');
        $proposta = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        verificar(is_array($proposta) && is_int($proposta['aluno_id'] ?? null)
            && in_array($proposta['operacao'] ?? '', ['consumir', 'atualizar', 'reservar_email'], true), 'Proposta do worker inválida.');
        $aluno = User::findOrFail($proposta['aluno_id']);
        verificar($aluno->nivel === 'aluno' && str_starts_with($aluno->name, 'Fixture Acessos PG '),
            'Worker só aceita aluno sintético.');
        $ator = null;
        if ($proposta['operacao'] === 'consumir') {
            verificar(is_string($proposta['codigo'] ?? null)
                && preg_match('/^[0-9]{8}$/D', $proposta['codigo']) === 1, 'Código sintético inválido.');
        } else {
            verificar(is_int($proposta['ator_id'] ?? null) && is_string($proposta['estado'] ?? null)
                && preg_match('/^[a-f0-9]{64}$/D', $proposta['estado']) === 1, 'Snapshot sintético inválido.');
            $ator = User::findOrFail($proposta['ator_id']);
            verificar($ator->nivel === 'secretario' && str_starts_with($ator->name, 'Fixture Acessos PG '),
                'Worker só aceita atendente sintético.');
            if ($proposta['operacao'] === 'reservar_email') {
                verificar(is_string($proposta['email'] ?? null)
                    && filter_var(trim($proposta['email']), FILTER_VALIDATE_EMAIL)
                    && str_ends_with(mb_strtolower(trim($proposta['email'])), '@example.test'),
                    'Reserva do worker exige endereço sintético.');
            }
        }
        DB::selectOne("SELECT set_config('application_name', ?, false)", [$nome]);
        verificar(touch($diretorio.'/ready-'.$indice), 'Worker não sinalizou início.');
        aguardar(fn () => is_file($diretorio.'/go'), 'Barreira do worker expirou.');
        if ($proposta['operacao'] === 'consumir') {
            $sessao = app(CredenciaisAluno::class)->consumirTemporaria($aluno, $proposta['codigo']);
            $aceito = $sessao !== null;
            if ($aceito) {
                verificar((int) $sessao['aluno_id'] === $aluno->id
                    && app(CredenciaisAluno::class)->validarSessao($sessao), 'Sessão restrita inválida.');
            }
        } else {
            $aceito = false;
            $reservar = $proposta['operacao'] === 'reservar_email';
            try {
                app(CadastroAluno::class)->atualizar($ator, $aluno,
                    $reservar ? ['email' => $proposta['email']] : ['name' => 'Fixture Acessos PG concorrente '.$indice],
                    $proposta['estado'], ($reservar ? 'Reserva' : 'Conferência').' sintética concorrente '.$indice);
                $aceito = true;
            } catch (ValidationException $erro) {
                verificar(array_key_exists($reservar ? 'email' : 'estado', $erro->errors()),
                    'Edição concorrente rejeitada por outro motivo.');
            }
        }
        verificar(DB::transactionLevel() === 0, 'Worker deixou transação aberta.');
        semEnvios();
        echo $aceito ? "aceito\n" : "rejeitado\n";
        exit(0);
    }

    verificar(($argv[1] ?? '') === '', 'Execute o runner sem argumentos.');
    verificar(DB::transactionLevel() === 0, 'Runner exige commits reais.');
    foreach (['users', 'acesso_auditorias', 'acesso_credenciais', 'matriculas', 'mensalidades', 'password_reset_tokens'] as $tabela) {
        verificar(Schema::hasTable($tabela), 'Aplique as migrations antes da integração: tabela '.$tabela.' ausente.');
    }
    $execucao = bin2hex(random_bytes(8));
    $atores = [];
    foreach ([0, 1] as $indice) {
        $atores[] = User::create([
            'name' => 'Fixture Acessos PG atendente '.$execucao.' '.$indice,
            'email' => 'acessos-'.$execucao.'-'.$indice.'@example.test',
            'password' => Hash::make(Str::random(48)), 'nivel' => 'secretario', 'ativo' => true,
        ]);
    }
    $cadastros = app(CadastroAluno::class);
    $credenciais = app(CredenciaisAluno::class);
    $aluno = $cadastros->criar($atores[0], [
        'name' => 'Fixture Acessos PG aluno '.$execucao, 'whatsapp' => '21000000000',
        'formacao_teologica' => false,
    ], 'Cadastro sintético presencial');
    verificar($aluno->email === null && $aluno->email_verified_at === null && $aluno->whatsapp === '5521000000000'
        && $aluno->nivel === 'aluno' && $aluno->ativo && $aluno->primeiro_acesso_pendente
        && $aluno->formacao_teologica === false
        && preg_match('/^aluno-[a-z0-9]{20}$/D', $aluno->matricula_login) === 1,
        'Cadastro sem e-mail perdeu dados ou elegibilidade.');
    verificar(DB::table('acesso_auditorias')->where('aluno_id', $aluno->id)->where('acao', 'cadastro_criado')->count() === 1,
        'Cadastro não produziu auditoria única.');
    $login = $aluno->matricula_login;
    $aluno = $cadastros->atualizar($atores[0], $aluno, [
        'name' => 'Fixture Acessos PG revisado '.$execucao, 'rg' => 'RG SINTETICO CI',
        'data_nasc' => '1990-01-15', 'genero' => 'F', 'estado_civil' => 'Solteiro(a)',
        'nacionalidade' => 'Brasileira', 'naturalidade_cidade' => 'Cidade sintética', 'naturalidade_estado' => 'SP',
        'email' => 'aluno-'.$execucao.'@example.test', 'telefone' => '21000000001', 'whatsapp' => '21000000002',
        'cep' => '01001000', 'endereco' => 'Rua sintética CI', 'numero' => '0', 'complemento' => 'Fixture',
        'bairro' => 'Bairro sintético', 'cidade' => 'Cidade sintética', 'estado' => 'RJ',
        'escolaridade' => 'medio_completo', 'ano_conclusao' => 2008, 'formacao_teologica' => false,
        'instituicao_anterior' => 'Instituto sintético', 'como_conheceu' => 'indicacao', 'indicacao_nome' => 'Pessoa sintética',
    ], AtendimentoAluno::estado($aluno), 'Revisão sintética de todos os grupos');
    verificar($aluno->name === 'Fixture Acessos PG revisado '.$execucao && $aluno->rg === 'RG SINTETICO CI'
        && $aluno->data_nasc?->format('Y-m-d') === '1990-01-15' && $aluno->genero === 'F'
        && $aluno->estado_civil === 'Solteiro(a)' && $aluno->naturalidade_estado === 'SP'
        && $aluno->telefone === '21000000001' && $aluno->whatsapp === '5521000000002'
        && $aluno->endereco === 'Rua sintética CI' && $aluno->estado === 'RJ'
        && $aluno->ano_conclusao === 2008 && $aluno->formacao_teologica === false
        && $aluno->instituicao_anterior === 'Instituto sintético' && $aluno->como_conheceu === 'indicacao'
        && $aluno->indicacao_nome === 'Pessoa sintética' && $aluno->matricula_login === $login,
        'Edição perdeu dados cadastrais ou alterou o identificador de login.');
    verificar($aluno->email === null && $aluno->email_verified_at === null
        && $aluno->email_confirmacao_pendente === 'aluno-'.$execucao.'@example.test',
        'Edição confirmou ou substituiu o e-mail sem prova de propriedade.');
    verificar(! DB::table('matriculas')->where('user_id', $aluno->id)->exists()
        && ! DB::table('mensalidades')->join('matriculas', 'matriculas.id', '=', 'mensalidades.matricula_id')
            ->where('matriculas.user_id', $aluno->id)->exists(),
        'Atendimento cadastral criou vínculo ou cobrança acadêmica.');
    echo "OK cadastro e edição PostgreSQL: dados persistidos, e-mail pendente e sem vínculo acadêmico\n";

    // A auditoria falha depois da escrita: o rollback deve desfazer inclusive senha e versão.
    $auditorias = DB::table('acesso_auditorias')->whereIn('ator_id', [$atores[0]->id, $atores[1]->id])->count();
    $nomeRevertido = 'Fixture Acessos PG rollback '.$execucao;
    rejeitar(fn () => $cadastros->criar($atores[0], ['name' => $nomeRevertido], ''), 'motivo');
    verificar(! User::where('name', $nomeRevertido)->exists(), 'Cadastro com auditoria inválida persistiu.');
    $antes = AtendimentoAluno::estado($aluno);
    rejeitar(fn () => $cadastros->atualizar($atores[0], $aluno,
        ['name' => $nomeRevertido, 'whatsapp' => '21000000003'], $antes, ''), 'motivo');
    verificar(AtendimentoAluno::estado($aluno) === $antes, 'Rollback da edição não restaurou o cadastro completo.');
    rejeitar(fn () => $credenciais->emitir($atores[0], $aluno, 'temporaria', 'presencial', ''), 'motivo');
    verificar(AtendimentoAluno::estado($aluno) === $antes
        && ! DB::table('acesso_credenciais')->where('aluno_id', $aluno->id)->exists(),
        'Rollback da emissão não restaurou senha, versão ou credenciais.');
    verificar(DB::table('acesso_auditorias')->whereIn('ator_id', [$atores[0]->id, $atores[1]->id])->count() === $auditorias
        && DB::transactionLevel() === 0, 'Falha deixou auditoria ou transação parcial.');
    echo "OK transações PostgreSQL: falhas de auditoria revertem cadastro, edição e emissão\n";

    $emissao = $credenciais->emitir($atores[0], $aluno, 'temporaria', 'presencial', 'Emissão sintética concorrente');
    $proposta = ['operacao' => 'consumir', 'codigo' => $emissao['segredo']];
    concorrer($aluno, [$proposta, $proposta]);
    $registro = DB::table('acesso_credenciais')->find($emissao['id']);
    verificar($registro->consumida_em !== null && (int) $registro->tentativas === 0
        && $registro->revogada_em === null && $registro->concluida_em === null
        && $credenciais->consumirTemporaria($aluno, $emissao['segredo']) === null,
        'Consumo concorrente não preservou uso único da credencial.');
    verificar(! str_contains(DB::table('acesso_auditorias')->where('aluno_id', $aluno->id)->get()->toJson(), $emissao['segredo']),
        'Código temporário vazou na auditoria.');
    echo "OK credencial PostgreSQL: dois workers com o mesmo código, um consumo e replay recusado\n";

    $estado = AtendimentoAluno::estado($aluno);
    $auditorias = DB::table('acesso_auditorias')->where('aluno_id', $aluno->id)->where('acao', 'cadastro_atualizado')->count();
    $resultados = concorrer($aluno, [
        ['operacao' => 'atualizar', 'ator_id' => $atores[0]->id, 'estado' => $estado],
        ['operacao' => 'atualizar', 'ator_id' => $atores[1]->id, 'estado' => $estado],
    ]);
    $vencedor = array_search('aceito', $resultados, true);
    $ultima = DB::table('acesso_auditorias')->where('aluno_id', $aluno->id)->where('acao', 'cadastro_atualizado')->orderByDesc('id')->first();
    verificar($aluno->fresh()->name === 'Fixture Acessos PG concorrente '.$vencedor
        && (int) $ultima->ator_id === $atores[$vencedor]->id
        && $ultima->motivo === 'Conferência sintética concorrente '.$vencedor
        && DB::table('acesso_auditorias')->where('aluno_id', $aluno->id)->where('acao', 'cadastro_atualizado')->count() === $auditorias + 1,
        'Mesmo snapshot produziu mais de um commit ou auditoria incompatível.');
    echo "OK edição PostgreSQL: dois atendentes com o mesmo snapshot, um commit e uma auditoria\n";

    // Linhas e atendentes distintos: somente o endereço canônico pode serializar esta corrida.
    $emailReserva = 'reserva-compartilhada-'.$execucao.'@example.test';
    $alunosReserva = [];
    $estadosReserva = [];
    $propostasReserva = [];
    foreach ([0, 1] as $indice) {
        $alunosReserva[$indice] = $cadastros->criar($atores[$indice], [
            'name' => 'Fixture Acessos PG reserva '.$execucao.' '.$indice,
            'email' => 'reserva-aluno-'.$execucao.'-'.$indice.'@example.test',
        ], 'Cadastro sintético para disputa de reserva');
        $estadosReserva[$indice] = AtendimentoAluno::estado($alunosReserva[$indice]);
        $propostasReserva[] = [
            'operacao' => 'reservar_email', 'aluno_id' => $alunosReserva[$indice]->id,
            'ator_id' => $atores[$indice]->id, 'estado' => $estadosReserva[$indice],
            'email' => $indice === 0 ? '  '.mb_strtoupper($emailReserva).'  ' : $emailReserva,
        ];
    }
    verificar(! User::whereRaw('LOWER(TRIM(email)) = ?', [$emailReserva])
        ->orWhereRaw('LOWER(TRIM(email_confirmacao_pendente)) = ?', [$emailReserva])->exists(),
        'Endereço sintético já estava ocupado antes da corrida.');
    $resultados = concorrer($alunosReserva[0], $propostasReserva, $emailReserva);
    $vencedor = array_search('aceito', $resultados, true);
    $perdedor = 1 - $vencedor;
    $idsReserva = [$alunosReserva[0]->id, $alunosReserva[1]->id];
    $auditoriaReserva = DB::table('acesso_auditorias')->whereIn('aluno_id', $idsReserva)
        ->where('acao', 'cadastro_atualizado')->get();
    verificar(User::whereRaw('LOWER(TRIM(email_confirmacao_pendente)) = ?', [$emailReserva])->count() === 1
        && ! User::whereRaw('LOWER(TRIM(email)) = ?', [$emailReserva])->exists()
        && $alunosReserva[$vencedor]->fresh()->email_confirmacao_pendente === $emailReserva
        && $alunosReserva[$vencedor]->fresh()->email === 'reserva-aluno-'.$execucao.'-'.$vencedor.'@example.test'
        && $alunosReserva[$vencedor]->fresh()->email_verified_at === null
        && AtendimentoAluno::estado($alunosReserva[$perdedor]) === $estadosReserva[$perdedor],
        'Reserva concorrente duplicou endereço, confirmou e-mail ou alterou o cadastro perdedor.');
    verificar($auditoriaReserva->count() === 1
        && (int) $auditoriaReserva->first()->ator_id === $atores[$vencedor]->id
        && (int) $auditoriaReserva->first()->aluno_id === $alunosReserva[$vencedor]->id
        && $auditoriaReserva->first()->motivo === 'Reserva sintética concorrente '.$vencedor,
        'Reserva concorrente não produziu exatamente uma edição auditada pelo vencedor.');
    semEnvios();
    echo "OK reserva PostgreSQL: dois alunos distintos, mesmo e-mail canônico, um commit e uma rejeição por email\n";
    echo "OK integração: somente fixtures sintéticas preservadas; descarte o banco efêmero.\n";
} catch (Throwable $erro) {
    // Não imprimir SQL, código temporário, STDIN, configuração ou dados do worker.
    $mensagem = $erro instanceof FalhaIntegracao ? $erro->getMessage() : get_class($erro).' (detalhes omitidos)';
    fwrite(STDERR, 'FALHA: '.$mensagem.PHP_EOL);
    exit(1);
}
