<?php

namespace App\Filament\Secretario\Resources\CadastroRapidoResource\Pages;

use App\Filament\Secretario\Resources\CadastroRapidoResource;
use App\Models\User;
use App\Models\Matricula;
use App\Models\Mensalidade;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreateCadastroRapido extends CreateRecord
{
    protected static string $resource = CadastroRapidoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove dados que não são do aluno
        $alunoData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf' => $data['cpf'],
            'telefone' => $data['telefone'],
            'data_nasc' => $data['data_nasc'] ?? null,
            'foto' => $data['foto'] ?? null,
            'documentos' => $data['documentos'] ?? null,
            
            // Endereço
            'cep' => $data['cep'] ?? null,
            'endereco' => $data['endereco'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade' => $data['cidade'] ?? null,
            'estado' => $data['estado'] ?? null,
            
            // Responsável
            'responsavel_nome' => $data['responsavel_nome'] ?? null,
            'responsavel_telefone' => $data['responsavel_telefone'] ?? null,
            'observacoes' => $data['observacoes_aluno'] ?? null,
            
            // Sistema
            'nivel' => 'aluno',
            'password' => bcrypt('aluno@123'),
            'ativo' => true,
        ];

        return $alunoData;
    }

    protected function afterCreate(): void
    {
        $aluno = $this->record;
        $data = $this->form->getRawState();

        try {
            DB::beginTransaction();

            // 1️⃣ CRIAR MATRÍCULA
            $ano = date('Y');
            $ultimaMatricula = Matricula::whereYear('created_at', $ano)->max('id') ?? 0;
            $numeroMatricula = $ano . '-' . str_pad($ultimaMatricula + 1, 4, '0', STR_PAD_LEFT);

            $matricula = Matricula::create([
                'user_id' => $aluno->id,
                'curso_id' => $data['curso_id'],
                'turma_id' => $data['turma_id'],
                'numero_matricula' => $numeroMatricula,
                'data_matricula' => $data['data_matricula'] ?? now(),
                'dia_vencimento' => $data['dia_vencimento'] ?? 10,
                'status' => $data['status_matricula'] ?? Matricula::STATUS_ATIVO,
                'observacoes' => $data['observacoes_matricula'] ?? null,
            ]);

            // 2️⃣ GERAR MENSALIDADES
            $valorMensalidade = $data['valor_mensalidade'];
            $qtdParcelas = $data['qtd_parcelas'];
            $primeiraMensalidade = Carbon::parse($data['primeira_mensalidade']);
            $gerarTodasPendentes = $data['gerar_todas_pendentes'] ?? true;

            $mensalidadesCriadas = 0;

            for ($i = 0; $i < $qtdParcelas; $i++) {
                $dataVencimento = $primeiraMensalidade->copy()->addMonths($i);
                
                // Define o status baseado na data e opção do usuário
                $status = 'Pendente';
                if (!$gerarTodasPendentes && $dataVencimento->isPast()) {
                    $status = 'Atrasado';
                }

                Mensalidade::create([
                    'matricula_id' => $matricula->id,
                    'valor' => $valorMensalidade,
                    'data_vencimento' => $dataVencimento,
                    'status' => $status,
                    'observacao' => 'Gerada automaticamente no cadastro rápido',
                ]);

                $mensalidadesCriadas++;
            }

            DB::commit();

            // 3️⃣ CONTABILIZAR DOCUMENTOS
            $documentos = $data['documentos'] ?? [];
            $totalDocs = count($documentos);
            $docsEnviados = collect($documentos)->filter(function ($doc) {
                $arquivo = $doc['arquivo'] ?? null;
                if (is_array($arquivo)) $arquivo = array_values($arquivo)[0] ?? null;
                return !empty($arquivo);
            })->count();

            // 4️⃣ NOTIFICAÇÃO DE SUCESSO DETALHADA
            $mensagemSucesso = "
                ✅ **Aluno Cadastrado com Sucesso!**
                
                👤 **Aluno:** {$aluno->name}
                📧 **Email:** {$aluno->email}
                📱 **Telefone:** {$aluno->telefone}
                
                📋 **Matrícula:** {$numeroMatricula}
                🎓 **Curso:** " . ($matricula->curso->titulo ?? 'N/A') . "
                👥 **Turma:** " . ($matricula->turma->nome ?? 'N/A') . "
                
                💰 **{$mensalidadesCriadas} mensalidades** de R$ " . number_format($valorMensalidade, 2, ',', '.') . " geradas
                📄 **{$docsEnviados}/{$totalDocs} documentos** enviados
                
                🔐 **Senha padrão:** aluno@123
                ⚠️ **Oriente o aluno a trocar a senha no primeiro acesso**
            ";

            Notification::make()
                ->title('✅ Cadastro Completo!')
                ->body($mensagemSucesso)
                ->success()
                ->duration(10000)
                ->send();

            // Notificação adicional se faltarem documentos
            if ($docsEnviados < $totalDocs) {
                Notification::make()
                    ->title('📄 Documentos Pendentes')
                    ->body("Ainda faltam " . ($totalDocs - $docsEnviados) . " documento(s) para serem enviados. Você pode completá-los editando o cadastro do aluno.")
                    ->warning()
                    ->duration(8000)
                    ->send();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('❌ Erro no Cadastro')
                ->body("Erro: {$e->getMessage()}")
                ->danger()
                ->duration(10000)
                ->send();
            
            throw $e;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Usamos notificações customizadas no afterCreate
    }

    /**
     * Remove os botões padrão do Filament: "Criar", "Salvar e criar outro" e "Cancelar".
     * O botão de submissão já está dentro do submitAction do Wizard (✅ Finalizar Cadastro).
     */
    protected function getFormActions(): array
    {
        return [];
    }
}