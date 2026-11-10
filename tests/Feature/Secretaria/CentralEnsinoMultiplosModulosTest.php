<?php

namespace Tests\Feature\Secretaria;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralEnsinoMultiplosModulosTest extends TestCase
{
    use RefreshDatabase;

    private function prepararDisciplina(): array
    {
        $this->actingAs(User::factory()->create(['nivel' => 'secretario', 'ativo' => true]));
        $curso = Curso::create(['titulo' => 'Teologia', 'ativo' => true]);
        $principal = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Principal', 'ordem' => 1]);
        $segundo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Aprofundamento', 'ordem' => 2]);
        $disciplina = Disciplina::create([
            'curso_id' => $curso->id, 'modulo_id' => $principal->id,
            'nome' => 'Bibliologia', 'codigo' => 'BIB-1', 'carga_horaria' => 40, 'ordem' => 1, 'ativa' => true,
        ]);
        $disciplina->modulos()->syncWithoutDetaching([$principal->id, $segundo->id]);

        return [$disciplina, $principal, $segundo];
    }

    private function dados(Disciplina $disciplina): array
    {
        return [
            'disciplina_id' => $disciplina->id, 'titulo' => 'Aula da disciplina',
            'conteudo_texto' => 'Conteúdo revisado.', 'ordem' => 1,
        ];
    }

    public function test_central_salva_modulo_secundario_e_preserva_na_edicao_legada(): void
    {
        [$disciplina, , $segundo] = $this->prepararDisciplina();
        $this->post(route('secretaria.ensino.aulas.salvar'), $this->dados($disciplina) + [
            'modulo_id' => $segundo->id,
        ])->assertSessionHasNoErrors();
        $aula = Aula::query()->sole();
        $this->assertSame($segundo->id, $aula->modulo_id);
        $this->get(route('secretaria.ensino.aula', $aula))
            ->assertOk()->assertSee('Módulo da aula')->assertSee('Aprofundamento');
        $this->put(route('secretaria.ensino.aulas.atualizar', $aula), $this->dados($disciplina))
            ->assertSessionHasNoErrors();
        $this->assertSame($segundo->id, $aula->fresh()->modulo_id);
    }

    public function test_central_recusa_modulo_do_curso_que_nao_esta_na_disciplina(): void
    {
        [$disciplina] = $this->prepararDisciplina();
        $alheio = Modulo::create(['curso_id' => $disciplina->curso_id, 'titulo' => 'Outra disciplina', 'ordem' => 3]);
        $this->post(route('secretaria.ensino.aulas.salvar'), $this->dados($disciplina) + [
            'modulo_id' => $alheio->id,
        ])->assertSessionHasErrors('modulo_id');
        $this->assertDatabaseCount('aulas', 0);
    }

    public function test_requisicao_legada_usa_modulo_principal_para_nova_aula(): void
    {
        [$disciplina, $principal] = $this->prepararDisciplina();
        $this->post(route('secretaria.ensino.aulas.salvar'), $this->dados($disciplina))
            ->assertSessionHasNoErrors();
        $this->assertSame($principal->id, Aula::query()->sole()->modulo_id);
    }
}
