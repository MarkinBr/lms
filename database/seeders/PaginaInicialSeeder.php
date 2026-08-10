<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaginaInicial;

class PaginaInicialSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa a tabela antes de criar
        PaginaInicial::truncate();

        PaginaInicial::create([
            'titulo_identificacao' => 'Tema Padrão - Lançamento 2025',
            'ativa' => true,
            
            // --- HERO ---
            'hero_titulo_principal' => 'Transforme Seu',
            'hero_destaque_1' => 'Chamado',
            'hero_destaque_2' => 'Ministério',
            'hero_subtitulo' => 'Formação teológica de excelência com metodologia EAD moderna. Capacite-se para servir ao Reino de Deus com conhecimento, sabedoria e unção.',
            'hero_botao_texto' => 'Comece Agora',
            'hero_botao_link' => '/admin/register',
            'hero_botao_secundario_texto' => 'Ver Cursos',
            'hero_botao_secundario_link' => '#cursos',

            // --- ESTATÍSTICAS ---
            'estatisticas' => [
                ['numero' => '100%', 'texto' => 'Online', 'icone' => 'fas fa-laptop'],
                ['numero' => '3+', 'texto' => 'Cursos', 'icone' => 'fas fa-book'],
                ['numero' => '2024', 'texto' => 'Fundação', 'icone' => 'fas fa-calendar'],
                ['numero' => '150+', 'texto' => 'Alunos Ativos', 'icone' => 'fas fa-users'],
            ],

            // --- SOBRE ---
            'sobre_titulo' => 'Quem Somos',
            'sobre_texto' => '<p>O CETSADP é o Centro Educacional, Teológico e Social da Assembleia de Deus de Paracambi. Nossa missão é equipar os santos para a obra do ministério.</p>',
            'sobre_diferenciais' => [
                ['titulo' => 'Excelência Teológica', 'descricao' => 'Ensino fundamentado', 'icone' => 'fas fa-check-circle'],
                ['titulo' => 'Visão Social', 'descricao' => 'Impacto na comunidade', 'icone' => 'fas fa-heart'],
                ['titulo' => 'Metodologia Moderna', 'descricao' => 'Tecnologia avançada', 'icone' => 'fas fa-laptop'],
                ['titulo' => 'Compromisso', 'descricao' => 'Formação de líderes', 'icone' => 'fas fa-users'],
            ],

            // --- CURSOS ---
            'cursos_titulo' => 'Nossos Cursos',
            'cursos_descricao' => 'Escolha a formação ideal para seu chamado ministerial.',
            'cursos_categorias' => [
                ['nome' => 'Teologia'],
                ['nome' => 'Ministério'],
                ['nome' => 'Liderança']
            ],
            'cursos_lista' => [
                [
                    'titulo' => 'Habilitação ao Diaconato',
                    'categoria' => 'Ministério',
                    'nivel' => 'INICIANTE',
                    'resumo' => 'Preparação completa para o exercício do diaconato.',
                    'duracao' => '6 meses',
                    'carga_horaria' => '120h',
                    'modalidade' => '100% Online',
                    'investimento' => 'R$ 50,00/mês',
                    'link' => '/admin/register'
                ],
                [
                    'titulo' => 'Médio em Teologia',
                    'categoria' => 'Teologia',
                    'nivel' => 'INTERMEDIÁRIO',
                    'resumo' => 'Aprofundamento nas Escrituras e doutrinas.',
                    'duracao' => '12 meses',
                    'carga_horaria' => '360h',
                    'modalidade' => '100% Online',
                    'investimento' => 'R$ 100,00/mês',
                    'link' => '/admin/register'
                ],
                [
                    'titulo' => 'Liderança Cristã',
                    'categoria' => 'Liderança',
                    'nivel' => 'AVANÇADO',
                    'resumo' => 'Gestão e liderança para o Reino de Deus.',
                    'duracao' => '18 meses',
                    'carga_horaria' => '400h',
                    'modalidade' => 'Híbrido',
                    'investimento' => 'R$ 150,00/mês',
                    'link' => '/admin/register'
                ],
            ],

            // --- METODOLOGIA ---
            'metodologia_titulo' => 'Como Funciona',
            'metodologia_passos' => [
                ['numero' => '1', 'titulo' => 'Inscrição', 'descricao' => 'Faça seu cadastro', 'icone' => 'fas fa-user-plus'],
                ['numero' => '2', 'titulo' => 'Acesso', 'descricao' => 'Entre no portal', 'icone' => 'fas fa-laptop'],
                ['numero' => '3', 'titulo' => 'Estudo', 'descricao' => 'Assista às aulas', 'icone' => 'fas fa-book-open'],
                ['numero' => '4', 'titulo' => 'Diploma', 'descricao' => 'Receba certificação', 'icone' => 'fas fa-certificate'],
            ],

            // --- VANTAGENS ---
            'vantagens_titulo' => 'Diferenciais',
            'vantagens_lista' => [
                ['titulo' => '100% EAD', 'descricao' => 'Estude onde quiser', 'icone' => 'fas fa-wifi'],
                ['titulo' => 'Professores Qualificados', 'descricao' => 'Mestres e Doutores', 'icone' => 'fas fa-user-tie'],
                ['titulo' => 'Material Didático', 'descricao' => 'Incluso no curso', 'icone' => 'fas fa-book'],
            ],

            // --- FAQ ---
            'faq_titulo' => 'Dúvidas Comuns',
            'faq_lista' => [
                ['pergunta' => 'Os cursos são reconhecidos?', 'resposta' => 'São cursos livres de caráter eclesiástico.'],
                ['pergunta' => 'Como acesso as aulas?', 'resposta' => 'Pelo nosso portal do aluno com login e senha.'],
            ],

            // --- CONTATO ---
            'contato_titulo' => 'Fale Conosco',
            'endereco' => 'Rua Afonso Franco, Nº15, Centro - Paracambi - RJ',
            'telefone' => '(21) 1234-5678',
            'whatsapp' => '(21) 99999-9999',
            'email' => 'contato@cetsadp.com.br',
            'horario_funcionamento' => 'Seg-Sex: 18h às 22h',
            
            // --- CORES ---
            'cores_personalizadas' => [
                'primary' => '#003D7A',
                'secondary' => '#D62828',
                'accent' => '#F77F00'
            ]
        ]);
    }
}