<?php

namespace Tests\Feature\Legado;

use App\Filament\Professor\Resources\AulaResource;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportarPortalLegadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.legado' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::purge('legado');
        $schema = Schema::connection('legado');
        foreach ([
            'cursos' => ['id', 'nome', 'slug', 'descricao', 'ativo'],
            'modulos' => ['id', 'curso_id', 'titulo', 'ordem'],
            'alunos' => ['id', 'nome', 'matricula', 'email', 'cpf', 'senha_hash', 'ativo', 'bloqueado'],
            'professores' => ['id', 'nome', 'username', 'email', 'senha_hash', 'ativo', 'curso_id'],
            'aluno_cursos' => ['id', 'aluno_id', 'curso_id', 'matricula', 'ativo', 'matriculado_em'],
            'matriculas' => ['id', 'user_id', 'curso_id', 'numero_matricula', 'status', 'data_matricula'],
            'aula_programacao' => ['id', 'curso_id', 'titulo', 'descricao', 'data_hora', 'duracao_minutos', 'video_id', 'status', 'ordem', 'material_url'],
            'materiais' => ['id', 'nome', 'url', 'tipo', 'is_upload', 'file_path', 'curso_id', 'aula_id'],
            'presence_log' => ['id', 'aluno_id', 'evento', 'senha_plain', 'token'],
        ] as $table => $fields) {
            $schema->create($table, function (Blueprint $t) use ($fields) {
                foreach ($fields as $f) {
                    $t->string($f)->nullable();
                }
            });
        }
        $this->fonte('cursos')->insert(['id' => '3', 'nome' => 'Teologia sintética', 'slug' => 'teologia', 'ativo' => '1']);
        $this->fonte('modulos')->insert(['id' => '7', 'curso_id' => '3', 'titulo' => 'Módulo original', 'ordem' => '1']);
        $this->fonte('alunos')->insert(['id' => 'uuid-aluno-1', 'nome' => 'Aluno sintético', 'matricula' => ' CETS001 ', 'email' => null, 'cpf' => '12345678901', 'senha_hash' => Hash::make('SenhaSintetica#2026'), 'ativo' => '1', 'bloqueado' => '0']);
        $this->fonte('aluno_cursos')->insert(['id' => '11', 'aluno_id' => 'uuid-aluno-1', 'curso_id' => '3', 'matricula' => 'CETS001', 'ativo' => '1', 'matriculado_em' => '2026-01-10']);
        $this->fonte('matriculas')->insert(['id' => '20', 'user_id' => 'uuid-aluno-1', 'curso_id' => '3', 'numero_matricula' => 'CETS001', 'status' => 'Ativa', 'data_matricula' => '2026-01-10']);
        $this->fonte('aula_programacao')->insert(['id' => '2', 'curso_id' => '3', 'titulo' => 'Aula original', 'descricao' => 'Conteúdo original', 'data_hora' => '2026-01-15 10:00:00', 'video_id' => 'S7s27o1xXps', 'status' => 'encerrada', 'ordem' => '1']);
        $this->fonte('materiais')->insert(['id' => '9', 'nome' => 'Apostila', 'url' => 'https://example.test/apostila.pdf', 'tipo' => 'pdf', 'curso_id' => '3', 'aula_id' => '2']);
        $this->fonte('presence_log')->insert(['id' => '4', 'aluno_id' => 'uuid-aluno-1', 'evento' => 'entrada', 'senha_plain' => 'NAO_COPIAR', 'token' => 'NAO_COPIAR']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('legado');
        parent::tearDown();
    }

    private function fonte(string $table): Builder
    {
        return DB::connection('legado')->table($table);
    }

    public function test_previa_nao_grava_destino_nem_origem(): void
    {
        $this->artisan('portal:importar-legado')->assertExitCode(0);
        $this->assertDatabaseCount('legado_mapeamentos', 0);
        $this->assertDatabaseCount('matriculas', 0);
        $this->assertSame(1, $this->fonte('alunos')->count());
    }

    public function test_importa_sem_email_preserva_senha_e_reconcilia_vinculos_sem_duplicar(): void
    {
        $hash = $this->fonte('alunos')->value('senha_hash');
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(0);
        $aluno = User::where('matricula_login', 'cets001')->firstOrFail();
        $this->assertNull($aluno->email);
        $this->assertSame($hash, $aluno->password);
        $this->assertDatabaseCount('matriculas', 1);
        $this->assertDatabaseCount('cursos', 1);
        $this->assertDatabaseCount('aulas', 1);
        $this->assertDatabaseCount('legado_materiais', 1);
        $this->assertDatabaseHas('aulas', ['video_url' => 'https://www.youtube.com/watch?v=S7s27o1xXps']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('matriculas', 1);
        $this->assertDatabaseCount('aulas', 1);
        $this->assertDatabaseCount('legado_registros', 1);
        $this->assertStringNotContainsString('NAO_COPIAR', DB::table('legado_registros')->value('conteudo'));
        $this->assertSame($hash, $this->fonte('alunos')->value('senha_hash'));
    }

    public function test_edicao_local_nao_e_sobrescrita_na_reimportacao(): void
    {
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(0);
        User::where('matricula_login', 'cets001')->update(['name' => 'Nome editado no novo portal']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(2);
        $this->assertDatabaseHas('users', ['name' => 'Nome editado no novo portal']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_colisao_com_conta_existente_nao_vincula_por_email_ou_cpf(): void
    {
        User::factory()->create(['cpf' => '12345678901']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(2);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('matriculas', 0);
    }

    public function test_origem_e_destino_iguais_sao_recusados(): void
    {
        $this->artisan('portal:importar-legado', ['--origem' => config('database.default'), '--executar' => true])->assertExitCode(1);
        $this->assertDatabaseCount('legado_mapeamentos', 0);
    }

    public function test_status_conflitante_nao_concede_acesso_automaticamente(): void
    {
        $this->fonte('matriculas')->update(['status' => 'Cancelada']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(2);
        $this->assertDatabaseCount('matriculas', 0);
    }

    public function test_nao_executa_em_producao(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_material_importado_aparece_somente_no_curso_do_aluno(): void
    {
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(0);
        $aluno = User::where('matricula_login', 'cets001')->firstOrFail();
        $cursoId = DB::table('cursos')->value('id');
        $this->actingAs($aluno)->get(route('aluno.curso.show', $cursoId))
            ->assertOk()->assertSee('Apostila')->assertSee('https://example.test/apostila.pdf');
        $outro = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->actingAs($outro)->get(route('aluno.curso.show', $cursoId))->assertNotFound();
    }

    public function test_material_com_url_ativa_perigosa_e_recusado(): void
    {
        $this->fonte('materiais')->update(['url' => 'javascript:alert(1)']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(2);
        $this->assertDatabaseCount('legado_materiais', 0);
        $this->assertStringNotContainsString('javascript:', DB::table('aulas')->value('materiais'));
    }

    public function test_homologacao_recusa_banco_fora_do_namespace_reservado(): void
    {
        $this->app->detectEnvironment(fn () => 'homologacao');
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_matricula_concluida_importada_permite_consulta_da_aula_e_caderno(): void
    {
        $this->fonte('aluno_cursos')->delete();
        $this->fonte('matriculas')->update(['status' => 'Concluída']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(0);
        $aluno = User::where('matricula_login', 'cets001')->firstOrFail();
        $aulaId = DB::table('aulas')->value('id');
        $cursoId = DB::table('cursos')->value('id');
        $this->actingAs($aluno)->get(route('aluno.curso.show', $cursoId))->assertOk();
        $this->get(route('aluno.aula', $aulaId))->assertOk();
        $this->postJson(route('aluno.aula.caderno', $aulaId), [
            'revision' => 0, 'baseNotes' => '',
            'notebook' => ['version' => 1, 'text' => 'Minha anotação', 'highlights' => [], 'anchors' => []],
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_reimportacao_nao_recria_vinculo_cancelado_quando_nova_origem_aparece(): void
    {
        $this->fonte('matriculas')->delete();
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(0);
        DB::table('matriculas')->update(['status' => 'cancelada']);
        $this->fonte('matriculas')->insert(['id' => '20', 'user_id' => 'uuid-aluno-1', 'curso_id' => '3', 'numero_matricula' => 'CETS001', 'status' => 'Ativa', 'data_matricula' => '2026-01-10']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(2);
        $this->assertDatabaseCount('matriculas', 1);
        $this->assertDatabaseHas('matriculas', ['status' => 'cancelada']);
        $this->assertDatabaseMissing('matriculas', ['status' => 'ativa']);
    }

    public function test_cpf_com_mascara_no_destino_impede_duplicar_identidade(): void
    {
        User::factory()->create(['cpf' => '123.456.789-01']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(2);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('matriculas', 0);
    }

    public function test_historico_remove_credenciais_em_json_aninhado_preservando_conteudo(): void
    {
        Schema::connection('legado')->table('presence_log', fn (Blueprint $t) => $t->text('data')->nullable());
        $this->fonte('presence_log')->update(['data' => json_encode(['mensagem' => 'Conteúdo preservado', 'itens' => [['token' => 'SEGREDO_ANINHADO', 'senha_atual' => 'SEGREDO_ANINHADO', 'minutos' => 12]]])]);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(0);
        $conteudo = DB::table('legado_registros')->value('conteudo');
        $this->assertStringNotContainsString('SEGREDO_ANINHADO', $conteudo);
        $this->assertStringContainsString('Conteúdo preservado', $conteudo);
        $this->assertStringContainsString('minutos', $conteudo);
    }

    public function test_material_de_aula_bloqueada_nao_aparece_no_curso(): void
    {
        $this->fonte('aula_programacao')->update(['status' => 'programada']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(0);
        $aluno = User::where('matricula_login', 'cets001')->firstOrFail();
        $this->actingAs($aluno)->get(route('aluno.curso.show', DB::table('cursos')->value('id')))
            ->assertOk()->assertDontSee('https://example.test/apostila.pdf');
    }

    public function test_professor_importado_preserva_curso_e_nao_ve_curso_de_outro(): void
    {
        $this->fonte('professores')->insert(['id' => '5', 'nome' => 'Professor sintético', 'username' => 'prof-sintetico', 'senha_hash' => Hash::make('SenhaProfessor#2026'), 'ativo' => '1', 'curso_id' => '3']);
        $this->artisan('portal:importar-legado', ['--executar' => true])->assertExitCode(0);
        $professor = User::where('nivel', 'professor')->firstOrFail();
        $this->assertDatabaseHas('cursos', ['instrutor_id' => $professor->id]);
        $this->actingAs($professor);
        $this->assertSame(1, AulaResource::getEloquentQuery()->count());
        $outro = User::factory()->create(['nivel' => 'professor', 'ativo' => true]);
        $this->actingAs($outro);
        $this->assertSame(0, AulaResource::getEloquentQuery()->count());
    }
}
