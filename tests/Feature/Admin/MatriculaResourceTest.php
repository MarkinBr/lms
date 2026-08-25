<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CursoResource\Pages\EditCurso;
use App\Filament\Resources\CursoResource\RelationManagers\ModulosRelationManager;
use App\Filament\Resources\MatriculaResource;
use App\Filament\Resources\MatriculaResource\Pages\CreateMatricula;
use App\Filament\Resources\MatriculaResource\Pages\ListMatriculas;
use App\Filament\Resources\ModuloResource;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MatriculaResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'nivel' => 'admin',
            'ativo' => true,
        ]);
    }

    private function aluno(): User
    {
        return User::factory()->create([
            'nivel' => 'aluno',
            'ativo' => true,
        ]);
    }

    private function curso(string $titulo = 'Curso de Teologia'): Curso
    {
        return Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);
    }

    private function turma(Curso $curso, string $nome = 'Turma 2026'): Turma
    {
        return Turma::query()->create([
            'curso_id' => $curso->id,
            'nome' => $nome,
            'ano_letivo' => 2026,
            'valor_total_curso' => 1200,
            'qtd_parcelas' => 12,
            'status' => Turma::STATUS_ABERTA,
            'ativa' => true,
        ]);
    }

    public function test_admin_cria_matricula_com_campos_do_schema_atual(): void
    {
        $aluno = $this->aluno();
        $curso = $this->curso();
        $turma = $this->turma($curso);
        $this->actingAs($this->admin());

        Livewire::test(CreateMatricula::class)
            ->fillForm([
                'user_id' => $aluno->id,
                'curso_id' => $curso->id,
                'turma_id' => $turma->id,
                'numero_matricula' => 'MAT-ADMIN-001',
                'data_matricula' => '2026-08-24',
                'data_validade' => '2027-08-24',
                'dia_vencimento' => 10,
                'status' => Matricula::STATUS_ATIVA,
                'observacoes' => 'Matrícula conferida pela administração.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('matriculas', [
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'turma_id' => $turma->id,
            'numero_matricula' => 'MAT-ADMIN-001',
            'dia_vencimento' => 10,
            'status' => Matricula::STATUS_ATIVA,
            'observacoes' => 'Matrícula conferida pela administração.',
        ]);
    }

    public function test_servidor_rejeita_turma_de_outro_curso(): void
    {
        $aluno = $this->aluno();
        $cursoInformado = $this->curso('Curso informado');
        $turmaIncompativel = $this->turma($this->curso('Outro curso'), 'Turma incompatível');

        try {
            MatriculaResource::prepararDadosAcademicos([
                'user_id' => $aluno->id,
                'curso_id' => $cursoInformado->id,
                'turma_id' => $turmaIncompativel->id,
                'numero_matricula' => 'MAT-CRUZADA-001',
                'data_matricula' => '2026-08-24',
                'dia_vencimento' => 10,
                'status' => Matricula::STATUS_ATIVA,
            ]);

            $this->fail('O vínculo cruzado entre turma e curso deveria ser rejeitado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('turma_id', $exception->errors());
        }
    }

    public function test_servidor_rejeita_aluno_inativo(): void
    {
        $alunoInativo = User::factory()->create([
            'nivel' => 'aluno',
            'ativo' => false,
        ]);
        $curso = $this->curso();
        $turma = $this->turma($curso);

        try {
            MatriculaResource::prepararDadosAcademicos([
                'user_id' => $alunoInativo->id,
                'curso_id' => $curso->id,
                'turma_id' => $turma->id,
                'numero_matricula' => 'MAT-INATIVA-001',
                'data_matricula' => '2026-08-24',
                'dia_vencimento' => 10,
                'status' => Matricula::STATUS_ATIVA,
            ]);

            $this->fail('Um aluno inativo não deveria receber nova matrícula.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('user_id', $exception->errors());
        }
    }

    public function test_status_legados_sao_normalizados_para_os_valores_canonicos(): void
    {
        $this->assertSame(Matricula::STATUS_ATIVA, MatriculaResource::normalizarStatus('Ativo'));
        $this->assertSame(Matricula::STATUS_TRANCADA, MatriculaResource::normalizarStatus('Trancado'));
        $this->assertSame(Matricula::STATUS_CONCLUIDA, MatriculaResource::normalizarStatus('Concluída'));
        $this->assertSame(Matricula::STATUS_CANCELADA, MatriculaResource::normalizarStatus('Cancelado'));
    }

    public function test_listagem_usa_relacoes_reais_e_nao_oferece_exclusao_em_massa(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListMatriculas::class)
            ->assertTableColumnExists('user.name')
            ->assertTableColumnExists('curso.titulo')
            ->assertTableColumnExists('turma.nome')
            ->assertTableColumnDoesNotExist('aluno.nome')
            ->assertTableBulkActionDoesNotExist('delete');
    }

    public function test_modulo_do_curso_exibe_e_gerencia_disciplinas_em_vez_de_aulas(): void
    {
        $curso = $this->curso();
        $modulo = Modulo::query()->create([
            'curso_id' => $curso->id,
            'titulo' => 'Módulo 1',
            'ordem' => 1,
        ]);
        Disciplina::query()->create([
            'curso_id' => $curso->id,
            'modulo_id' => $modulo->id,
            'codigo' => 'TEO-001',
            'nome' => 'Bibliologia',
            'carga_horaria' => 40,
            'ordem' => 1,
            'ativa' => true,
        ]);
        $this->actingAs($this->admin());

        Livewire::test(ModulosRelationManager::class, [
            'ownerRecord' => $curso,
            'pageClass' => EditCurso::class,
        ])
            ->assertCanSeeTableRecords([$modulo])
            ->assertTableColumnExists('disciplinas_count')
            ->assertTableColumnDoesNotExist('aulas_count')
            ->assertTableActionExists('gerenciar_disciplinas', null, $modulo)
            ->assertTableActionHasLabel('gerenciar_disciplinas', 'Disciplinas', $modulo)
            ->assertTableActionHasUrl(
                'gerenciar_disciplinas',
                ModuloResource::getUrl('edit', ['record' => $modulo]),
                $modulo,
            )
            ->assertTableActionDoesNotExist('gerenciar_aulas')
            ->assertTableBulkActionDoesNotExist('delete');
    }
}
