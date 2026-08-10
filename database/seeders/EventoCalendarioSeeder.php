<?php

namespace Database\Seeders;

use App\Models\EventoCalendario;
use App\Models\Turma;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventoCalendarioSeeder extends Seeder
{
    public function run(): void
    {
        $turmas = Turma::with('curso')
            ->orderBy('data_inicio')
            ->take(3)
            ->get();

        if ($turmas->isEmpty()) {
            return;
        }

        foreach ($turmas as $indice => $turma) {
            $dataBase = $turma->data_inicio
                ? Carbon::parse($turma->data_inicio)
                : now()->addWeeks($indice + 1);

            EventoCalendario::updateOrCreate(
                [
                    'turma_id' => $turma->id,
                    'titulo' => 'Encontro presencial de abertura - ' . $turma->nome,
                ],
                [
                    'curso_id' => $turma->curso_id,
                    'descricao' => 'Encontro inicial cadastrado pela secretaria para apresentar cronograma, materiais e orientações da turma.',
                    'data_inicio' => $dataBase->copy()->setTime(8, 0),
                    'data_fim' => $dataBase->copy()->setTime(12, 0),
                    'tipo' => 'academico',
                    'cor' => '#7c3aed',
                    'ativo' => true,
                ]
            );

            EventoCalendario::updateOrCreate(
                [
                    'turma_id' => $turma->id,
                    'titulo' => 'Prazo para atividades - ' . $turma->nome,
                ],
                [
                    'curso_id' => $turma->curso_id,
                    'descricao' => 'Data importante para entrega de atividades e conferência de pendências acadêmicas da turma.',
                    'data_inicio' => $dataBase->copy()->addWeeks(3)->setTime(18, 30),
                    'data_fim' => $dataBase->copy()->addWeeks(3)->setTime(20, 0),
                    'tipo' => 'prazo',
                    'cor' => '#ea580c',
                    'ativo' => true,
                ]
            );
        }

        EventoCalendario::updateOrCreate(
            [
                'titulo' => 'Prazo geral de requerimentos acadêmicos',
                'turma_id' => null,
                'curso_id' => null,
            ],
            [
                'descricao' => 'Evento geral da secretaria para lembrar alunos sobre datas de requerimentos, declarações e ajustes cadastrais.',
                'data_inicio' => now()->addDays(10)->setTime(9, 0),
                'data_fim' => now()->addDays(10)->setTime(17, 0),
                'tipo' => 'secretaria',
                'cor' => '#0f766e',
                'ativo' => true,
            ]
        );
    }
}
