<?php

namespace Tests\Feature\Professor;

use App\Filament\Professor\Resources\CursoResource\Pages\EditCurso;
use App\Filament\Professor\Resources\CursoResource\Pages\ListCursos;
use App\Filament\Professor\Resources\CursoResource\RelationManagers\ModulosRelationManager;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HierarquiaAcademicaProfessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('professor'));
        Filament::bootCurrentPanel();
    }

    private function professor(): User
    {
        return User::factory()->create([
            'nivel' => 'professor',
            'ativo' => true,
        ]);
    }

    private function cursoDoProfessor(User $professor): Curso
    {
        $curso = Curso::query()->create([
            'titulo' => 'Curso do Professor',
            'ativo' => true,
        ]);

        $curso->forceFill(['instrutor_id' => $professor->id])->saveQuietly();

        return $curso;
    }

    public function test_modulo_nao_oferece_criacao_rapida_de_aula_sem_disciplina(): void
    {
        $professor = $this->professor();
        $curso = $this->cursoDoProfessor($professor);
        Modulo::query()->create([
            'curso_id' => $curso->id,
            'titulo' => 'Módulo 1',
            'ordem' => 1,
        ]);

        $this->actingAs($professor);

        Livewire::test(ModulosRelationManager::class, [
            'ownerRecord' => $curso,
            'pageClass' => EditCurso::class,
        ])
            ->assertTableActionDoesNotExist('adicionar_aula_rapida')
            ->assertTableActionDoesNotExist('gerenciar_aulas')
            ->assertTableColumnExists('disciplinas_count')
            ->assertTableColumnDoesNotExist('aulas_count')
            ->assertTableBulkActionDoesNotExist('delete');
    }

    public function test_listagem_nao_oferece_duplicacao_insegura_de_curso(): void
    {
        $professor = $this->professor();
        $this->cursoDoProfessor($professor);

        $this->actingAs($professor);

        Livewire::test(ListCursos::class)
            ->assertTableActionDoesNotExist('duplicar');
    }
}
