<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportarAlunos extends Command
{
    protected $signature = 'importar:alunos';
    protected $description = 'Importa alunos da tabela antiga para a tabela users';

    public function handle()
    {
        $alunos = DB::table('alunos')->get();

        foreach ($alunos as $aluno) {
            // Verifica se já existe um usuário com este email ou CPF
            $user = User::where('email', $aluno->email)
                        ->orWhere('cpf', $aluno->cpf)
                        ->first();

            if (!$user) {
                // Cria o usuário
                $senha = str_replace(['.', '-'], '', $aluno->cpf); // Senha padrão é o CPF sem pontuação
                
                $user = User::create([
                    'name' => $aluno->nome,
                    'email' => $aluno->email,
                    'password' => Hash::make($senha),
                    'cpf' => $aluno->cpf,
                    'telefone' => $aluno->telefone,
                    'data_nasc' => $aluno->data_nascimento,
                    'cep' => $aluno->cep,
                    'endereco' => $aluno->endereco,
                    // Mapeie outros campos conforme necessário
                    'nivel' => 'aluno', // Importante para o acesso
                    'ativo' => $aluno->ativo,
                ]);
                
                $this->info("Aluno {$aluno->nome} importado com sucesso.");
            } else {
                $this->info("Aluno {$aluno->nome} já existe como usuário.");
            }
            
            // Atualiza a tabela de matrículas para apontar para o novo user_id
            // Isso assume que você já rodou a migration que adiciona user_id na tabela matriculas
            DB::table('matriculas')
                ->where('aluno_id', $aluno->id)
                ->update(['user_id' => $user->id]);
        }
    }
}