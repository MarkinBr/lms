<?php

namespace Tests\Feature\Secretaria;

use App\Models\Aula;
use App\Models\BancoQuestao;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\InstrumentoAvaliacao;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CentralEnsinoTest extends TestCase
{
    use RefreshDatabase;

    private function equipe(string $nivel = 'secretario'): User
    {
        return User::factory()->create(['nivel' => $nivel, 'ativo' => true]);
    }

    private function catalogo(): array
    {
        $curso = Curso::create(['titulo' => 'Teologia', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Fundamentos', 'ordem' => 1]);
        $disciplina = Disciplina::create([
            'curso_id' => $curso->id, 'modulo_id' => $modulo->id, 'codigo' => 'HIST-1',
            'nome' => 'História da Igreja', 'carga_horaria' => 40, 'ordem' => 1, 'ativa' => true,
        ]);

        return [$curso, $modulo, $disciplina];
    }

    public function test_central_exibe_terceiro_modulo_e_todas_as_abas(): void
    {
        [$curso, $modulo, $disciplina] = $this->catalogo();
        $this->actingAs($this->equipe());

        $this->get(route('secretaria.central'))->assertOk()->assertSee(route('secretaria.ensino.painel'));
        foreach (['painel', 'modulos', 'disciplinas', 'aulas', 'notas', 'frequencias', 'ead', 'avaliacoes', 'questoes'] as $rota) {
            $this->get(route('secretaria.ensino.'.$rota))->assertOk();
        }
        $this->get(route('secretaria.ensino.disciplinas'))
            ->assertSee($disciplina->nome)->assertSee($modulo->titulo);
    }

    public function test_sem_autorizacao_nao_le_conteudos_nem_grava(): void
    {
        [$curso] = $this->catalogo();
        $this->get(route('secretaria.ensino.painel'))->assertRedirect('/secretaria/login');
        $this->actingAs($this->equipe('aluno'))->get(route('secretaria.ensino.questoes'))->assertForbidden();
        $this->post(route('secretaria.ensino.modulos.salvar'), [
            'curso_id' => $curso->id, 'titulo' => 'Indevido', 'ordem' => 2,
        ])->assertForbidden();
    }

    public function test_modulo_e_aula_com_upload_privado_e_download_autorizado(): void
    {
        Storage::fake('local');
        [$curso, $modulo, $disciplina] = $this->catalogo();
        $this->actingAs($this->equipe());
        $this->post(route('secretaria.ensino.modulos.salvar'), [
            'curso_id' => $curso->id, 'titulo' => 'Módulo de extensão', 'ordem' => 2,
        ])->assertRedirect(route('secretaria.ensino.modulos'));

        $resposta = $this->post(route('secretaria.ensino.aulas.salvar'), [
            'disciplina_id' => $disciplina->id,
            'titulo' => 'Encontro introdutório',
            'conteudo_texto' => str_repeat('Conteúdo de referência para a aula. ', 5),
            'video_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
            'transcricao_video' => str_repeat('Uma transcrição validada para explicar a unidade. ', 5),
            'duracao' => 20,
            'ordem' => 1,
            'materiais' => [UploadedFile::fake()->create('apostila.pdf', 64, 'application/pdf')],
        ]);
        $aula = Aula::query()->latest('id')->firstOrFail();
        $resposta->assertRedirect(route('secretaria.ensino.aula', $aula));
        $this->assertSame($modulo->id, $aula->modulo_id);
        $this->assertFalse((bool) $aula->liberada);
        $this->assertCount(1, $aula->anexos);
        Storage::disk('local')->assertExists($aula->anexos[0]['caminho']);

        $this->get(route('secretaria.ensino.aulas.material', [$aula, 0]))->assertOk();
        $this->actingAs($this->equipe('aluno'))
            ->get(route('secretaria.ensino.aulas.material', [$aula, 0]))->assertForbidden();
    }

    public function test_nao_permite_arquivo_executavel_nem_aula_sem_conteudo(): void
    {
        Storage::fake('local');
        [$curso, $modulo, $disciplina] = $this->catalogo();
        $this->actingAs($this->equipe());
        $this->post(route('secretaria.ensino.aulas.salvar'), [
            'disciplina_id' => $disciplina->id, 'titulo' => 'Tentativa',
            'ordem' => 1, 'materiais' => [UploadedFile::fake()->create('script.php', 1, 'application/x-php')],
        ])->assertSessionHasErrors('materiais.0');
        $this->post(route('secretaria.ensino.aulas.salvar'), [
            'disciplina_id' => $disciplina->id, 'titulo' => 'Sem conteúdo', 'ordem' => 1,
        ])->assertSessionHasErrors('conteudo_texto');
        $this->assertDatabaseCount('aulas', 0);
    }

    public function test_quiz_pertence_a_aula_e_prova_fica_fora_da_aula(): void
    {
        [$curso, $modulo, $disciplina] = $this->catalogo();
        $outro = Disciplina::create([
            'curso_id' => $curso->id, 'modulo_id' => $modulo->id, 'codigo' => 'HIST-2',
            'nome' => 'Outra disciplina', 'carga_horaria' => 20, 'ordem' => 2, 'ativa' => true,
        ]);
        $aula = Aula::create(['titulo' => 'Introdução', 'disciplina_id' => $disciplina->id,
            'modulo_id' => $modulo->id, 'ordem' => 1, 'conteudo_texto' => 'Um texto didático.',
            'tipo_conteudo' => 'texto', 'duracao' => 15, 'liberada' => false]);
        $this->actingAs($this->equipe());
        $base = ['titulo' => 'Avaliação preliminar', 'disciplina_id' => $disciplina->id,
            'tentativas_permitidas' => 1, 'tipo' => 'quiz'];

        $this->post(route('secretaria.ensino.avaliacoes.salvar'), $base)
            ->assertSessionHasErrors('aula_id');
        $this->post(route('secretaria.ensino.avaliacoes.salvar'), array_merge($base, [
            'disciplina_id' => $outro->id, 'aula_id' => $aula->id,
        ]))->assertSessionHasErrors('aula_id');
        $this->post(route('secretaria.ensino.avaliacoes.salvar'), array_merge($base, [
            'aula_id' => $aula->id,
        ]))->assertRedirect();
        $this->assertDatabaseHas('instrumentos_avaliacao', [
            'tipo' => 'quiz', 'aula_id' => $aula->id, 'disciplina_id' => $disciplina->id,
        ]);

        $this->post(route('secretaria.ensino.avaliacoes.salvar'), array_merge($base, [
            'tipo' => 'prova', 'aula_id' => $aula->id,
        ]))->assertSessionHasErrors('aula_id');
        $this->post(route('secretaria.ensino.avaliacoes.salvar'), array_merge($base, [
            'tipo' => 'simulado', 'aula_id' => null,
        ]))->assertRedirect();
        $this->assertDatabaseHas('instrumentos_avaliacao', [
            'tipo' => 'simulado', 'aula_id' => null,
        ]);
    }

    public function test_gabarito_validado_e_questoes_revisadas_podem_ser_reutilizadas(): void
    {
        [$curso, $modulo, $disciplina] = $this->catalogo();
        $this->actingAs($this->equipe());
        $dados = [
            'disciplina_id' => $disciplina->id, 'tipo' => 'multipla', 'dificuldade' => 'medio',
            'enunciado' => 'Quais afirmações estão de acordo com o material?',
            'alternativas' => ['A' => 'Afirmação um', 'B' => 'Afirmação dois',
                'C' => 'Afirmação três', 'D' => 'Afirmação quatro'],
            'gabarito' => ['A', 'C'], 'revisada' => '1',
        ];
        $this->post(route('secretaria.ensino.questoes.salvar'), array_merge($dados, [
            'gabarito' => ['A'],
        ]))->assertSessionHasErrors('gabarito');

        $this->post(route('secretaria.ensino.questoes.salvar'), $dados)->assertRedirect();
        $questao = BancoQuestao::firstOrFail();
        $this->assertSame(['A', 'C'], $questao->gabarito);
        $this->assertSame('revisada', $questao->status);

        $this->post(route('secretaria.ensino.avaliacoes.salvar'), [
            'disciplina_id' => $disciplina->id, 'tipo' => 'simulado', 'titulo' => 'Simulado de História',
            'tentativas_permitidas' => 2,
        ])->assertRedirect();
        $instrumento = InstrumentoAvaliacao::firstOrFail();
        $this->post(route('secretaria.ensino.instrumento.publicar', $instrumento), [
            'confirmar_publicacao' => '1',
        ])->assertSessionHasErrors('confirmar_publicacao');
        $this->post(route('secretaria.ensino.instrumento.questoes', $instrumento), [
            'questao_id' => $questao->id, 'pontos' => 2,
        ])->assertRedirect();
        $this->assertDatabaseHas('instrumento_questoes', [
            'instrumento_id' => $instrumento->id, 'questao_id' => $questao->id, 'pontos' => 2,
        ]);
        $this->post(route('secretaria.ensino.instrumento.publicar', $instrumento), [
            'confirmar_publicacao' => '1',
        ])->assertRedirect();
        $this->assertSame('publicado', $instrumento->fresh()->status);
        $this->put(route('secretaria.ensino.questoes.atualizar', $questao), $dados)
            ->assertSessionHasErrors('enunciado');
    }

    public function test_rascunhos_de_texto_exigem_transcricao_e_nao_inventam_gabarito(): void
    {
        [$curso, $modulo, $disciplina] = $this->catalogo();
        $aula = Aula::create([
            'disciplina_id' => $disciplina->id, 'modulo_id' => $modulo->id,
            'titulo' => 'Aula fonte', 'conteudo_texto' => str_repeat('O conteúdo exige estudo criterioso da documentação utilizada nesta disciplina e de seus exemplos. ', 3),
            'ordem' => 1, 'duracao' => 15, 'tipo_conteudo' => 'texto',
        ]);
        $this->actingAs($this->equipe());
        $this->post(route('secretaria.ensino.questoes.sugerir'), [
            'aula_id' => $aula->id, 'dificuldade' => 'facil', 'quantidade' => 2,
        ])->assertRedirect(route('secretaria.ensino.questoes'));
        $this->assertDatabaseCount('banco_questoes', 2);
        $this->assertDatabaseHas('banco_questoes', [
            'origem' => 'texto_assistido', 'status' => 'rascunho', 'tipo' => 'dissertativa',
        ]);
        $this->assertSame([], BancoQuestao::firstOrFail()->gabarito);
    }

    public function test_anexos_privados_so_sao_entregues_ao_aluno_matriculado_na_aula_liberada(): void
    {
        Storage::fake('local');
        [$curso, $modulo, $disciplina] = $this->catalogo();
        $aula = Aula::create([
            'modulo_id' => $modulo->id, 'disciplina_id' => $disciplina->id,
            'titulo' => 'Sala com material', 'conteudo_texto' => 'Texto de apoio',
            'tipo_conteudo' => 'texto', 'ordem' => 1, 'duracao' => 10,
            'liberada' => true, 'permite_download' => true,
        ]);
        $arquivo = 'ensino/aulas/'.$aula->id.'/apostila.pdf';
        Storage::disk('local')->put($arquivo, '%PDF-1.4 Conteúdo fictício');
        $aula->update(['anexos' => [['caminho' => $arquivo, 'nome' => 'apostila.pdf']]]);

        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $outro = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        Matricula::create([
            'user_id' => $aluno->id, 'curso_id' => $curso->id,
            'numero_matricula' => 'MAT-ENS-001', 'data_matricula' => today()->toDateString(),
            'dia_vencimento' => 10, 'status' => 'ativo',
        ]);
        $rota = route('aluno.aula.material', [$aula, 0]);

        $this->actingAs($outro)->get($rota)->assertForbidden();
        $this->actingAs($aluno)->get($rota)->assertOk();
        $this->get(route('aluno.aula', $aula->id))->assertOk()
            ->assertSee($rota);

        $aula->update(['liberada' => false]);
        $this->get($rota)->assertForbidden();
        $aula->update(['liberada' => true, 'permite_download' => false]);
        $this->get($rota)->assertForbidden();
    }

    public function test_previa_do_simulado_corrige_sem_gravar_nota_ou_tentativa(): void
    {
        [$curso, $modulo, $disciplina] = $this->catalogo();
        $ator = $this->equipe();
        $questao = BancoQuestao::create([
            'disciplina_id' => $disciplina->id, 'criada_por' => $ator->id,
            'enunciado' => 'Qual alternativa expressa a ideia principal da referência?',
            'tipo' => 'unica', 'dificuldade' => 'facil',
            'alternativas' => ['A' => 'Primeiro conceito', 'B' => 'Segundo conceito',
                'C' => 'Terceiro conceito', 'D' => 'Quarto conceito'],
            'gabarito' => ['B'], 'status' => 'revisada',
        ]);
        $instrumento = InstrumentoAvaliacao::create([
            'disciplina_id' => $disciplina->id, 'criado_por' => $ator->id,
            'tipo' => 'simulado', 'titulo' => 'Prévia do simulado', 'status' => 'rascunho',
            'tentativas_permitidas' => 2,
        ]);
        $instrumento->questoes()->attach($questao->id, ['ordem' => 1, 'pontos' => 3]);

        $this->actingAs($ator)
            ->get(route('secretaria.ensino.previa', $instrumento))->assertOk()
            ->assertSee('Corrigir respostas desta prévia');

        $this->post(route('secretaria.ensino.previa.corrigir', $instrumento), [
            'respostas' => [$questao->id => ['B']],
        ])->assertOk()->assertSee('3 de 3 pontos')->assertSee('100%');

        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('progressos', 0);
    }
}
