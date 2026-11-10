<?php

// Executar ANTES do migrate geral, em banco isolado ou clone QA pré-migração.
// Não limpa tabelas: fixtures sintéticas ficam no banco para inspeção.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

$banco = getenv('DB_DATABASE');
$permitido = $banco === 'cetsadp_secretaria_ci'
    || ($banco === 'secretaria_pr146_qa' && getenv('ACADEMICO_QA_CLONE') === '1');
if (getenv('APP_ENV') !== 'testing' || ! $permitido) {
    fwrite(STDERR, "Recusado: exige APP_ENV=testing e banco CI, ou clone QA com ACADEMICO_QA_CLONE=1.\n");
    exit(1);
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$verificar = static function (bool $condicao, string $mensagem): void {
    if (! $condicao) {
        throw new RuntimeException($mensagem);
    }
};

try {
    $verificar($app->environment('testing'), 'Configuração efetiva não está em testing.');
    $verificar(DB::connection()->getDriverName() === 'pgsql', 'Exige PostgreSQL.');
    $verificar(DB::selectOne('select current_database() as nome')->nome === $banco,
        'Banco efetivo difere do banco autorizado; confira DB_URL e config cache.');

    // Não depende de PHPUnit, Faker ou observers dos modelos; bloqueia saídas.
    Http::preventStrayRequests();
    Mail::fake();
    Notification::fake();
    Queue::fake();

    $nome = '2026_11_10_000001_flexibilizar_estrutura_academica';
    $arquivo = 'database/migrations/'.$nome.'.php';
    $verificar(! Schema::hasTable('migrations')
        || ! DB::table('migrations')->where('migration', $nome)->exists(),
        'Migração acadêmica já aplicada: use banco CI novo ou clone anterior à migração.');
    foreach (['turma_periodos', 'turma_disciplinas', 'disciplina_modulo', 'professor_atribuicoes'] as $tabela) {
        $verificar(! Schema::hasTable($tabela), 'Esquema acadêmico parcial ou já migrado: '.$tabela);
    }
    $verificar(! Schema::hasTable('turmas') || ! Schema::hasColumn('turmas', 'situacao_academica'),
        'Colunas acadêmicas já existem; não é um baseline anterior à migração.');

    $migrar = static function (array $caminhos) use ($kernel, $verificar): void {
        $codigo = $kernel->call('migrate', ['--path' => $caminhos, '--force' => true]);
        fwrite(STDOUT, $kernel->output());
        $verificar($codigo === 0, 'Falha ao executar migrações.');
    };
    $baseline = [];
    foreach (glob($app->databasePath('migrations/*.php')) as $caminho) {
        if (basename($caminho, '.php') !== $nome) {
            $baseline[] = 'database/migrations/'.basename($caminho);
        }
    }
    $verificar(count($baseline) > 0, 'Nenhuma migração baseline encontrada.');
    $migrar($baseline);

    $marca = 'QA-'.bin2hex(random_bytes(6));
    $agora = date('Y-m-d H:i:s');
    $inserir = static fn (string $tabela, array $dados): int => (int) DB::table($tabela)
        ->insertGetId($dados + ['created_at' => $agora, 'updated_at' => $agora]);

    // Usa período inativo para não modificar o período ativo de um clone existente.
    $periodo = $inserir('periodo_letivos', [
        'nome' => $marca, 'data_inicio' => '2026-01-01', 'data_fim' => '2026-12-31', 'ativo' => false,
    ]);
    $curso = $inserir('cursos', ['titulo' => $marca, 'ativo' => true]);
    $modulo = $inserir('modulos', ['curso_id' => $curso, 'titulo' => $marca, 'ordem' => 1]);
    $disciplina = $inserir('disciplinas', [
        'curso_id' => $curso, 'modulo_id' => $modulo, 'codigo' => $marca,
        'nome' => $marca, 'carga_horaria' => 40, 'ordem' => 1, 'ativa' => true,
    ]);
    $professor = $inserir('users', [
        'name' => $marca, 'email' => strtolower($marca).'@example.invalid',
        'password' => password_hash(bin2hex(random_bytes(20)), PASSWORD_BCRYPT),
        'nivel' => 'professor', 'ativo' => true,
    ]);
    $turmas = [];
    foreach (['aberta', 'encerrada', 'cancelada'] as $status) {
        $turmas[$status] = $inserir('turmas', [
            'nome' => $marca.' '.$status, 'curso_id' => $curso, 'disciplina_id' => $disciplina,
            'periodo_letivo_id' => $periodo, 'professor_id' => $professor,
            'codigo' => $marca.'-'.$status, 'status' => $status, 'ano_letivo' => 2026,
            'ativa' => $status === 'aberta', 'modalidade' => 'hibrido', 'capacidade' => 25,
            'valor_total_curso' => 1200, 'qtd_parcelas' => 12,
        ]);
    }

    $legados = [];
    foreach (['periodo_letivos' => [$periodo], 'cursos' => [$curso], 'modulos' => [$modulo],
        'disciplinas' => [$disciplina], 'users' => [$professor], 'turmas' => array_values($turmas)] as $tabela => $ids) {
        $legados[$tabela] = DB::table($tabela)->whereIn('id', $ids)->get();
    }
    $migrar([$arquivo]);

    foreach ($legados as $tabela => $registros) {
        foreach ($registros as $antes) {
            $depois = DB::table($tabela)->where('id', $antes->id)->first();
            $verificar($depois !== null, 'Registro legado ausente em '.$tabela);
            foreach ((array) $antes as $campo => $valor) {
                $verificar($depois->{$campo} === $valor, 'Valor legado alterado: '.$tabela.'.'.$campo);
            }
        }
    }
    $verificar(DB::table('disciplina_modulo')->where('disciplina_id', $disciplina)
        ->where('modulo_id', $modulo)->count() === 1, 'Backfill de disciplina/módulo incorreto.');
    foreach (['aberta' => 'em_andamento', 'encerrada' => 'concluida', 'cancelada' => 'cancelada'] as $status => $situacao) {
        $id = $turmas[$status];
        $turma = DB::table('turmas')->find($id);
        $verificar($turma->situacao_academica === $situacao, 'Situação acadêmica incorreta.');
        $verificar($turma->situacao_inscricoes === ($status === 'aberta' ? 'abertas' : 'nao_iniciadas'),
            'Situação das inscrições incorreta.');
        $verificar((bool) $turma->publicada === ($status === 'aberta'), 'Publicação legada incorreta.');
        $verificar(DB::table('turma_periodos')->where('turma_id', $id)
            ->where('periodo_letivo_id', $periodo)->count() === 1, 'Backfill de período incorreto.');
        $verificar(DB::table('turma_disciplinas')->where('turma_id', $id)
            ->where('disciplina_id', $disciplina)->where('ativa', true)->count() === 1, 'Backfill de disciplina incorreto.');
        $atribuicoes = DB::table('professor_atribuicoes')->where('turma_id', $id)
            ->where('professor_id', $professor)->where('escopo', 'turma')->where('ativa', true)->get();
        $verificar($atribuicoes->count() === 1, 'Backfill de professor incorreto.');
        $verificar(json_decode($atribuicoes->first()->permissoes, true, flags: JSON_THROW_ON_ERROR)
            === ['visualizar', 'editar_conteudo', 'gerenciar_atividades', 'corrigir', 'lancar_notas'],
            'Permissões legadas incorretas.');
    }
    foreach (['A', 'B'] as $sufixo) {
        $inserir('periodo_letivos', [
            'nome' => $marca.'-'.$sufixo, 'data_inicio' => '2027-01-01', 'data_fim' => '2027-12-31', 'ativo' => true,
        ]);
    }
    $verificar(DB::table('periodo_letivos')->whereIn('nome', [$marca.'-A', $marca.'-B'])
        ->where('ativo', true)->count() === 2, 'Múltiplos períodos ativos não foram aceitos.');
    // Repetir migrate deve ser no-op, preservando o backfill já validado.
    $totalAtribuicoes = DB::table('professor_atribuicoes')->count();
    $migrar([$arquivo]);
    $verificar(DB::table('professor_atribuicoes')->count() === $totalAtribuicoes, 'Retry duplicou atribuições.');
    fwrite(STDOUT, "OK: legado preservado, pivôs, permissões e períodos ativos validados em {$banco}; fixtures {$marca}.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'FALHA: '.$exception->getMessage()."\n");
    exit(1);
}
