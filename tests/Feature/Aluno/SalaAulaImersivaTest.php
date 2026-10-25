<?php

namespace Tests\Feature\Aluno;

use App\Models\Matricula;
use App\Models\Progresso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SalaAulaFixture;
use Tests\TestCase;

class SalaAulaImersivaTest extends TestCase
{
    use RefreshDatabase;

    private function carga(string $texto = 'Minha reflexão'): array
    {
        return ['revision' => 0, 'baseNotes' => '', 'notebook' => ['version' => 1, 'text' => $texto, 'highlights' => [], 'anchors' => []]];
    }

    public function test_sala_dedicada_preserva_rotas_reais_e_nao_carrega_scripts_legados(): void
    {
        $f = SalaAulaFixture::criar();
        $this->actingAs($f['aluno'])->get(route('aluno.aula', $f['aula']->id))
            ->assertOk()->assertSee('A Escritura como fundamento da fé')->assertSee('Meu caderno')
            ->assertSee('estudio-aula.js')->assertSee('estudio-aula.css')->assertViewIs('aluno.sala-aula')
            ->assertDontSee('premium-classroom.js')->assertDontSee('cdn.tailwindcss.com')->assertDontSee('CETSADP_ESTUDIO_V1');
    }

    public function test_transcricao_cadastrada_chega_a_sala_com_tempos_sem_confundir_com_leitura(): void
    {
        $f = SalaAulaFixture::criar();
        $f['aula']->update(['transcricao_video' => "WEBVTT\n\n00:01.000 --> 00:04.000\nUma fala do professor."]);
        $this->actingAs($f['aluno'])->get(route('aluno.aula', $f['aula']->id))
            ->assertOk()->assertSee('Uma fala do professor.')->assertSee('video-pin')->assertSee('panel-transcript');
        $this->assertSame("WEBVTT\n\n00:01.000 --> 00:04.000\nUma fala do professor.", $f['aula']->fresh()->transcricao_video);
        $f['aula']->update(['transcricao_video' => null]);
        $this->get(route('aluno.aula', $f['aula']->id))->assertOk()->assertDontSee('Uma fala do professor.');
    }

    public function test_caderno_persiste_grifos_e_notas_simples_sem_concluir_aula(): void
    {
        $f = SalaAulaFixture::criar();
        $carga = $this->carga();
        $carga['notebook']['highlights'][] = ['id' => 'grifo-1', 'source' => 'pdf', 'text' => 'Um trecho', 'color' => 'yellow', 'url' => 'http://localhost/material.pdf', 'page' => 2, 'rects' => [[0.1, 0.2, 0.5, 0.03]]];
        $carga['notebook']['anchors'][] = ['id' => 'ref-1', 'kind' => 'video', 'label' => 'Vídeo · 1:30', 'time' => 90];
        $this->actingAs($f['aluno'])->postJson(route('aluno.aula.caderno', $f['aula']->id), $carga)->assertOk()->assertJson(['success' => true, 'revision' => 1]);
        $this->assertDatabaseHas('progressos', ['user_id' => $f['aluno']->id, 'aula_id' => $f['aula']->id, 'notas' => 'Minha reflexão']);
        $this->assertDatabaseMissing('aula_progresso', ['aluno_id' => $f['aluno']->id, 'aula_id' => $f['aula']->id, 'concluida' => true]);
        $salvo = json_decode(DB::table('cadernos_aula')->value('conteudo'), true);
        $this->assertSame($carga['notebook']['highlights'], $salvo['highlights']);
        $this->actingAs($f['aluno'])->get(route('aluno.aula', $f['aula']->id))->assertOk()->assertSee('grifo-1')->assertViewHas('caderno');
    }

    public function test_revisao_antiga_nao_substitui_caderno_mais_recente(): void
    {
        $f = SalaAulaFixture::criar();
        $url = route('aluno.aula.caderno', $f['aula']->id);
        $this->actingAs($f['aluno'])->postJson($url, $this->carga('Primeira versão'))->assertOk();
        $this->postJson($url, $this->carga('Aba antiga'))->assertStatus(409);
        $this->assertSame('Primeira versão', json_decode(DB::table('cadernos_aula')->value('conteudo'), true)['text']);
    }

    public function test_outra_aba_que_muda_so_grifos_tambem_gera_conflito(): void
    {
        $f = SalaAulaFixture::criar();
        $carga = $this->carga('');
        $this->actingAs($f['aluno'])->postJson(route('aluno.aula.caderno', $f['aula']->id), $carga)->assertOk();
        $this->postJson(route('aluno.aula.caderno', $f['aula']->id), $carga)->assertStatus(409);
    }

    public function test_notas_antigas_sao_preservadas_e_edicao_legada_e_detectada(): void
    {
        $f = SalaAulaFixture::criar();
        Progresso::create(['user_id' => $f['aluno']->id, 'aula_id' => $f['aula']->id, 'notas' => 'Anotação antiga']);
        $this->actingAs($f['aluno'])->get(route('aluno.aula', $f['aula']->id))->assertOk()->assertViewHas('progresso', fn ($p) => $p->notas === 'Anotação antiga');
        $this->postJson(route('aluno.aula.caderno', $f['aula']->id), $this->carga())->assertStatus(409);
        $carga = $this->carga('Atualizada');
        $carga['baseNotes'] = 'Anotação antiga';
        $this->postJson(route('aluno.aula.caderno', $f['aula']->id), $carga)->assertOk();
    }

