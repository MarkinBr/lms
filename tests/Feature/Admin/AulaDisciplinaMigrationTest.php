<?php

namespace Tests\Feature\Admin;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AulaDisciplinaMigrationTest extends TestCase
{
    private string $conexaoOriginal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conexaoOriginal = DB::getDefaultConnection();

        config()->set('database.connections.issue57_migration', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('issue57_migration');
        DB::setDefaultConnection('issue57_migration');
    }

    protected function tearDown(): void
    {
        DB::disconnect('issue57_migration');
        DB::purge('issue57_migration');
        DB::setDefaultConnection($this->conexaoOriginal);

        parent::tearDown();
    }

    public function test_preserva_view_existente_quando_todas_as_tabelas_estao_disponiveis(): void
    {
        $this->criarEstruturaMinima();
        $this->criarTabelasExigidasPelaView();
        DB::statement('CREATE VIEW v_curso_estatisticas AS SELECT id AS curso_id FROM cursos');

        $migration = $this->migration();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('aulas', 'disciplina_id'));
        $this->assertTrue($this->viewExiste());
        $this->assertSame([], DB::select('SELECT * FROM v_curso_estatisticas'));

        $migration->down();

        $this->assertFalse(Schema::hasColumn('aulas', 'disciplina_id'));
        $this->assertTrue($this->viewExiste());
        $this->assertSame([], DB::select('SELECT * FROM v_curso_estatisticas'));
    }

    public function test_nao_cria_view_que_nao_existia(): void
    {
        $this->criarEstruturaMinima();

        $migration = $this->migration();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('aulas', 'disciplina_id'));
        $this->assertFalse($this->viewExiste());

        $migration->down();

        $this->assertFalse(Schema::hasColumn('aulas', 'disciplina_id'));
        $this->assertFalse($this->viewExiste());
    }

    public function test_preserva_a_definicao_original_da_view_em_schema_parcial(): void
    {
        $this->criarEstruturaMinima();
        DB::statement('CREATE VIEW v_curso_estatisticas AS SELECT id AS curso_id FROM aulas');

        $this->migration()->up();

        $this->assertTrue(Schema::hasColumn('aulas', 'disciplina_id'));
        $this->assertTrue($this->viewExiste());
        $this->assertSame([], DB::select('SELECT * FROM v_curso_estatisticas'));
    }

    private function criarEstruturaMinima(): void
    {
        Schema::create('disciplinas', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('aulas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('modulo_id')->nullable();
        });
    }

    private function criarTabelasExigidasPelaView(): void
    {
        Schema::create('cursos', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo');
        });

        Schema::create('matriculas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('curso_id')->nullable();
            $table->string('status')->nullable();
        });

        Schema::create('modulos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('curso_id')->nullable();
        });

        Schema::create('curso_avaliacoes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('curso_id')->nullable();
            $table->decimal('nota', 5, 2)->nullable();
        });
    }

    private function viewExiste(): bool
    {
        return DB::table('sqlite_master')
            ->where('type', 'view')
            ->where('name', 'v_curso_estatisticas')
            ->exists();
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_08_24_000001_add_disciplina_id_to_aulas_table.php');
    }
}
