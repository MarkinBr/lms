<?php

namespace Tests\Feature\Secretaria;

use App\Filament\Professor\Resources\AulaAoVivoProfessorResource;
use App\Filament\Secretario\Resources\AulaAoVivoSecretariaResource;
use App\Models\AulaAoVivo;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\VincularMatriculaTurmaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Issue #25 — Organizar aulas híbridas por turma.
 *
 * Segue o padrão de tests/Feature/Secretaria/*Test.php: criação manual dos
 * modelos (sem factories), validações testadas no nível do Model/Policy, e
 * apenas o necessário de HTTP quando agrega valor real.
 */
class AulaAoVivoHibridaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $nivel): User
    {
        return User::query()->create([
            'name' => 'Usuário '.ucfirst($nivel),
            'email' => $nivel.uniqid().'@cetsadp.test',
            'password' => 'segredo',
            'nivel' => $nivel,
            'ativo' => true,
        ]);
    }

    private function curso(string $titulo = 'Teologia'): Curso
    {
        return Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);
    }

    private function turma(Curso $curso, array $override = []): Turma
    {
        return Turma::query()->create(array_merge([
            'curso_id' => $curso->id,
            'nome' => 'Turma teste',
            'ano_letivo' => 2026,
            'valor_total_curso' => 1200,
            'qtd_parcelas' => 12,
            'status' => Turma::STATUS_PLANEJADA,
            'ativa' => true,
        ], $override));
    }

    private function matricula(User $aluno, Curso $curso, string $numero): Matricula
    {
        return Matricula::query()->create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'turma_id' => null,
            'data_matricula' => now()->toDateString(),
            'status' => 'ativo',
            'numero_matricula' => $numero,
            'dia_vencimento' => 10,
        ]);
    }

    private function encontro(Curso $curso, ?Turma $turma, array $override = []): AulaAoVivo
    {
        return AulaAoVivo::create(array_merge([
            'curso_id' => $curso->id,
            'turma_id' => $turma?->id,
            'titulo' => 'Encontro teste',
            'modalidade' => AulaAoVivo::MODALIDADE_ONLINE,
            'data_hora' => now()->addDays(2),
            'link_transmissao' => 'https://meet.google.com/abc-defg-hij',
            'duracao' => 60,
        ], $override));
    }

    // 1. Secretário cria encontro online válido.
    public function test_secretario_cria_encontro_online_valido(): void
    {
        $curso = $this->curso();
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');
        $this->actingAs($secretario);

        $this->assertTrue($secretario->can('create', AulaAoVivo::class));
        $this->assertTrue(AulaAoVivoSecretariaResource::canAccess());

        $aula = AulaAoVivo::create([
            'curso_id' => $curso->id,
            'turma_id' => $turma->id,
            'titulo' => 'Aula inaugural',
            'modalidade' => AulaAoVivo::MODALIDADE_ONLINE,
            'data_hora' => now()->addDays(3),
            'link_transmissao' => 'https://youtube.com/live/abc123',
            'duracao' => 60,
        ]);

        $this->assertDatabaseHas('aulas_ao_vivo', [
            'id' => $aula->id,
            'modalidade' => AulaAoVivo::MODALIDADE_ONLINE,
            'status' => AulaAoVivo::STATUS_AGENDADA,
            'local' => null,
        ]);
    }

    // 2. Secretário cria encontro presencial válido.
    public function test_secretario_cria_encontro_presencial_valido(): void
    {
        $curso = $this->curso();
        $turma = $this->turma($curso);
        $secretario = $this->usuario('secretario');
        $this->actingAs($secretario);

        $aula = AulaAoVivo::create([
            'curso_id' => $curso->id,
            'turma_id' => $turma->id,
            'titulo' => 'Encontro presencial de abertura',
            'modalidade' => AulaAoVivo::MODALIDADE_PRESENCIAL,
            'local' => 'Templo Histórico, Sala 3',
            'data_hora' => now()->addDays(5),
            'duracao' => 90,
        ]);

        $this->assertDatabaseHas('aulas_ao_vivo', [
            'id' => $aula->id,
            'modalidade' => AulaAoVivo::MODALIDADE_PRESENCIAL,
            'local' => 'Templo Histórico, Sala 3',
            'status' => AulaAoVivo::STATUS_AGENDADA,
        ]);
    }

    // 3a. Online sem URL falha.
    public function test_encontro_online_sem_link_transmissao_falha(): void
    {
        $curso = $this->curso();

        $this->expectException(ValidationException::class);

        $this->encontro($curso, null, [
            'modalidade' => AulaAoVivo::MODALIDADE_ONLINE,
            'link_transmissao' => null,
        ]);
    }

    // 3b. Online com local preenchido também falha (regra explícita da Issue #25).
    public function test_encontro_online_com_local_preenchido_falha(): void
    {
        $curso = $this->curso();

        $this->expectException(ValidationException::class);

        $this->encontro($curso, null, [
            'modalidade' => AulaAoVivo::MODALIDADE_ONLINE,
            'local' => 'Sala física, por engano',
        ]);
    }

    // 3c. Presencial sem local falha.
    public function test_encontro_presencial_sem_local_falha(): void
    {
        $curso = $this->curso();

        $this->expectException(ValidationException::class);

        $this->encontro($curso, null, [
            'modalidade' => AulaAoVivo::MODALIDADE_PRESENCIAL,
            'local' => null,
            'link_transmissao' => null,
        ]);
    }

    // 4. Turma vinculada a curso diferente falha.
    public function test_turma_de_curso_diferente_falha(): void
    {
        $cursoA = $this->curso('Teologia');
        $cursoB = $this->curso('Diaconato');
        $turmaB = $this->turma($cursoB);

        $this->expectException(ValidationException::class);

        $this->encontro($cursoA, $turmaB);
    }

    // 5. Aluno vê somente encontros de sua MatriculaTurma ativa.
    public function test_aluno_ve_somente_encontros_de_sua_matricula_turma_ativa(): void
    {
        $curso = $this->curso();
        $turmaDoAluno = $this->turma($curso, ['nome' => 'Turma do aluno']);
        $outraTurma = $this->turma($curso, ['nome' => 'Outra turma']);

        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-HIB-1');
        app(VincularMatriculaTurmaService::class)->executar($matricula, $turmaDoAluno);

        $encontroDaTurma = $this->encontro($curso, $turmaDoAluno, ['titulo' => 'Encontro da turma do aluno']);
        $encontroDeOutraTurma = $this->encontro($curso, $outraTurma, ['titulo' => 'Encontro de outra turma']);

        $this->actingAs($aluno);

        // Nível 1: policy nunca autoriza acesso direto por ID a outra turma.
        $this->assertTrue($aluno->can('view', $encontroDaTurma));
        $this->assertFalse($aluno->can('view', $encontroDeOutraTurma));

        // Nível 2: a mesma consulta usada pelo calendário do aluno
        // (AulaAoVivo::paraAluno) nunca traz o encontro de outra turma,
        // mesmo que o aluno conheça o ID.
        $idsVisiveis = AulaAoVivo::query()->paraAluno($aluno)->pluck('id');

        $this->assertTrue($idsVisiveis->contains($encontroDaTurma->id));
        $this->assertFalse($idsVisiveis->contains($encontroDeOutraTurma->id));
    }

    // 5b. Matrícula-turma inativa (trancada/cancelada) também não dá acesso.
    public function test_aluno_com_matricula_turma_inativa_nao_ve_encontro(): void
    {
        $curso = $this->curso();
        $turma = $this->turma($curso);

        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-HIB-2');
        $vinculo = app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);
        $vinculo->update(['status' => MatriculaTurma::STATUS_TRANCADO]);

        $encontro = $this->encontro($curso, $turma);

        $this->actingAs($aluno);

        $this->assertFalse($aluno->can('view', $encontro));
        $this->assertFalse(AulaAoVivo::query()->paraAluno($aluno)->pluck('id')->contains($encontro->id));
    }

    public function test_aluno_com_matricula_legada_ativa_ve_encontro_da_propria_turma(): void
    {
        $curso = $this->curso();
        $turma = $this->turma($curso);
        $aluno = $this->usuario('aluno');

        Matricula::query()->create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'turma_id' => $turma->id,
            'data_matricula' => now()->toDateString(),
            'status' => 'ativo',
            'numero_matricula' => 'MAT-HIB-LEGADA',
            'dia_vencimento' => 10,
        ]);

        $encontro = $this->encontro($curso, $turma);

        $this->assertTrue(
            AulaAoVivo::query()->paraAluno($aluno)->pluck('id')->contains($encontro->id),
        );
    }

    // 6. Professor não vê nem altera encontro de turma alheia.
    public function test_professor_nao_ve_nem_altera_encontro_de_turma_alheia(): void
    {
        $curso = $this->curso();

        $professor = $this->usuario('professor');
        $outroProfessor = $this->usuario('professor');

        $turmaDoProfessor = $this->turma($curso, ['professor_id' => $professor->id, 'nome' => 'Turma do professor']);
        $turmaDeOutroProfessor = $this->turma($curso, ['professor_id' => $outroProfessor->id, 'nome' => 'Turma de outro professor']);

        $encontroProprio = $this->encontro($curso, $turmaDoProfessor, ['titulo' => 'Encontro próprio']);
        $encontroAlheio = $this->encontro($curso, $turmaDeOutroProfessor, ['titulo' => 'Encontro alheio']);

        $this->actingAs($professor);

        $this->assertTrue($professor->can('view', $encontroProprio));
        $this->assertFalse($professor->can('view', $encontroAlheio));

        // Professor é somente-leitura mesmo para as próprias turmas.
        $this->assertFalse($professor->can('update', $encontroProprio));
        $this->assertFalse($professor->can('delete', $encontroProprio));
        $this->assertFalse($professor->can('update', $encontroAlheio));

        $idsVisiveis = AulaAoVivo::query()->paraProfessor((int) $professor->id)->pluck('id');
        $this->assertTrue($idsVisiveis->contains($encontroProprio->id));
        $this->assertFalse($idsVisiveis->contains($encontroAlheio->id));

        $this->assertTrue(AulaAoVivoProfessorResource::canAccess());
        $this->assertFalse(AulaAoVivoSecretariaResource::canAccess());
    }

    // 7. Cancelar encontro não cria nem remove matrícula, mensalidade, presença ou nota.
    public function test_cancelar_encontro_nao_afeta_matricula_mensalidade_ou_nota(): void
    {
        $curso = $this->curso();
        $turma = $this->turma($curso);
        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-HIB-3');
        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);

        $aula = $this->encontro($curso, $turma, ['titulo' => 'Encontro a ser cancelado']);

        $matriculasAntes = DB::table('matriculas')->count();
        $matriculaTurmaAntes = DB::table('matricula_turma')->count();
        $notasAntes = DB::table('notas')->count();
        // Nome de tabela inferido de Matricula::mensalidades() -> Mensalidade::class.
        $mensalidadesAntes = DB::table('mensalidades')->count();

        $secretario = $this->usuario('secretario');
        $this->actingAs($secretario);

        $this->assertTrue($secretario->can('update', $aula));

        $aula->cancelar();

        // Continua no histórico — não é excluído — e fica claramente identificado.
        $this->assertDatabaseHas('aulas_ao_vivo', [
            'id' => $aula->id,
            'status' => AulaAoVivo::STATUS_CANCELADA,
        ]);
        $this->assertTrue($aula->fresh()->isCancelada());

        $this->assertSame($matriculasAntes, DB::table('matriculas')->count());
        $this->assertSame($matriculaTurmaAntes, DB::table('matricula_turma')->count());
        $this->assertSame($notasAntes, DB::table('notas')->count());
        $this->assertSame($mensalidadesAntes, DB::table('mensalidades')->count());
    }

    // 7b. Encontro cancelado não aparece como "próximo encontro" do aluno.
    public function test_encontro_cancelado_nao_aparece_como_proximo_encontro(): void
    {
        $curso = $this->curso();
        $turma = $this->turma($curso);
        $aluno = $this->usuario('aluno');
        $matricula = $this->matricula($aluno, $curso, 'MAT-HIB-4');
        app(VincularMatriculaTurmaService::class)->executar($matricula, $turma);

        $aula = $this->encontro($curso, $turma, ['titulo' => 'Encontro cancelado']);
        $aula->cancelar();

        $proximos = AulaAoVivo::query()->paraAluno($aluno)->proximas()->pluck('id');

        $this->assertFalse($proximos->contains($aula->id));
        // Mas segue existindo/consultável no histórico (não foi excluído).
        $this->assertDatabaseHas('aulas_ao_vivo', ['id' => $aula->id]);
    }

    // 8. Migration e rollback funcionam em SQLite.
    public function test_migration_e_rollback_funcionam_em_sqlite(): void
    {
        $colunas = ['modalidade', 'local', 'status', 'observacoes'];

        foreach ($colunas as $coluna) {
            $this->assertTrue(Schema::hasColumn('aulas_ao_vivo', $coluna), "Coluna {$coluna} deveria existir após a migration.");
        }

        $migration = require database_path('migrations/2026_08_13_090000_add_campos_hibridos_to_aulas_ao_vivo_table.php');

        $migration->down();

        foreach ($colunas as $coluna) {
            $this->assertFalse(Schema::hasColumn('aulas_ao_vivo', $coluna), "Coluna {$coluna} deveria ter sido removida no rollback.");
        }

        // turma_id é preservado propositalmente no rollback (ver comentário na migration).
        $this->assertTrue(Schema::hasColumn('aulas_ao_vivo', 'turma_id'));

        $migration->up();

        foreach ($colunas as $coluna) {
            $this->assertTrue(Schema::hasColumn('aulas_ao_vivo', $coluna), "Coluna {$coluna} deveria voltar a existir após reaplicar a migration.");
        }
    }
}
