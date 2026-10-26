<?php

namespace App\Services\Telegram;

class CatalogoComandosTelegram
{
    private const OPERACOES = [
        'novoaluno' => ['Cadastrar aluno', '/novoaluno NOME | EMAIL ou - | CPF ou -'],
        'editaraluno' => ['Editar aluno', '/editaraluno ID | NOME | TELEFONE ou -'],
        'recuperaracesso' => ['Recuperar acesso', '/recuperaracesso ALUNO_ID. Somente e-mail verificado'],
        'ativaracesso' => ['Ativar primeiro acesso', '/ativaracesso ALUNO_ID. Envia ativação individual para aluno pendente com e-mail cadastrado'],
        'aprovarinscricao' => ['Aprovar inscrição', '/aprovarinscricao UUID. Cria matrícula após confirmação'],
        'rejeitarinscricao' => ['Rejeitar inscrição', '/rejeitarinscricao UUID | MOTIVO'],
        'lancarnota' => ['Lançar notas', '/lancarnota ALUNO_ID | DISCIPLINA_ID | PERIODO | AV1 | AV2 | AV3 | FALTAS'],
        'corrigirnota' => ['Corrigir notas', '/corrigirnota NOTA_ID | AV1 | AV2 | AV3 | FALTAS'],
        'novanoticia' => ['Publicar notícia', '/novanoticia TÍTULO | RESUMO | TEXTO'],
        'novoaviso' => ['Novo aviso', '/novoaviso TÍTULO | MENSAGEM'],
        'novoevento' => ['Novo evento', '/novoevento TÍTULO | AAAA-MM-DD HH:MM | DESCRIÇÃO'],
        'novaaula' => ['Nova aula', '/novaaula DISCIPLINA_ID | TÍTULO | URL_VIDEO | URL_PDF. Use - para material ausente; links devem usar HTTPS'],
        'liberaraula' => ['Liberar aula', '/liberaraula AULA_ID. Consulte /aulaspendentes para encontrar o ID'],
        'novachamada' => ['Nova chamada', '/novachamada TURMA_ID DISCIPLINA_ID AAAA-MM-DD'],
        'registrarpresenca' => ['Registrar frequência', '/registrarpresenca CHAMADA_ID MATRICULA_ID presente|falta|falta_justificada'],
        'concluirchamada' => ['Concluir chamada', '/concluirchamada CHAMADA_ID. Registre antes todos os alunos ativos'],
    ];

    public static function menuPrincipal(): array
    {
        return [
            'text' => "CETSADP • Painel administrativo\nEscolha uma área para continuar. Você pode voltar ao menu a qualquer momento.",
            'reply_markup' => ['inline_keyboard' => [
                [['text' => '👨‍🎓 Alunos', 'callback_data' => 'area:alunos'], ['text' => '📚 Cursos e aulas', 'callback_data' => 'area:cursos']],
                [['text' => '📝 Notas e presença', 'callback_data' => 'area:avaliacoes'], ['text' => '📢 Notícias e eventos', 'callback_data' => 'area:comunicacao']],
                [['text' => '📊 Relatórios', 'callback_data' => 'area:relatorios'], ['text' => '⚙️ Administração', 'callback_data' => 'area:admin']],
            ]],
        ];
    }

    public static function menuArea(string $area): array
    {
        $menus = [
            'alunos' => ['Gestão de alunos', [
                ['Cadastrar aluno', 'fluxo:novoaluno'],
                ['Listar alunos', 'lista:alunos:1'],
                ['Buscar aluno', 'orientar:buscar'],
                ['Editar cadastro', 'operacao:editaraluno'],
                ['Recuperar acesso', 'operacao:recuperaracesso'],
                ['Ativar acesso', 'operacao:ativaracesso'],
            ]],
            'cursos' => ['Cursos e aulas', [
                ['Consultar cursos', 'cursos'],
                ['Listar turmas', 'lista:turmas:1'],
                ['Listar aulas', 'lista:aulas:1'],
                ['Cadastrar aula', 'operacao:novaaula'],
                ['Liberar aula', 'operacao:liberaraula'],
            ]],
            'avaliacoes' => ['Notas e presença', [
                ['Lançar nota', 'operacao:lancarnota'],
                ['Corrigir nota', 'operacao:corrigirnota'],
                ['Nova chamada', 'operacao:novachamada'],
                ['Registrar presença', 'operacao:registrarpresenca'],
                ['Concluir chamada', 'operacao:concluirchamada'],
            ]],
            'comunicacao' => ['Notícias e eventos', [
                ['Publicar notícia', 'operacao:novanoticia'],
                ['Publicar aviso', 'operacao:novoaviso'],
                ['Novo evento', 'operacao:novoevento'],
            ]],
            'relatorios' => ['Relatórios e indicadores', [
                ['Painel do dia', 'hoje'],
                ['Relatórios', 'relatorios'],
                ['Agenda da semana', 'agenda7'],
                ['Resumo financeiro', 'financeiro'],
            ]],
            'admin' => ['Administração', [
                ['Pendências', 'pendencias'],
                ['Alertas', 'alertas'],
                ['Consultas avançadas', 'menuconsultas'],
                ['Todas as operações', 'administrar'],
            ]],
        ];

        if (! isset($menus[$area])) {
            return self::menuPrincipal();
        }

        [$titulo, $opcoes] = $menus[$area];
        $teclado = [];
        foreach (array_chunk($opcoes, 2) as $linha) {
            $teclado[] = array_map(
                fn (array $opcao): array => ['text' => $opcao[0], 'callback_data' => $opcao[1]],
                $linha
            );
        }
        $teclado[] = [['text' => 'Voltar ao menu principal', 'callback_data' => 'menu']];

        return [
            'text' => "CETSADP • {$titulo}\nSelecione o que deseja fazer:",
            'reply_markup' => ['inline_keyboard' => $teclado],
        ];
    }

