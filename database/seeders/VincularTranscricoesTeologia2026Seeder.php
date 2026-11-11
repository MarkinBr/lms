<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Classifica as gravações com base nas três transcrições fornecidas pelo usuário.
 *
 * Referências: CETSADP_TRANSCRICAO_NOsvbWaaaus.txt,
 * CETSADP_TRANSCRICAO_5Osxg5CvQ2w.txt e
 * CETSADP_TRANSCRICAO_pQ8aokW5-Ls.txt.
 *
 * O número no título do painel antigo é do encontro e não da disciplina.
 * Não altera a matriz, cargas horárias, notas, turmas nem matrículas.
 */
class VincularTranscricoesTeologia2026Seeder extends Seeder
{
    private const MAPEAMENTO = [
        [
            'origem' => '26',
            'video' => 'NOsvbWaaaus',
            'codigo' => 'TEO-M02-12',
            'disciplina' => 'História e Cultura Judaica',
            'titulo' => 'Aula 2 — Divisão dos reinos, exílios e cultura judaica',
            'ordem' => 2,
            'duracao' => '180min',
            'data' => '20/06/2026',
            'conteudo' => 'historia-cultura-judaica-aula-02.html',
        ],
        [
            'origem' => '32',
            'video' => '5Osxg5CvQ2w',
            'codigo' => 'TEO-M01-01',
            'disciplina' => 'Teologia Bíblica do Antigo Testamento',
            'titulo' => 'Aula 1 — Criação, revelação e teologia do Antigo Testamento',
            'ordem' => 1,
            'duracao' => '120min',
            'data' => '15/08/2026',
            'conteudo' => 'teologia-biblica-antigo-testamento-aula-01.html',
        ],
        [
            'origem' => '36',
            'video' => 'pQ8aokW5-Ls',
            'codigo' => 'TEO-M01-02',
            'disciplina' => 'Teologia Bíblica do Novo Testamento',
            'titulo' => 'Aula 1 — Introdução à Teologia Bíblica do Novo Testamento',
            'ordem' => 1,
            'duracao' => '180min',
            'data' => '17/10/2026',
            'conteudo' => 'teologia-biblica-novo-testamento-aula-01.html',
        ],
    ];

    public function run(): void
    {
        $this->call(ConteudosTeologiaVerificadosSeeder::class);

        DB::transaction(function (): void {
            $curso = Curso::query()
                ->where('titulo', MatrizCurricularTeologiaSeeder::CURSO_TITULO)
                ->firstOrFail();

            foreach (self::MAPEAMENTO as $item) {
                $disciplina = Disciplina::query()
                    ->where('curso_id', $curso->id)
                    ->where('codigo', $item['codigo'])
                    ->where('nome', $item['disciplina'])
                    ->firstOrFail();

                $videoUrl = 'https://www.youtube.com/watch?v='.$item['video'];
                $arquivo = __DIR__.'/conteudos/teologia/'.$item['conteudo'];
                $html = @file_get_contents($arquivo);
                if (! is_string($html) || trim($html) === '') {
                    throw new RuntimeException('Roteiro da aula não encontrado: '.$arquivo);
                }

                // Não criar aulas duplicadas nem alterar aulas existentes,
                // principalmente se editadas/publicadas pela secretaria.
                $aulaComVideo = Aula::query()->where('video_url', $videoUrl)->first();
                if ($aulaComVideo) {
                    if ((int) $aulaComVideo->disciplina_id !== (int) $disciplina->id) {
                        $this->command?->warn(
                            'Conflito curricular: vídeo '.$item['video'].' está em outra disciplina. Revisão manual necessária.'
                        );
                    }
                    continue;
                }

                if (Aula::query()
                    ->where('disciplina_id', $disciplina->id)
                    ->where('titulo', $item['titulo'])
                    ->exists()) {
                    $this->command?->warn('Aula com mesmo título já cadastrada: '.$item['titulo']);
                    continue;
                }

                Aula::query()->create([
                    'modulo_id' => $disciplina->modulo_id,
                    'disciplina_id' => $disciplina->id,
                    'titulo' => $item['titulo'],
                    'descricao' => 'Encontro CETSADP #'.$item['origem'].' de '.$item['data']
                        .'. Disciplina identificada pela transcrição. Duração nominal do painel anterior; '
                        .'conferir duração da gravação. Roteiro de estudo sujeito à revisão acadêmica.',
                    'conteudo_texto' => $html,
                    'tipo_conteudo' => 'misto',
                    'video_url' => $videoUrl,
                    'video_provider' => 'youtube',
                    'duracao' => $item['duracao'],
                    'materiais' => $item['codigo'] === 'TEO-M02-12' ? $this->materiaisJudaica() : [],
                    'anexos' => [],
                    'ordem' => $item['ordem'],
                    'liberada' => false,
                ]);
            }
        });
    }

    private function materiaisJudaica(): array
    {
        // A associação é com a disciplina; o PDF não comprova uma aula exclusiva.
        return [
            [
                'titulo' => 'Ementa — História e Cultura Judaica',
                'tipo' => 'pdf',
                'url' => 'https://drive.google.com/file/d/1zZlhfQqFAt7RoUxV9B3yaZ6_jhRVsQKa/view?usp=sharing',
                'contexto' => 'Material da disciplina (aulas 1 e 2).',
            ],
            [
                'titulo' => 'História e Cultura Judaica — Material do Seminário',
                'tipo' => 'pdf',
                'url' => 'https://drive.google.com/file/d/11KUqDpLwoGcE5JLzqh3kLxUIWJ1LvVv7/view?usp=sharing',
                'contexto' => 'Material da disciplina; verificar capítulos da Aula 2.',
            ],
        ];
    }
}
