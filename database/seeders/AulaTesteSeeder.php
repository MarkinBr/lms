<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Aula;

class AulaTesteSeeder extends Seeder
{
    public function run(): void
    {
        // Verifica se já existe um curso, caso contrário, cria um básico
        $curso = Curso::first();
        
        if (!$curso) {
            $curso = Curso::create([
                'titulo' => 'Curso Avançado de Teste',
                'descricao' => 'Criado automaticamente para testes do novo layout.',
                'ativo' => true,
            ]);
        }

        // Criar um Módulo
        $modulo = Modulo::create([
            'curso_id' => $curso->id,
            'titulo' => 'Módulo 1 - Conhecendo a Plataforma',
            'descricao' => 'Módulo de demonstração dos novos recursos.',
            'ordem' => 1,
        ]);

        // Criar a Aula Completa
        Aula::create([
            'modulo_id' => $modulo->id,
            'titulo' => 'Aula 01 - Demonstração Completa (Áudio, Vídeo, PDF e Quiz)',
            'descricao' => 'Explore o novo design avançado das aulas.',
            'ordem' => 1,
            'liberada' => true,
            'tipo_liberacao' => 'imediata',
            'tipo_conteudo' => 'misto',
            'conteudo_texto' => '<p>Bem-vindo ao novo ecrã de aulas. Abaixo encontrará todos os formatos de conteúdo de forma integrada para maximizar a sua aprendizagem.</p>',
            
            // Mídia Principal
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // Vídeo de exemplo do YouTube
            'video_provider' => 'youtube',
            'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
            'audio_titulo' => 'Ouça o Podcast da Aula',
            'pdf_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'pdf_titulo' => 'Apostila Digital (PDF)',

            // Blocos de Conteúdo Estruturado
            'blocos_conteudo' => json_encode([
                [
                    'tipo' => 'Dica do Professor',
                    'conteudo' => 'Tente acompanhar a reprodução do vídeo enquanto consulta o material em PDF ao lado.',
                ],
                [
                    'tipo' => 'Importante',
                    'conteudo' => 'Não se esqueça de realizar o teste no final do conteúdo para fixar a matéria.',
                ]
            ]),

            // Questões / Quiz Dinâmico
            'questoes' => json_encode([
                [
                    'tipo' => 'multipla_escolha',
                    'enunciado' => 'Qual das seguintes mídias está disponível diretamente neste novo painel de aula?',
                    'alternativas' => [
                        ['texto' => 'Apenas texto simples', 'correta' => false],
                        ['texto' => 'Vídeo, Áudio e Visualizador de PDF integrados', 'correta' => true],
                        ['texto' => 'Apenas vídeos locais', 'correta' => false],
                    ],
                    'explicacao' => 'O novo painel foi construído para unificar o consumo de vídeos, podcasts e apostilas sem a necessidade de abrir outros separadores.'
                ]
            ]),
            'questoes_quantidade' => 1,
            'nota_minima_aprovacao' => 70,
            'permite_download' => true,
            'permite_comentarios' => true,
            'tempo_minimo_minutos' => 1,
        ]);

        $this->command->info('Aula de teste avançada criada com sucesso! 🚀');
    }
}