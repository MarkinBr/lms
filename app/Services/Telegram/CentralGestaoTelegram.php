<?php

namespace App\Services\Telegram;

use App\Models\Application;
use App\Models\Aula;
use App\Models\AulaAoVivo;
use App\Models\Matricula;
use App\Models\Turma;
use App\Services\GestaoAcademica\PainelAcademicoQuery;

class CentralGestaoTelegram
{
    public function central(): array
    {
        $indicadores = app(PainelAcademicoQuery::class)->resumo();

        return [
            'text' => "CETSADP • Central de gestão\n"
                .'Alunos ativos: '.$indicadores['alunos']."\n"
                .'Matrículas ativas: '.$indicadores['matriculas_ativas']."\n"
                .'Cursos ativos: '.$indicadores['cursos']."\n"
                .'Turmas cadastradas: '.$indicadores['turmas']."\n"
                .'Inscrições em análise: '.Application::query()->emAnalise()->count()."\n"
                .'Aulas não liberadas: '.Aula::query()->where('liberada', false)->count()
                ."\n\nIndicadores de consulta. Detalhes somente no painel autenticado.",
            'reply_markup' => ['inline_keyboard' => [
                [['text' => '🚨 Prioridades', 'callback_data' => 'prioridades'],
                    ['text' => '📊 Desempenho', 'callback_data' => 'desempenho']],
                [['text' => '📅 Agenda', 'callback_data' => 'agendainteligente'],
                    ['text' => '📑 Relatório 7 dias', 'callback_data' => 'relatoriosemanal']],
                [['text' => '🔔 Notificações', 'callback_data' => 'notificacoes'],
                    ['text' => 'Menu', 'callback_data' => 'menu']],
            ]],
        ];
    }

    public function prioridades(): array
    {
        $pendencias = app(PainelAcademicoQuery::class)->pendencias();
        $inscricoes = Application::query()->emAnalise()->count();
        $aulas = Aula::query()->where('liberada', false)->count();

        return [
            'text' => "CETSADP • Prioridades\n"
                ."Inscrições em análise: {$inscricoes}\n"
                ."Aulas não liberadas: {$aulas}\n"
                ."Pendências estruturais:\n"
                .'• Disciplinas sem módulo: '.$pendencias['disciplinas_sem_modulo']['count']."\n"
                .'• Aulas sem disciplina: '.$pendencias['aulas_sem_disciplina']['count']."\n"
                .'• Turmas sem período: '.$pendencias['turmas_sem_periodo']['count']."\n"
                .'• Turmas sem disciplina: '.$pendencias['turmas_sem_disciplina']['count']
                ."\n\nContagens indicativas, não classificações de gravidade. Trate os casos no painel.",
            'reply_markup' => ['inline_keyboard' => [
                [['text' => 'Atualizar prioridades', 'callback_data' => 'prioridades']],
                [['text' => 'Central', 'callback_data' => 'central']],
            ]],
        ];
    }

    public function desempenho(?int $turmaId = null): array
    {
        if ($turmaId === null) {
            $turmas = Turma::query()->orderByDesc('id')->limit(5)->get(['id', 'nome']);
            $teclado = $turmas->map(fn (Turma $turma) => [[
                'text' => 'Turma #'.$turma->id.' • '.mb_strimwidth((string) $turma->nome, 0, 30, '…'),
                'callback_data' => 'desempenho:'.$turma->id,
            ]])->all();
            $teclado[] = [['text' => 'Central', 'callback_data' => 'central']];

            return ['text' => "Desempenho por turma\n"
                ."Escolha uma turma ou envie /desempenho ID.\n"
                ."As frequências usam somente chamadas concluídas. Não há ranking de alunos.",
                'reply_markup' => ['inline_keyboard' => $teclado]];
        }

        $turma = Turma::query()->find($turmaId, ['id', 'nome']);
        if ($turma === null) {
            return ['text' => 'Turma não encontrada. Use /desempenho para escolher uma turma.'];
        }

        $matriculas = Matricula::query()->where(function ($q) use ($turmaId) {
            $q->where('turma_id', $turmaId)->orWhereHas('turmasHistorico',
                fn ($turmas) => $turmas->where('turmas.id', $turmaId)
                    ->where('matricula_turma.status', 'ativo'));
        })->ativas();
        $total = (clone $matriculas)->count();
        $amostra = $matriculas->orderBy('id')->limit(5)->get(['id', 'user_id', 'turma_id']);
        $linhas = $amostra->map(function (Matricula $matricula) use ($turmaId) {
            $freq = $matricula->resumoFrequencia($turmaId);

            return '#'.$matricula->id.' • '.$freq['percentual'].'% de presença ('
                .$freq['chamadas_concluidas'].' chamadas concluídas)';
        })->all();

        return ['text' => "Desempenho • turma #{$turma->id}\n"
            .'Turma: '.mb_strimwidth((string) $turma->nome, 0, 42, '…')."\n"
            ."Matrículas ativas vinculadas: {$total}\n"
            ."Frequência: amostra de até 5 matrículas (sem ranking)\n"
            .($linhas ? implode("\n", $linhas) : 'Nenhuma matrícula ativa vinculada.')
            ."\nConsulte o painel para análise acadêmica completa.",
            'reply_markup' => ['inline_keyboard' => [
                [['text' => 'Outras turmas', 'callback_data' => 'desempenho'],
                    ['text' => 'Central', 'callback_data' => 'central']],
            ]]];
    }

    public function agendaInteligente(): array
    {
        $agenda = app(ConsultasAcademicasTelegram::class)->agendaSemanal();

        return [
            'text' => "Agenda inteligente • próximos 7 dias\n"
                .$agenda['text']
                ."\n\nLembretes automáticos ainda não habilitados. Configure /notificacoes para registrar preferência.",
            'reply_markup' => ['inline_keyboard' => [
                [['text' => 'Notificações', 'callback_data' => 'notificacoes'],
                    ['text' => 'Central', 'callback_data' => 'central']],
            ]],
        ];
    }

    public function relatorioSemanal(): array
    {
        $inicio = now()->subDays(7);
        $fim = now();
        $inscricoes = Application::query()->whereBetween('created_at', [$inicio, $fim])->count();
        $matriculas = Matricula::query()->whereBetween('created_at', [$inicio, $fim])->count();
        $encontros = AulaAoVivo::query()->whereBetween('data_hora', [$inicio, $fim])->count();
        $emAnalise = Application::query()->emAnalise()->count();

        return [
            'text' => "CETSADP • Relatório semanal\n"
                .'Janela: últimos 7 dias ('.$inicio->format('d/m H:i').' a '.$fim->format('d/m H:i').")\n"
                ."Novas inscrições cadastradas: {$inscricoes}\n"
                ."Novas matrículas cadastradas: {$matriculas}\n"
                ."Encontros com data no período: {$encontros}\n"
                ."Inscrições atualmente em análise: {$emAnalise}\n"
                ."As contagens registram eventos cadastrados, não resultados pedagógicos.\n"
                ."Envio automático ainda não habilitado.",
            'reply_markup' => ['inline_keyboard' => [
                [['text' => 'Central', 'callback_data' => 'central'],
                    ['text' => 'Notificações', 'callback_data' => 'notificacoes']],
            ]],
        ];
    }
}
