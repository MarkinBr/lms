<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Turma;
use App\Models\Matricula;
use App\Models\Mensalidade;
use App\Models\Conta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cria o Usuário Admin
        User::factory()->create([
            'name' => 'Marcos Andre',
            'email' => 'admin@cets.com',
            'password' => Hash::make('12345678'),
            'nivel' => 'admin',
            'ativo' => true,
        ]);

        // 2. Contas Financeiras
        Conta::create([
            'nome' => 'Mercado Pago',
            'saldo_inicial' => 50.00,
            'tipo' => 'Banco',
            'ativa' => true,
        ]);

        Conta::create([
            'nome' => 'Caixa Secretaria',
            'saldo_inicial' => 50.00,
            'tipo' => 'Caixa',
            'ativa' => true,
        ]);

        // 3. Alunos (Agora usando a tabela de Usuários)
        $alunos = User::factory(20)->create([
            'nivel' => 'aluno',
            'ativo' => true,
        ]);

        // 4. Turmas
        $turmaDiaconato = Turma::create([
            'nome' => 'Habilitação ao Diaconato',
            'ano_letivo' => 2025,
            'turno' => 'Noturno',
            'valor_total_curso' => 300.00, 
            'qtd_parcelas' => 6,           
            'data_inicio' => '2025-02-01',
            'data_fim' => '2025-07-01',
        ]);

        $turmaLideranca = Turma::create([
            'nome' => 'Liderança Cristã Ministerial',
            'ano_letivo' => 2025,
            'turno' => 'Sábado',
            'valor_total_curso' => 840.00,
            'qtd_parcelas' => 12,
            'data_inicio' => '2025-02-01',
            'data_fim' => '2025-12-01',
        ]);

        $turmaMedio = Turma::create([
            'nome' => 'Médio em Teologia',
            'ano_letivo' => 2025,
            'turno' => 'Online',
            'valor_total_curso' => 2400.00,
            'qtd_parcelas' => 24,
            'data_inicio' => '2025-02-01',
            'data_fim' => '2026-12-01',
        ]);

        $todasTurmas = collect([$turmaDiaconato, $turmaLideranca, $turmaMedio]);

        // 5. Eventos do calendário acadêmico
        $this->call(EventoCalendarioSeeder::class);

        // 6. Matrículas e Financeiro Inteligente
        foreach ($alunos as $aluno) {
            
            $qtdCursos = (rand(1, 100) <= 30) ? 2 : 1;
            $turmasEscolhidas = $todasTurmas->random($qtdCursos);

            foreach ($turmasEscolhidas as $turma) {
                
                $matricula = Matricula::create([
                    'user_id' => $aluno->id, // Corrigido para user_id
                    'turma_id' => $turma->id,
                    'data_matricula' => $turma->data_inicio,
                    'dia_vencimento' => 10,
                    'status' => 'Ativa',
                ]);

                $valorParcela = $turma->valor_total_curso / $turma->qtd_parcelas;
                
                $dataInicioTurma = Carbon::parse($turma->data_inicio);

                for ($i = 0; $i < $turma->qtd_parcelas; $i++) {
                    
                    $vencimento = $dataInicioTurma->copy()->addMonths($i + 1)->setDay($matricula->dia_vencimento);
                    
                    $status = 'Pendente';
                    $dataPagamento = null;
                    $metodoPagamento = null;

                    if ($vencimento->isPast()) {
                        
                        $pagou = rand(1, 100) <= 80;

                        if ($pagou) {
                            $status = 'Pago';
                            $dataPagamento = $vencimento->copy()->addDays(rand(0, 5));
                            $metodoPagamento = (rand(1, 2) == 1) ? 'pix' : 'boleto';
                        } else {
                            $status = 'Atrasado';
                        }

                    } else {
                        $status = 'Pendente';
                    }

                    Mensalidade::create([
                        'matricula_id' => $matricula->id,
                        'valor' => $valorParcela,
                        'data_vencimento' => $vencimento,
                        'status' => $status,
                        'data_pagamento' => $dataPagamento,
                        'metodo_pagamento' => $metodoPagamento,
                        'observacao' => 'Parcela ' . ($i + 1) . '/' . $turma->qtd_parcelas,
                    ]);
                }
            }
        }
    }
}