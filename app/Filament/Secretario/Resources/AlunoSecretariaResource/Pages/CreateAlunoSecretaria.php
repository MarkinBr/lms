<?php

namespace App\Filament\Secretario\Resources\AlunoSecretariaResource\Pages;

use App\Filament\Secretario\Resources\AlunoSecretariaResource;
use App\Models\Matricula;
use App\Models\Mensalidade;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAlunoSecretaria extends CreateRecord
{
    protected static string $resource = AlunoSecretariaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $aluno = $this->record;
        $data = $this->form->getRawState();

        // Verificar se há dados de matrícula para criar
        $deveCriarMatricula = ! empty($data['criar_matricula']) &&
                              ! empty($data['curso_id_matricula']) &&
                              ! empty($data['turma_id_matricula']);

        if ($deveCriarMatricula) {
            try {
                DB::beginTransaction();

                // 1️⃣ CRIAR MATRÍCULA
                $ano = date('Y');
                $ultimaMatricula = Matricula::whereYear('created_at', $ano)->max('id') ?? 0;
                $numeroMatricula = $ano.'-'.str_pad($ultimaMatricula + 1, 4, '0', STR_PAD_LEFT);

                $matricula = Matricula::create([
                    'user_id' => $aluno->id,
                    'curso_id' => $data['curso_id_matricula'],
                    'turma_id' => $data['turma_id_matricula'],
                    'numero_matricula' => $numeroMatricula,
                    'data_matricula' => $data['data_matricula_nova'] ?? now(),
                    'dia_vencimento' => $data['dia_vencimento_nova'] ?? 10,
                    'status' => Matricula::STATUS_ATIVO,
                ]);

                // 2️⃣ GERAR MENSALIDADES (se solicitado)
                if (! empty($data['gerar_mensalidades'])) {
                    $valorMensalidade = $data['valor_mensalidade_nova'];
                    $qtdParcelas = $data['qtd_parcelas_nova'] ?? 12;
                    $primeiraMensalidade = ! empty($data['primeira_mensalidade_nova'])
                        ? Carbon::parse($data['primeira_mensalidade_nova'])
                        : now()->addMonth()->day($data['dia_vencimento_nova'] ?? 10);

                    $mensalidadesCriadas = 0;

                    for ($i = 0; $i < $qtdParcelas; $i++) {
                        $dataVencimento = $primeiraMensalidade->copy()->addMonths($i);

                        $status = $dataVencimento->isPast() ? 'Atrasado' : 'Pendente';

                        Mensalidade::create([
                            'matricula_id' => $matricula->id,
                            'valor' => $valorMensalidade,
                            'data_vencimento' => $dataVencimento,
                            'status' => $status,
                        ]);

                        $mensalidadesCriadas++;
                    }
                }

                DB::commit();

                // Notificação de sucesso
                Notification::make()
                    ->title('✅ Aluno cadastrado com sucesso!')
                    ->body("
                        👤 Aluno: {$aluno->name}
                        ".($deveCriarMatricula ? "📝 Matrícula: {$numeroMatricula}" : '').'
                        '.(! empty($mensalidadesCriadas) ? "💰 {$mensalidadesCriadas} mensalidades geradas" : '').'
                        🔑 Senha padrão: aluno@123
                    ')
                    ->success()
                    ->duration(8000)
                    ->send();

            } catch (\Exception $e) {
                DB::rollBack();

                Notification::make()
                    ->title('⚠️ Aluno criado, mas houve erro na matrícula')
                    ->body("Aluno cadastrado, mas erro ao criar matrícula: {$e->getMessage()}")
                    ->warning()
                    ->duration(8000)
                    ->send();
            }
        } else {
            // Apenas aluno, sem matrícula
            Notification::make()
                ->title('✅ Aluno cadastrado!')
                ->body("Aluno {$aluno->name} cadastrado. Senha padrão: aluno@123")
                ->success()
                ->duration(5000)
                ->send();
        }
    }
}
