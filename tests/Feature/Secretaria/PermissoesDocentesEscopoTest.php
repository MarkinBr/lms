<?php

namespace Tests\Feature\Secretaria;

use App\Filament\Professor\Resources\AulaResource;
use App\Filament\Professor\Resources\AulaResource\Pages\CreateAula;
use App\Filament\Professor\Resources\AulaResource\Pages\EditAula;
use App\Filament\Professor\Resources\AulaResource\Pages\ListAulas;
use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\InstrumentoAvaliacao;
use App\Models\Modulo;
use App\Models\ProfessorAtribuicao;
use App\Models\Turma;
use App\Models\User;
use App\Services\GestaoAcademica\PermissoesDocentes;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermissoesDocentesEscopoTest extends TestCase
{
    use RefreshDatabase;

    private User $professor;

    private Disciplina $disciplina;

    private Modulo $modulo;

    private Turma $turma;

    private Aula $aula;

    private PermissoesDocentes $acl;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => str_repeat('t', 32)]);
        Filament::setCurrentPanel(Filament::getPanel('professor'));
        Filament::bootCurrentPanel();
        $this->professor = User::factory()->create(['nivel' => 'professor', 'ativo' => true]);
        $curso = Curso::query()->create(['titulo' => 'Curso', 'ativo' => true]);
        $this->modulo = Modulo::query()->create(['curso_id' => $curso->id, 'titulo' => 'Módulo', 'ordem' => 1]);
        $this->disciplina = Disciplina::query()->create([
            'curso_id' => $curso->id, 'modulo_id' => $this->modulo->id,
            'codigo' => 'BIB', 'nome' => 'Bíblia', 'carga_horaria' => 40, 'ordem' => 1, 'ativa' => true,
        ]);
        $this->turma = $this->novaTurma('Turma A');
        $this->aula = Aula::query()->create([
            'disciplina_id' => $this->disciplina->id, 'modulo_id' => $this->modulo->id,
            'titulo' => 'Aula original', 'ordem' => 1,
        ]);
        $this->acl = app(PermissoesDocentes::class);
        $this->actingAs($this->professor);
    }

    private function novaTurma(string $nome): Turma
    {
        return Turma::query()->create([
            'curso_id' => $this->disciplina->curso_id, 'disciplina_id' => $this->disciplina->id,
            'nome' => $nome, 'ano_letivo' => 2026, 'status' => 'planejada',
            'valor_total_curso' => 1000, 'qtd_parcelas' => 10,
            'data_inicio' => '2026-01-01', 'data_fim' => '2026-12-31',
        ]);
    }

    private function atribuir(string $escopo, array $campos = []): ProfessorAtribuicao
    {
        return ProfessorAtribuicao::query()->create(array_merge([
            'turma_id' => $this->turma->id, 'professor_id' => $this->professor->id,
            'escopo' => $escopo, 'permissoes' => ['visualizar', 'editar_conteudo'], 'ativa' => true,
        ], $campos));
    }

    public function test_atribuicao_local_nao_edita_nem_cria_conteudo_compartilhado(): void
    {
        $this->atribuir('turma');
        $this->novaTurma('Turma B');

        $this->assertFalse($this->acl->podeEditarAula($this->professor->id, $this->aula));
        $this->assertFalse($this->acl->podeCriarAula($this->professor->id, $this->disciplina, $this->modulo->id));
        Livewire::test(EditAula::class, ['record' => $this->aula->id])->assertForbidden();
        $this->assertSame('Aula original', $this->aula->fresh()->titulo);
    }

    public function test_responsavel_do_curso_preserva_edicao_compartilhada(): void
    {
        $this->novaTurma('Turma B');
        Curso::query()->whereKey($this->disciplina->curso_id)->update(['instrutor_id' => $this->professor->id]);
        $this->assertTrue($this->acl->podeEditarAula($this->professor->id, $this->aula));
        $this->assertTrue($this->acl->podeCriarAula($this->professor->id, $this->disciplina, $this->modulo->id));
    }

    public function test_modulo_autorizado_cria_pelo_formulario(): void
    {
        $this->atribuir('modulo', ['disciplina_id' => $this->disciplina->id, 'modulo_id' => $this->modulo->id]);
        Livewire::test(CreateAula::class)->fillForm([
            'disciplina_id' => $this->disciplina->id, 'modulo_id' => $this->modulo->id,
            'titulo' => 'Nova aula autorizada', 'ordem' => 2,
        ])->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('aulas', [
            'disciplina_id' => $this->disciplina->id, 'modulo_id' => $this->modulo->id, 'titulo' => 'Nova aula autorizada',
        ]);
    }

    public function test_compartilhamento_posterior_a_abertura_do_editor_bloqueia_gravacao(): void
    {
        $this->atribuir('turma');
        $editor = Livewire::test(EditAula::class, ['record' => $this->aula->id]);
        $this->novaTurma('Turma B');
        $editor->fillForm(['titulo' => 'Alteração indevida'])->call('save')->assertStatus(404);
        $this->assertSame('Aula original', $this->aula->fresh()->titulo);
    }

    public function test_modulo_da_avaliacao_e_derivado_do_registro_e_nao_do_chamador(): void
    {
        $this->atribuir('modulo', ['disciplina_id' => $this->disciplina->id, 'modulo_id' => $this->modulo->id]);
        $outro = Modulo::query()->create(['curso_id' => $this->disciplina->curso_id, 'titulo' => 'Outro', 'ordem' => 2]);
        $this->disciplina->modulos()->attach($outro);
        $aula = Aula::query()->create([
            'disciplina_id' => $this->disciplina->id, 'modulo_id' => $outro->id, 'titulo' => 'Fora do escopo', 'ordem' => 1,
        ]);
        $prova = InstrumentoAvaliacao::query()->create([
            'disciplina_id' => $this->disciplina->id, 'aula_id' => $aula->id, 'tipo' => 'prova', 'titulo' => 'Prova', 'status' => 'rascunho',
        ]);
        $this->assertFalse($this->acl->permite($this->professor->id, $this->turma->id, 'editar_conteudo',
            $this->disciplina->id, $this->modulo->id, null, $prova->id));
        $this->assertFalse($this->acl->permite($this->professor->id, $this->turma->id, 'editar_conteudo',
            $this->disciplina->id, $this->modulo->id, $this->aula->id, $prova->id));
    }

    public function test_aula_especifica_edita_pelo_formulario_sem_ganhar_criacao_ou_duplicacao(): void
    {
        $this->atribuir('aula', [
            'disciplina_id' => $this->disciplina->id, 'modulo_id' => $this->modulo->id, 'aula_id' => $this->aula->id,
        ]);
        Livewire::test(EditAula::class, ['record' => $this->aula->id])
            ->fillForm(['titulo' => 'Conteúdo autorizado'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Conteúdo autorizado', $this->aula->fresh()->titulo);
        $this->assertFalse(AulaResource::canCreate());
        Livewire::test(ListAulas::class)->assertTableActionHidden('duplicar', $this->aula);
        $this->assertSame(1, Aula::query()->count());
    }

    public function test_visualizacao_nao_libera_exclusao_em_lote(): void
    {
        $this->atribuir('turma', ['permissoes' => ['visualizar']]);
        Livewire::test(ListAulas::class)->assertTableBulkActionDoesNotExist('delete');
        $this->assertFalse(AulaResource::canDelete($this->aula));
        $this->assertDatabaseHas('aulas', ['id' => $this->aula->id]);
    }

    public function test_formulario_rejeita_modulo_fora_da_atribuicao_na_criacao_e_edicao(): void
    {
        $this->atribuir('modulo', ['disciplina_id' => $this->disciplina->id, 'modulo_id' => $this->modulo->id]);
        $outro = Modulo::query()->create(['curso_id' => $this->disciplina->curso_id, 'titulo' => 'Outro', 'ordem' => 2]);
        $this->disciplina->modulos()->attach($outro);
        Livewire::test(CreateAula::class)->fillForm([
            'disciplina_id' => $this->disciplina->id, 'modulo_id' => $outro->id,
            'titulo' => 'Forjada', 'ordem' => 1,
        ])->call('create')->assertHasFormErrors();
        Livewire::test(EditAula::class, ['record' => $this->aula->id])
            ->fillForm(['modulo_id' => $outro->id, 'titulo' => 'Forjada'])
            ->call('save')->assertHasFormErrors();
        $this->assertSame(1, Aula::query()->count());
        $this->assertSame($this->modulo->id, $this->aula->fresh()->modulo_id);
        $this->assertSame('Aula original', $this->aula->fresh()->titulo);
    }

    public function test_vinculo_em_memoria_nao_substitui_aula_persistida_na_autorizacao(): void
    {
        $this->atribuir('modulo', ['disciplina_id' => $this->disciplina->id, 'modulo_id' => $this->modulo->id]);
        $outro = Modulo::query()->create(['curso_id' => $this->disciplina->curso_id, 'titulo' => 'Outro', 'ordem' => 2]);
        $this->disciplina->modulos()->attach($outro);
        $aula = Aula::query()->create([
            'disciplina_id' => $this->disciplina->id, 'modulo_id' => $outro->id, 'titulo' => 'Restrita', 'ordem' => 1,
        ]);
        $aula->modulo_id = $this->modulo->id;
        $this->assertFalse($this->acl->podeEditarAula($this->professor->id, $aula));
    }

    public function test_atribuicao_da_aula_nao_sobrevive_a_movimento_para_outro_modulo(): void
    {
        $atribuicao = $this->atribuir('aula', [
            'disciplina_id' => $this->disciplina->id, 'modulo_id' => $this->modulo->id, 'aula_id' => $this->aula->id,
        ]);
        $outro = Modulo::query()->create(['curso_id' => $this->disciplina->curso_id, 'titulo' => 'Outro', 'ordem' => 2]);
        $this->disciplina->modulos()->attach($outro);
        $this->aula->update(['modulo_id' => $outro->id]);
        $this->assertFalse($atribuicao->permite('editar_conteudo', $this->disciplina->id, $outro->id, $this->aula->id));
        $this->assertFalse($this->acl->podeEditarAula($this->professor->id, $this->aula));
        $this->assertFalse($this->acl->filtrarAulasVisiveis(Aula::query(), $this->professor->id)->exists());
    }
}
