<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Curso;
use Illuminate\Support\Facades\DB;

class ImportarCursos extends Command
{
    protected $signature = 'importar:cursos';
    protected $description = 'Importa turmas da tabela antiga para a tabela cursos';

    public function handle()
    {
        $turmas = DB::table('turmas')->get();

        foreach ($turmas as $turma) {
            $curso = Curso::firstOrCreate(
                ['titulo' => $turma->nome],
                [
                    'descricao' => "Curso importado. Ano: {$turma->ano_letivo}",
                    'valor' => $turma->valor_total_curso,
                    'ativo' => $turma->ativa,
                    // Mapeie outros campos se necessário
                ]
            );
            
            $this->info("Curso {$curso->titulo} importado/verificado.");

            // Atualiza a tabela de matrículas para apontar para o novo curso_id
            DB::table('matriculas')
                ->where('turma_id', $turma->id)
                ->update(['curso_id' => $curso->id]);
        }
    }
}