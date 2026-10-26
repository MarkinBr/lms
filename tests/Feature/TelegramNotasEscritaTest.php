<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Nota;
use App\Models\User;
use App\Services\Telegram\ConfirmacoesTelegram;
use App\Services\Telegram\NotasAdministrativasTelegram;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TelegramNotasEscritaTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'migrations/2026_10_26_000004_normalizar_notas_administrativas_telegram.php';

    protected function setUp(): void
    {
        parent::setUp();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake();
        Notification::fake();
    }

    public function test_migration_preserva_legado_e_permite_user_id_e_recuperacao(): void
    {
        $this->assertFileExists(database_path(self::MIGRATION));
        $original = DB::getDefaultConnection();
        config()->set('database.connections.notas_legadas', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('notas_legadas');
        DB::setDefaultConnection('notas_legadas');

        try {
            Schema::create('users', fn (Blueprint $table) => $table->id());
            DB::table('users')->insert([['id' => 1], ['id' => 2]]);
            Schema::create('notas', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('disciplina')->index();
                $table->enum('situacao', ['Cursando', 'Aprovado', 'Reprovado', 'Reprovado por Falta'])->default('Cursando');
                $table->text('observacoes')->nullable();
                $table->timestamps();
            });
            DB::table('notas')->insert([
                ['id' => 10, 'aluno_id' => 1, 'user_id' => null, 'disciplina' => 'Legada',
                    'situacao' => 'Aprovado', 'observacoes' => 'Preservar', 'created_at' => '2024-01-01 00:00:00'],
                ['id' => 11, 'aluno_id' => 1, 'user_id' => 2, 'disciplina' => 'Canônica',
                    'situacao' => 'Cursando', 'observacoes' => null, 'created_at' => '2024-02-01 00:00:00'],
            ]);

            $migration = require database_path(self::MIGRATION);
            $migration->up();
            $migration->up();

            $this->assertSame(1, DB::table('notas')->where('id', 10)->value('user_id'));
            $this->assertSame(2, DB::table('notas')->where('id', 11)->value('user_id'));
            $this->assertSame('Preservar', DB::table('notas')->where('id', 10)->value('observacoes'));
            $this->assertSame('2024-01-01 00:00:00', DB::table('notas')->where('id', 10)->value('created_at'));
            DB::table('notas')->insert(['user_id' => 1, 'disciplina' => 'Nova', 'situacao' => 'Recuperação']);
            $this->assertCount(3, DB::table('notas')->get());
            $this->assertTrue(Schema::hasIndex('notas', ['disciplina']));
            $this->assertCount(2, Schema::getForeignKeys('notas'));
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
            $migration->down();
            $this->assertSame('Recuperação', DB::table('notas')->where('disciplina', 'Nova')->value('situacao'));
        } finally {
            DB::purge('notas_legadas');
            DB::setDefaultConnection($original);
        }
    }

    private function ambiente(): array
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $admin->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $admin->id, 'chat_id' => '707', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $curso = Curso::create(['titulo' => 'Teologia', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Primeiro', 'ordem' => 1]);
        $disciplina = Disciplina::create([
            'curso_id' => $curso->id, 'modulo_id' => $modulo->id, 'codigo' => 'TG-1',
            'nome' => 'Hermenêutica', 'carga_horaria' => 40, 'ordem' => 1, 'ativa' => true,
        ]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $matricula = Matricula::create([
            'user_id' => $aluno->id, 'curso_id' => $curso->id, 'status' => 'ativa',
            'data_matricula' => now()->toDateString(), 'numero_matricula' => 'TG-001', 'dia_vencimento' => 10,
        ]);

        return [$admin, $aluno, $disciplina, $matricula];
    }

    private function prepararLancamento(User $aluno, Disciplina $disciplina, string $avaliacoes = '6 | 6 | 0 | 0', int $updateId = 2): array
    {
        return app(NotasAdministrativasTelegram::class)->preparar(
            '707', '/lancarnota', "{$aluno->id} | {$disciplina->id} | 2026.2 | {$avaliacoes}", $updateId
        );
    }

    private function proposta(): array
    {
        $linha = DB::table('telegram_confirmacoes')->latest('id')->first();
        $publicacao = DB::table('telegram_publicacoes')->where('confirmacao_id', $linha->id)->first();

        return [$linha->acao, json_decode(Crypt::decryptString($publicacao->dados_criptografados), true, 512, JSON_THROW_ON_ERROR)];
    }

    private function executar(User $admin): ?Nota
    {
        [$acao, $dados] = $this->proposta();

        return DB::transaction(fn () => app(NotasAdministrativasTelegram::class)->executar($acao, $dados, $admin));
    }

    public function test_previa_nao_escreve_e_execucao_lanca_media_e_recuperacao_sem_http(): void
    {
        [$admin, $aluno, $disciplina] = $this->ambiente();
        $previa = $this->prepararLancamento($aluno, $disciplina);
        $this->assertArrayHasKey('reply_markup', $previa);
        $this->assertStringContainsString('Hermenêutica', $previa['text']);
        $this->assertStringContainsString('2026.2', $previa['text']);
        $this->assertStringContainsString('6', $previa['text']);
        $this->assertDatabaseCount('notas', 0);

        $nota = $this->executar($admin);
        $this->assertInstanceOf(Nota::class, $nota);
        $this->assertSame('6.00', $nota->media_final);
        $this->assertSame('Recuperação', $nota->situacao);
        $this->assertSame($aluno->id, $nota->user_id);
        $this->assertSame('Hermenêutica', $nota->disciplina);
        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    #[DataProvider('notasInvalidas')]
    public function test_rejeita_limites_e_formatos_sem_preparar_confirmacao(string $avaliacoes): void
    {
        [, $aluno, $disciplina] = $this->ambiente();
        $previa = $this->prepararLancamento($aluno, $disciplina, $avaliacoes);
        $this->assertArrayNotHasKey('reply_markup', $previa);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        $this->assertDatabaseCount('notas', 0);
    }

    public static function notasInvalidas(): array
    {
        return [
            ['-1 | 7 | 0 | 0'], ['10.01 | 7 | 0 | 0'], ['7 | 11 | 0 | 0'],
            ['7 | 7 | -1 | 0'], ['7 | 7 | 10.01 | 0'], ['7 | 7 | 0 | -1'],
            ['7 | 7 | 0 | 1.5'], ['7 | 7 | 0 | 2147483648'], ['7e0 | 7 | 0 | 0'],
            ['7.001 | 7 | 0 | 0'], ['NaN | 7 | 0 | 0'], ['7 | 7 | 0 | 0 | extra'],
        ];
    }

    public function test_nao_sobrescreve_duplicata_nem_propostas_concorrentes(): void
    {
        [$admin, $aluno, $disciplina] = $this->ambiente();
        $this->prepararLancamento($aluno, $disciplina, '0 | 10 | 0 | 0');
        [$acao, $dados] = $this->proposta();
        $this->prepararLancamento($aluno, $disciplina, '10 | 10 | 0 | 0', 3);
        $nota = DB::transaction(fn () => app(NotasAdministrativasTelegram::class)->executar($acao, $dados, $admin));
        $this->assertSame('5.00', $nota->media_final);
        $this->assertNull($this->executar($admin));
        $previa = $this->prepararLancamento($aluno, $disciplina, '10 | 10 | 0 | 0', 4);
        $this->assertArrayNotHasKey('reply_markup', $previa);
        $this->assertDatabaseCount('notas', 1);
        $this->assertSame('5.00', $nota->fresh()->media_final);
    }

    #[DataProvider('vinculosInvalidos')]
    public function test_rejeita_aluno_ou_matricula_inelegivel(string $recurso, array $mudancas): void
    {
        [, $aluno, $disciplina, $matricula] = $this->ambiente();
        $registro = match ($recurso) {
            'aluno' => $aluno, 'matricula' => $matricula, 'disciplina' => $disciplina,
            'curso' => $disciplina->curso,
        };
        $registro->forceFill($mudancas)->save();
        $previa = $this->prepararLancamento($aluno, $disciplina);
        $this->assertArrayNotHasKey('reply_markup', $previa);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
    }

    public static function vinculosInvalidos(): array
    {
        return [
            ['aluno', ['nivel' => 'professor']], ['aluno', ['ativo' => false]],
            ['matricula', ['status' => 'cancelada']], ['matricula', ['data_validade' => '2020-01-01']],
            ['disciplina', ['ativa' => false]], ['curso', ['ativo' => false]],
        ];
    }

    public function test_disciplina_de_outro_curso_nao_e_elegivel(): void
    {
        [, $aluno, $disciplina, $matricula] = $this->ambiente();
        $outro = Curso::create(['titulo' => 'Diaconato', 'ativo' => true]);
        $matricula->update(['curso_id' => $outro->id]);
        $this->assertArrayNotHasKey('reply_markup', $this->prepararLancamento($aluno, $disciplina));
    }

    public function test_corrige_com_snapshot_e_calcula_av3_e_reprovacao_por_faltas(): void
    {
        [$admin, $aluno, $disciplina] = $this->ambiente();
        $this->prepararLancamento($aluno, $disciplina);
        $nota = $this->executar($admin);
        $previa = app(NotasAdministrativasTelegram::class)->preparar('707', '/corrigirnota', "{$nota->id} | 4 | 6 | 8 | 16", 3);
        $this->assertArrayHasKey('reply_markup', $previa);
        $this->assertSame('6.00', $nota->fresh()->media_final);
        $corrigida = $this->executar($admin);
        $this->assertSame($nota->id, $corrigida->id);
        $this->assertSame('8.00', $corrigida->media_final);
        $this->assertSame('Reprovado por Falta', $corrigida->situacao);
        $this->assertDatabaseCount('notas', 1);
        Http::assertNothingSent();
    }

    public function test_snapshot_bloqueia_correcao_apos_edicao_sem_depender_do_timestamp(): void
    {
        [$admin, $aluno, $disciplina] = $this->ambiente();
        $this->prepararLancamento($aluno, $disciplina);
        $nota = $this->executar($admin);
        app(NotasAdministrativasTelegram::class)->preparar('707', '/corrigirnota', "{$nota->id} | 10 | 10 | 0 | 0", 3);
        DB::table('notas')->where('id', $nota->id)->update(['nota_av1' => 4]);
        $this->assertNull($this->executar($admin));
        $this->assertSame('4.00', $nota->fresh()->nota_av1);
    }

    public function test_revalida_matricula_e_snapshot_disciplina_ao_executar(): void
    {
        [$admin, $aluno, $disciplina, $matricula] = $this->ambiente();
        $this->prepararLancamento($aluno, $disciplina);
        $disciplina->update(['nome' => 'Nome alterado']);
        $this->assertNull($this->executar($admin));
        $this->prepararLancamento($aluno, $disciplina, '6 | 6 | 0 | 0', 3);
        $matricula->update(['status' => 'cancelada']);
        $this->assertNull($this->executar($admin));
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_confirmacao_pai_exige_dono_atual_e_nao_escreve_apos_revogacao(): void
    {
        [$admin, $aluno, $disciplina] = $this->ambiente();
        $previa = $this->prepararLancamento($aluno, $disciplina);
        $codigo = substr($previa['reply_markup']['inline_keyboard'][0][0]['callback_data'], strlen('confirmar:'));
        app(TelegramAdministrativo::class)->revogar($admin);

        try {
            app(ConfirmacoesTelegram::class)->decidir('707', 'confirmar', $codigo, 3);
            $this->fail('A confirmação do vínculo revogado deveria ser proibida.');
        } catch (HttpException $erro) {
            $this->assertSame(403, $erro->getStatusCode());
        }
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_confirmacao_pai_executa_uma_vez_e_registra_recurso(): void
    {
        [, $aluno, $disciplina] = $this->ambiente();
        $previa = $this->prepararLancamento($aluno, $disciplina, '10 | 10 | 0 | 15');
        $codigo = substr($previa['reply_markup']['inline_keyboard'][0][0]['callback_data'], strlen('confirmar:'));
        app(ConfirmacoesTelegram::class)->decidir('707', 'confirmar', $codigo, 3);
        app(ConfirmacoesTelegram::class)->decidir('707', 'confirmar', $codigo, 4);
        $this->assertDatabaseCount('notas', 1);
        $this->assertDatabaseHas('notas', ['media_final' => 10, 'situacao' => 'Aprovado', 'faltas' => 15]);
        $this->assertDatabaseHas('telegram_confirmacoes', [
            'acao' => 'lancar_nota', 'status' => 'confirmado', 'recurso_tipo' => 'notas',
        ]);
        Http::assertNothingSent();
    }

    public function test_recusa_correcao_quando_titulo_legado_se_torna_ambiguo(): void
    {
        [$admin, $aluno, $disciplina] = $this->ambiente();
        $this->prepararLancamento($aluno, $disciplina);
        $nota = $this->executar($admin);
        app(NotasAdministrativasTelegram::class)->preparar('707', '/corrigirnota', "{$nota->id} | 10 | 10 | 0 | 0", 3);
        Disciplina::create([
            'curso_id' => $disciplina->curso_id, 'modulo_id' => $disciplina->modulo_id,
            'codigo' => 'TG-2', 'nome' => $disciplina->nome, 'carga_horaria' => 40, 'ordem' => 2, 'ativa' => true,
        ]);

        $this->assertNull($this->executar($admin));
        $this->assertSame('6.00', $nota->fresh()->media_final);
        $previa = app(NotasAdministrativasTelegram::class)->preparar('707', '/corrigirnota', "{$nota->id} | 10 | 10 | 0 | 0", 4);
        $this->assertArrayNotHasKey('reply_markup', $previa);
    }

    public function test_confirmacao_pai_marca_snapshot_alterado_como_obsoleto(): void
    {
        [, $aluno, $disciplina] = $this->ambiente();
        $previa = $this->prepararLancamento($aluno, $disciplina);
        $codigo = substr($previa['reply_markup']['inline_keyboard'][0][0]['callback_data'], strlen('confirmar:'));
        $aluno->update(['ativo' => false]);
        app(ConfirmacoesTelegram::class)->decidir('707', 'confirmar', $codigo, 3);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'lancar_nota', 'status' => 'obsoleto']);
        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
    }

    public function test_preparacao_pai_rejeita_vinculo_que_nao_pertence_ao_dono_configurado(): void
    {
        [, $aluno, $disciplina] = $this->ambiente();
        $outro = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $outro->id);
        try {
            $this->prepararLancamento($aluno, $disciplina);
            $this->fail('A preparação deveria exigir o dono configurado.');
        } catch (HttpException $erro) {
            $this->assertSame(403, $erro->getStatusCode());
        }
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        $this->assertDatabaseCount('notas', 0);
    }

    #[DataProvider('formatosLegados')]
    public function test_migration_aceita_tambem_schema_so_legado_ou_so_canonico(bool $alunoLegado): void
    {
        $original = DB::getDefaultConnection();
        config()->set('database.connections.notas_variantes', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('notas_variantes');
        DB::setDefaultConnection('notas_variantes');
        try {
            Schema::create('users', fn (Blueprint $table) => $table->id());
            DB::table('users')->insert(['id' => 1]);
            Schema::create('notas', function (Blueprint $table) use ($alunoLegado): void {
                $table->id();
                $table->foreignId($alunoLegado ? 'aluno_id' : 'user_id')->constrained('users')->cascadeOnDelete();
                $table->string('disciplina');
                $table->enum('situacao', ['Cursando', 'Aprovado', 'Reprovado', 'Reprovado por Falta'])->default('Cursando');
            });
            DB::table('notas')->insert([
                $alunoLegado ? 'aluno_id' : 'user_id' => 1, 'disciplina' => 'Antiga', 'situacao' => 'Aprovado',
            ]);
            (require database_path(self::MIGRATION))->up();
            $this->assertSame(1, DB::table('notas')->where('disciplina', 'Antiga')->value('user_id'));
            $this->assertSame('Aprovado', DB::table('notas')->where('disciplina', 'Antiga')->value('situacao'));
            DB::table('notas')->insert(['user_id' => 1, 'disciplina' => 'Nova', 'situacao' => 'Recuperação']);
            $this->assertSame(2, DB::table('notas')->count());
            $this->assertSame($alunoLegado, Schema::hasColumn('notas', 'aluno_id'));
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        } finally {
            DB::purge('notas_variantes');
            DB::setDefaultConnection($original);
        }
    }

    public static function formatosLegados(): array
    {
        return [[true], [false]];
    }
}
