<?php

namespace Tests\Support;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\User;

class SalaAulaFixture
{
    public static function criar(): array
    {
        $aluno = User::factory()->create(['name' => 'Aluno de homologação', 'nivel' => 'aluno', 'ativo' => true]);
        $curso = Curso::create(['titulo' => 'Formação em Teologia', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Fundamentos da fé cristã', 'ordem' => 1]);
        $disciplina = Disciplina::create(['curso_id' => $curso->id, 'modulo_id' => $modulo->id, 'codigo' => 'TESTE-01', 'nome' => 'Introdução à Teologia', 'carga_horaria' => 40, 'ordem' => 1, 'ativa' => true]);
        Matricula::create(['user_id' => $aluno->id, 'curso_id' => $curso->id, 'status' => 'ativo', 'data_matricula' => now(), 'numero_matricula' => 'TESTE-001', 'dia_vencimento' => 10]);
        $aulas = [];
        foreach (['Uma jornada de conhecimento e fé', 'A Escritura como fundamento da fé', 'O contexto histórico das Escrituras', 'Reflexão e aplicação no ministério'] as $i => $titulo) {
            $aulas[] = Aula::create([
                'modulo_id' => $modulo->id, 'disciplina_id' => $disciplina->id, 'titulo' => $titulo, 'ordem' => $i + 1,
                'liberada' => $i < 3, 'tipo_conteudo' => 'misto', 'duracao' => 24,
                'descricao' => 'Nesta aula, vamos compreender o lugar das Escrituras na formação cristã e relacionar a leitura atenta com a vida e o ministério.',
                'conteudo_texto' => '<h2>Conhecer para viver a fé</h2><p>O estudo da Teologia é um convite à leitura atenta, à reflexão e à prática. Ao nos aproximarmos das Escrituras, procuramos compreender seu contexto e cultivar uma fé consciente.</p><h3>Uma leitura com propósito</h3><p>Observe o texto, identifique suas ideias centrais e registre suas perguntas. Uma boa anotação ajuda a conectar o que aprendemos à nossa caminhada.</p><blockquote>Procura apresentar-te a Deus aprovado, como obreiro que não tem de que se envergonhar. — 2 Timóteo 2:15</blockquote><p>Ao concluir a leitura, retome os trechos que você grifou e escreva, com suas palavras, uma aplicação para sua vida.</p>',
                'pdf_url' => '/_qa/material.pdf', 'pdf_titulo' => 'Guia de estudo · Introdução à Teologia',
                'video_url' => '/_qa/video.mp4', 'permite_download' => true, 'permite_comentarios' => true,
                'questoes' => [['tipo' => 'multipla_escolha', 'enunciado' => 'O estudo atento envolve:', 'pontos' => 1, 'alternativas' => [['letra' => 'A', 'texto' => 'Leitura, reflexão e prática.', 'correta' => true], ['letra' => 'B', 'texto' => 'Somente memorização.', 'correta' => false]]]],
            ]);
        }

        return ['aluno' => $aluno, 'curso' => $curso, 'aula' => $aulas[1], 'bloqueada' => $aulas[3], 'aulas' => $aulas];
    }
}