    public function test_aluno_sem_vinculo_nao_acessa_nem_salva_caderno(): void
    {
        $f = SalaAulaFixture::criar();
        $outro = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->actingAs($outro)->get(route('aluno.aula', $f['aula']->id))->assertRedirect(route('aluno.cursos'));
        $this->postJson(route('aluno.aula.caderno', $f['aula']->id), $this->carga())->assertForbidden();
        $this->assertDatabaseCount('cadernos_aula', 0);
    }

    public function test_visitante_outros_perfis_e_aula_bloqueada_nao_salvam(): void
    {
        $f = SalaAulaFixture::criar();
        $this->postJson(route('aluno.aula.caderno', $f['aula']->id), $this->carga())->assertUnauthorized();
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $this->actingAs($admin)->postJson(route('aluno.aula.caderno', $f['aula']->id), $this->carga())->assertForbidden();
        $this->actingAs($f['aluno'])->postJson(route('aluno.aula.caderno', $f['bloqueada']->id), $this->carga())->assertForbidden();
    }

    public function test_rejeita_grifos_invalidos_sem_alterar_caderno(): void
    {
        $f = SalaAulaFixture::criar();
        $carga = $this->carga();
        $carga['notebook']['highlights'][] = ['id' => 'x', 'source' => 'pdf', 'text' => 'x', 'color' => 'yellow', 'url' => 'javascript:alert(1)', 'rects' => [[-1, 0, 1, 1]]];
        $this->actingAs($f['aluno'])->postJson(route('aluno.aula.caderno', $f['aula']->id), $carga)->assertUnprocessable();
        $this->assertDatabaseCount('cadernos_aula', 0);
    }

    public function test_identificador_de_outro_aluno_na_carga_nao_muda_o_dono(): void
    {
        $f = SalaAulaFixture::criar();
        $carga = $this->carga();
        $carga['aluno_id'] = 999;
        $this->actingAs($f['aluno'])->postJson(route('aluno.aula.caderno', $f['aula']->id), $carga)->assertOk();
        $this->assertDatabaseHas('cadernos_aula', ['aluno_id' => $f['aluno']->id, 'aula_id' => $f['aula']->id]);
        $this->assertDatabaseCount('cadernos_aula', 1);
    }

    public function test_quiz_calcula_nota_no_servidor_sem_confiar_na_nota_enviada(): void
    {
        $f = SalaAulaFixture::criar();
        $url = route('aluno.aula.quiz', $f['aula']->id);
        $this->actingAs($f['aluno'])->postJson($url, ['score' => 100, 'answers' => ['B']])
            ->assertOk()->assertJson(['score' => 0, 'passed' => false]);
        $this->assertDatabaseMissing('aula_progresso', ['aluno_id' => $f['aluno']->id, 'aula_id' => $f['aula']->id, 'concluida' => true]);
        $this->postJson($url, ['answers' => ['A']])->assertOk()->assertJson(['score' => 100, 'passed' => true]);
        $this->assertDatabaseHas('aula_progresso', ['aluno_id' => $f['aluno']->id, 'aula_id' => $f['aula']->id, 'concluida' => true]);
    }

    public function test_comentarios_desativados_sao_negados_no_servidor(): void
    {
        $f = SalaAulaFixture::criar();
        $f['aula']->update(['permite_comentarios' => false]);
        $this->actingAs($f['aluno'])->postJson(route('aluno.aula.comentar', $f['aula']->id), ['comentario' => 'Teste'])->assertForbidden();
        $this->assertDatabaseCount('comentarios_aula', 0);
    }

    public function test_alunos_da_mesma_aula_tem_cadernos_independentes(): void
    {
        $f = SalaAulaFixture::criar();
        $outro = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        Matricula::create(['user_id' => $outro->id, 'curso_id' => $f['curso']->id, 'status' => 'ativo', 'data_matricula' => now(), 'numero_matricula' => 'OUTRO-001', 'dia_vencimento' => 10]);
        $url = route('aluno.aula.caderno', $f['aula']->id);
        $this->actingAs($f['aluno'])->postJson($url, $this->carga('Caderno pessoal um'))->assertOk();
        $this->actingAs($outro)->postJson($url, $this->carga('Caderno pessoal dois'))->assertOk();
        $this->get(route('aluno.aula', $f['aula']->id))->assertOk()
            ->assertViewHas('caderno', fn ($c) => json_decode($c->conteudo, true)['text'] === 'Caderno pessoal dois');
        $this->actingAs($f['aluno'])->get(route('aluno.aula', $f['aula']->id))->assertOk()
            ->assertViewHas('caderno', fn ($c) => json_decode($c->conteudo, true)['text'] === 'Caderno pessoal um');
        $this->assertDatabaseCount('cadernos_aula', 2);
    }
}