    public static function orientar(string $comando): ?array
    {
        if (! isset(self::OPERACOES[$comando])) {
            return null;
        }

        return ['text' => 'Uso: '.self::OPERACOES[$comando][1]."\nVocê receberá uma prévia para confirmar ou cancelar em até 10 minutos."];
    }

    public static function menuAdministrativo(): array
    {
        $botoes = [];
        foreach (self::OPERACOES as $comando => [$titulo]) {
            $botoes[] = [['text' => $titulo, 'callback_data' => 'operacao:'.$comando]];
        }
        $botoes[] = [['text' => 'Voltar às consultas', 'callback_data' => 'menu']];

        return ['text' => "CETSADP • Administrar\nEscolha uma operação para ver como preencher. Toda alteração exige prévia e confirmação.",
            'reply_markup' => ['inline_keyboard' => $botoes]];
    }

    /**
     * Apenas comandos já tratados no TelegramAdministrativo.
     * Comandos com argumentos exibem instruções de uso quando enviados sem parâmetros.
     */
    public static function listar(): array
    {
        return [
            ['command' => 'menu', 'description' => 'Abrir menu administrativo'],
            ['command' => 'administrar', 'description' => 'Operações com prévia e confirmação'],
            ['command' => 'novoaluno', 'description' => 'Cadastrar aluno após confirmação'],
            ['command' => 'editaraluno', 'description' => 'Editar nome e telefone de aluno'],
            ['command' => 'recuperaracesso', 'description' => 'Recuperação por e-mail verificado'],
            ['command' => 'ativaracesso', 'description' => 'Reenviar ativação de primeiro acesso'],
            ['command' => 'aprovarinscricao', 'description' => 'Aprovar inscrição e criar matrícula'],
            ['command' => 'rejeitarinscricao', 'description' => 'Rejeitar inscrição com justificativa'],
            ['command' => 'lancarnota', 'description' => 'Lançar notas com confirmação'],
            ['command' => 'corrigirnota', 'description' => 'Corrigir notas com confirmação'],
            ['command' => 'novanoticia', 'description' => 'Publicar notícia no site institucional'],
            ['command' => 'hoje', 'description' => 'Visão acadêmica de hoje'],
            ['command' => 'resumo', 'description' => 'Resumo acadêmico'],
            ['command' => 'pendencias', 'description' => 'Consultar pendências'],
            ['command' => 'agenda', 'description' => 'Ver agenda'],
            ['command' => 'agenda7', 'description' => 'Ver agenda dos próximos 7 dias'],
            ['command' => 'relatorios', 'description' => 'Consultar relatórios'],
            ['command' => 'financeiro', 'description' => 'Totais financeiros sem dados individuais'],
            ['command' => 'aulaspendentes', 'description' => 'Ver aulas pendentes'],
            ['command' => 'cursos', 'description' => 'Listar cursos'],
            ['command' => 'filtros', 'description' => 'Escolher filtros de alunos'],
            ['command' => 'buscar', 'description' => 'Pesquisar aluno: requer nome'],
            ['command' => 'aluno', 'description' => 'Ficha do aluno: requer ID'],
            ['command' => 'notas', 'description' => 'Notas do aluno: requer ID'],
            ['command' => 'frequencia', 'description' => 'Frequência do aluno: requer ID'],
            ['command' => 'turma', 'description' => 'Consultar turma: requer ID'],
            ['command' => 'matricula', 'description' => 'Consultar matrícula: requer ID'],
            ['command' => 'inscricao', 'description' => 'Consultar inscrição: requer UUID'],
            ['command' => 'filtro', 'description' => 'Filtrar alunos: requer critérios'],
            ['command' => 'revisarinscricao', 'description' => 'Pré-analisar inscrição: requer UUID'],
            ['command' => 'novoaviso', 'description' => 'Publicar aviso após confirmação'],
            ['command' => 'novaaula', 'description' => 'Cadastrar aula de vídeo ou PDF em rascunho'],
            ['command' => 'liberaraula', 'description' => 'Liberar aula após confirmação'],
            ['command' => 'concluirchamada', 'description' => 'Concluir chamada com frequência completa'],
            ['command' => 'novoevento', 'description' => 'Publicar evento após confirmação'],
            ['command' => 'novachamada', 'description' => 'Criar chamada após confirmação'],
            ['command' => 'registrarpresenca', 'description' => 'Registrar frequência após confirmação'],
            ['command' => 'alertas', 'description' => 'Consultar ou alterar preferência de alertas'],
            ['command' => 'testeconfirmacao', 'description' => 'Simular uma confirmação sem alterar dados'],
            ['command' => 'status', 'description' => 'Verificar o vínculo administrativo'],
            ['command' => 'ajuda', 'description' => 'Ver opções disponíveis'],
            ['command' => 'start', 'description' => 'Iniciar o bot'],
        ];
    }
}
