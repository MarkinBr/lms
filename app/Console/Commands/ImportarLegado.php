<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Curso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportarLegado extends Command
{
    protected $signature = 'cets:importar';
    protected $description = 'Importa alunos e turmas do banco legado para o novo sistema de usuários e cursos';

    public function handle()
    {
        $this->info('Iniciando importação...');

        // 1. Converter Turmas em Cursos
        $this->info('Convertendo Turmas em Cursos...');
        $turmas = DB::table('turmas')->get();
        $mapaTurmaCurso = [];

        foreach ($turmas as $turma) {
            // Verifica se já existe um curso com esse nome
            $curso = Curso::firstOrCreate(
                ['titulo' => $turma->nome],
                [
                    'descricao' => "Curso importado da turma {$turma->ano_letivo} - {$turma->turno}",
                    'valor' => $turma->valor_total_curso,
                    'ativo' => $turma->ativa,
                    'duracao_total' => 0 // Ajuste depois
                ]
            );
            
            $mapaTurmaCurso[$turma->id] = $curso->id;
            $this->info("Turma '{$turma->nome}' vinculada ao Curso ID: {$curso->id}");
        }

        // 2. Converter Alunos em Users
        $this->info('Convertendo Alunos em Usuários...');
        $alunos = DB::table('alunos')->get();
        
        foreach ($alunos as $aluno) {
            // Limpa o CPF para usar como senha
            $cpfLimpo = preg_replace('/[^0-9]/', '', $aluno->cpf);
            $senha = $cpfLimpo ?: '12345678'; // Se não tiver CPF, usa senha padrão

            // Cria ou atualiza o usuário baseado no email ou CPF
            $user = User::updateOrCreate(
                ['email' => $aluno->email], // Busca por email
                [
                    'name' => $aluno->nome,
                    'password' => Hash::make($senha), // Senha é o CPF sem pontos
                    'nivel' => 'aluno', // Define como ALUNO
                    'ativo' => $aluno->ativo,
                    'cpf' => $aluno->cpf,
                    'telefone' => $aluno->telefone,
                    'data_nasc' => $aluno->data_nascimento,
                    'endereco' => $aluno->endereco,
                    'bairro' => $aluno->bairro,
                    'cidade' => $aluno->cidade,
                    'estado' => $aluno->estado,
                    'cep' => $aluno->cep,
                ]
            );

            // 3. Atualizar as Matrículas desse aluno
            if (isset($user->id)) {
                // Busca matrículas antigas deste aluno
                $matriculasAntigas = DB::table('matriculas')
                    ->where('aluno_id', $aluno->id)
                    ->get();

                foreach ($matriculasAntigas as $matricula) {
                    if (isset($mapaTurmaCurso[$matricula->turma_id])) {
                        DB::table('matriculas')
                            ->where('id', $matricula->id)
                            ->update([
                                'user_id' => $user->id,
                                'curso_id' => $mapaTurmaCurso[$matricula->turma_id],
                                'status' => strtolower($matricula->status) == 'ativa' ? 'ativo' : 'cancelado'
                            ]);
                    }
                }
                
                // Atualizar Financeiro e Notas também
                DB::table('financeiro')->where('aluno_id', $aluno->id)->update(['aluno_id' => $user->id]);
                DB::table('notas')->where('aluno_id', $aluno->id)->update(['aluno_id' => $user->id]);
            }

            $this->info("Aluno '{$aluno->nome}' migrado. Senha: {$cpfLimpo}");
        }

        $this->info('Importação concluída com sucesso!');
    }
}