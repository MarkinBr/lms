<?php

namespace App\Services\Telegram;

class CatalogoComandosTelegram
{
    /**
     * Apenas comandos já tratados no TelegramAdministrativo.
     * Comandos com argumentos exibem instruções de uso quando enviados sem parâmetros.
     */
    public static function listar(): array
    {
        return [
            ['command' => 'menu', 'description' => 'Abrir menu administrativo'],
            ['command' => 'central', 'description' => 'Central de gestão acadêmica'],
            ['command' => 'prioridades', 'description' => 'Pendências que exigem atenção'],
            ['command' => 'pesquisar', 'description' => 'Pesquisar aluno: requer nome'],
            ['command' => 'desempenho', 'description' => 'Frequência acadêmica por turma'],
            ['command' => 'agendainteligente', 'description' => 'Agenda dos próximos sete dias'],
            ['command' => 'relatoriosemanal', 'description' => 'Indicadores dos últimos sete dias'],
            ['command' => 'notificacoes', 'description' => 'Preferências de notificações'],
            ['command' => 'hoje', 'description' => 'Visão acadêmica de hoje'],
            ['command' => 'resumo', 'description' => 'Resumo acadêmico'],
            ['command' => 'pendencias', 'description' => 'Consultar pendências'],
            ['command' => 'agenda', 'description' => 'Ver agenda'],
            ['command' => 'agenda7', 'description' => 'Ver agenda dos próximos 7 dias'],
            ['command' => 'relatorios', 'description' => 'Consultar relatórios'],
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
            ['command' => 'alertas', 'description' => 'Consultar ou alterar preferência de alertas'],
            ['command' => 'testeconfirmacao', 'description' => 'Simular uma confirmação sem alterar dados'],
            ['command' => 'status', 'description' => 'Verificar o vínculo administrativo'],
            ['command' => 'ajuda', 'description' => 'Ver opções disponíveis'],
            ['command' => 'start', 'description' => 'Iniciar o bot'],
        ];
    }
}
