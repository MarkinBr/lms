<?php

namespace Tests\Feature\Secretaria;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Modulo;
use App\Models\ProfessorAtribuicao;
use App\Models\Turma;
use App\Models\User;
use App\Services\GestaoAcademica\PermissoesDocentes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EstruturaAcademicaMigracaoTest extends TestCase
{
    use RefreshDatabase;

    private function disciplina(): Disciplina
    {
        $curso = Curso::query()->create(['titulo' => 'Curso de teste', 'ativo' => true]);
        $modulo = Modulo::query()->create(['curso_id' => $curso->id, 'titulo' => 'Módulo', 'ordem' => 1]);

        return Disciplina::query()->create([
            'curso_id' => $curso->id, 'modulo_id' => $modulo->id,
            'codigo' => 'LEGADO-001', 'nome' => 'Disciplina', 'carga_horaria' => 40, 'ordem' => 1,
        ]);
    }

    private function professor(string $email): User
    {
        return User::query()->create([
            'name' => 'Professor sintético', 'email' => $email,
            'password' => 'senha-de-teste', 'nivel' => 'professor', 'ativo' => true,
        ]);
    }

    private function turma(Disciplina $disciplina, User $professor): Turma
    {
        return Turma::query()->create([
            'curso_id' => $disciplina->curso_id, 'disciplina_id' => $disciplina->id,
            'professor_id' => $professor->id, 'nome' => 'Turma legada', 'ano_letivo' => 2026,
            'valor_total_curso' => 1200, 'qtd_parcelas' => 12, 'status' => 'aberta',
        ]);
    }

    public function test_excluir_disciplina_sem_dependentes_remove_apenas_seus_vinculos_de_modulos(): void
    {
        $disciplina = $this->disciplina();
        $moduloId = $disciplina->modulo_id;

        $this->assertTrue($disciplina->podeSerExcluida());
        $disciplina->delete();

        $this->assertDatabaseMissing('disciplinas', ['id' => $disciplina->id]);
        $this->assertDatabaseMissing('disciplina_modulo', ['disciplina_id' => $disciplina->id]);
        $this->assertDatabaseHas('modulos', ['id' => $moduloId]);
    }

    public function test_mudar_curso_nao_deixa_modulos_do_curso_anterior(): void
    {
        $disciplina = $this->disciplina();
        $cursoAnterior = $disciplina->curso_id;
        $novoCurso = Curso::query()->create(['titulo' => 'Outro curso']);
        $novoModulo = Modulo::query()->create(['curso_id' => $novoCurso->id, 'titulo' => 'Outro módulo', 'ordem' => 1]);

        try {
            $disciplina->update(['curso_id' => $novoCurso->id, 'modulo_id' => $novoModulo->id]);
            $this->fail('A mudança deve rejeitar os módulos ainda vinculados a outro curso.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('curso_id', $exception->errors());
        }

        $this->assertSame($cursoAnterior, $disciplina->fresh()->curso_id);
        $this->assertDatabaseMissing('disciplina_modulo', ['disciplina_id' => $disciplina->id, 'modulo_id' => $novoModulo->id]);
    }

    public function test_trocar_professor_legado_revoga_acesso_anterior_sem_apagar_historico(): void
    {
        $disciplina = $this->disciplina();
        $anterior = $this->professor('anterior@example.invalid');
        $novo = $this->professor('novo@example.invalid');
        $turma = $this->turma($disciplina, $anterior);
        $acl = app(PermissoesDocentes::class);
        $this->assertTrue($acl->permite($anterior->id, $turma->id, 'editar_conteudo', $disciplina->id));

        $turma->update(['professor_id' => $novo->id]);

        $this->assertFalse($acl->permite($anterior->id, $turma->id, 'editar_conteudo', $disciplina->id));
        $this->assertTrue($acl->permite($novo->id, $turma->id, 'editar_conteudo', $disciplina->id));
        $this->assertDatabaseHas('professor_atribuicoes', [
            'turma_id' => $turma->id, 'professor_id' => $anterior->id, 'escopo' => 'turma', 'ativa' => false,
        ]);
    }

    public function test_limpar_professor_legado_nao_revoga_outros_docentes(): void
    {
        $disciplina = $this->disciplina();
        $anterior = $this->professor('anterior@example.invalid');
        $adicional = $this->professor('adicional@example.invalid');
        $turma = $this->turma($disciplina, $anterior);
        $atribuicao = ProfessorAtribuicao::query()->create([
            'turma_id' => $turma->id, 'professor_id' => $adicional->id,
            'escopo' => 'turma', 'permissoes' => ['visualizar'], 'ativa' => true,
        ]);

        $turma->update(['professor_id' => null]);

        $acl = app(PermissoesDocentes::class);
        $this->assertFalse($acl->permite($anterior->id, $turma->id, 'visualizar', $disciplina->id));
        $this->assertTrue($atribuicao->fresh()->ativa);
        $this->assertTrue($acl->permite($adicional->id, $turma->id, 'visualizar', $disciplina->id));
    }

    public function test_salvar_turma_nao_recria_atribuicao_legada_removida(): void
    {
        $disciplina = $this->disciplina();
        $professor = $this->professor('removido@example.invalid');
        $turma = $this->turma($disciplina, $professor);
        $turma->professorAtribuicoes()->delete();

        $turma->update(['nome' => 'Nome atualizado']);

        $this->assertFalse(app(PermissoesDocentes::class)->permite(
            $professor->id, $turma->id, 'editar_conteudo', $disciplina->id
        ));
    }

    public function test_salvar_turma_preserva_restricoes_da_atribuicao_existente(): void
    {
        $disciplina = $this->disciplina();
        $professor = $this->professor('restrito@example.invalid');
        $turma = $this->turma($disciplina, $professor);
        $atribuicao = $turma->professorAtribuicoes()->firstOrFail();
        $atribuicao->update(['ativa' => false, 'permissoes' => ['visualizar']]);

        $turma->update(['nome' => 'Nome atualizado']);

        $this->assertFalse($atribuicao->fresh()->ativa);
        $this->assertSame(['visualizar'], $atribuicao->fresh()->permissoes);
        $this->assertSame(1, $turma->professorAtribuicoes()->count());
    }

    public function test_redesignar_professor_legado_reativa_sem_duplicar_nem_ampliar_permissoes(): void
    {
        $disciplina = $this->disciplina();
        $primeiro = $this->professor('primeiro@example.invalid');
        $segundo = $this->professor('segundo@example.invalid');
        $turma = $this->turma($disciplina, $primeiro);
        $atribuicao = $turma->professorAtribuicoes()->firstOrFail();
        $atribuicao->update(['permissoes' => ['visualizar']]);
        $turma->update(['professor_id' => $segundo->id]);

        $turma->update(['professor_id' => $primeiro->id]);

        $acl = app(PermissoesDocentes::class);
        $this->assertTrue($acl->permite($primeiro->id, $turma->id, 'visualizar', $disciplina->id));
        $this->assertFalse($acl->permite($primeiro->id, $turma->id, 'editar_conteudo', $disciplina->id));
        $this->assertFalse($acl->permite($segundo->id, $turma->id, 'visualizar', $disciplina->id));
        $this->assertSame(['visualizar'], $atribuicao->fresh()->permissoes);
        $this->assertSame(2, $turma->professorAtribuicoes()->count());
    }
}
