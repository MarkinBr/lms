<?php

namespace App\Filament\Secretario\Resources\MatriculaSecretariaResource\Pages;

use App\Filament\Secretario\Resources\MatriculaSecretariaResource;
use App\Models\Mensalidade;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class CreateMatriculaSecretaria extends CreateRecord
{
    protected static string $resource = MatriculaSecretariaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Usaremos notificação customizada no afterCreate
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Gera número de matrícula automático se não fornecido
        if (empty($data['numero_matricula'])) {
            $data['numero_matricula'] = $this->gerarNumeroMatricula();
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $mensalidadesCriadas = 0;
        
        // Gerar mensalidades automaticamente se houver turma
        if ($record->turma) {
            $turma = $record->turma;
            $valorMensalidade = $turma->valor_total_curso / $turma->qtd_parcelas;
            
            // Primeira mensalidade: próximo mês no dia do vencimento configurado
            $primeiraMensalidade = now()->addMonth()->day($record->dia_vencimento);
            
            for ($i = 0; $i < $turma->qtd_parcelas; $i++) {
                $dataVencimento = $primeiraMensalidade->copy()->addMonths($i);
                
                // Define status baseado na data
                $status = 'Pendente';
                if ($dataVencimento->isPast()) {
                    $status = 'Atrasado';
                }
                
                Mensalidade::create([
                    'matricula_id' => $record->id,
                    'valor' => $valorMensalidade,
                    'data_vencimento' => $dataVencimento,
                    'status' => $status,
                    'observacao' => 'Parcela ' . ($i + 1) . '/' . $turma->qtd_parcelas,
                ]);
                
                $mensalidadesCriadas++;
            }
        }
        
        // Notificação de sucesso detalhada
        $mensagemSucesso = "✅ Matrícula criada com sucesso!\n\n";
        $mensagemSucesso .= "📋 Nº: {$record->numero_matricula}\n";
        $mensagemSucesso .= "👤 Aluno: {$record->user->name}\n";
        $mensagemSucesso .= "🎓 Curso: {$record->curso->titulo}\n";
        
        if ($mensalidadesCriadas > 0) {
            $mensagemSucesso .= "\n💰 {$mensalidadesCriadas} mensalidades geradas automaticamente";
        }
        
        Notification::make()
            ->title('✅ Matrícula Criada!')
            ->body($mensagemSucesso)
            ->success()
            ->duration(8000)
            ->send();
    }
    
    /**
     * Gera número de matrícula no formato: AAAAMMDDHHMM
     * Exemplo: 202601081530 (2026-01-08 15:30)
     */
    private function gerarNumeroMatricula(): string
    {
        $ano = date('Y');
        $mes = date('m');
        $dia = date('d');
        $hora = date('H');
        $minuto = date('i');
        
        // Formato: AAAA MM DD HH MM
        return "{$ano}{$mes}{$dia}{$hora}{$minuto}";
    }
}