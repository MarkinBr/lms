<?php

namespace App\Observers;

use App\Models\CategoriaFinanceira;
use App\Models\Conta;
use App\Models\Mensalidade;
use App\Models\MovimentacaoFinanceira;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MensalidadeObserver
{
    /**
     * Métodos de pagamento que vão para a conta "Mercado Pago" / Banco.
     * Centralizado em constante para evitar drift entre métodos.
     */
    private const METODOS_BANCO = [
        'pix',
        'mercadopago',
        'boleto',
        'bolbradesco', // BUG-A16: aceita ambas as grafias
        'cartao_credito',
        'cartao_debito',
        'transferencia',
    ];

    // ═════════════════════════════════════════════════════════════════════
    // EVENTO: created — BUG-C12
    // Trata mensalidade que JÁ nasce com status='Pago'
    // (ex: importação histórica, cadastro retroativo)
    // ═════════════════════════════════════════════════════════════════════
    public function created(Mensalidade $mensalidade): void
    {
        if ($mensalidade->status === 'Pago' && $mensalidade->data_pagamento) {
            DB::transaction(function () use ($mensalidade) {
                $this->registrarEntrada($mensalidade);
            });

            Log::info('Mensalidade criada já como Paga — movimentação registrada', [
                'mensalidade_id' => $mensalidade->id,
            ]);
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    // EVENTO: updated
    // Trata as três transições críticas:
    //   1) Tornou-se Pago → criar/atualizar/reativar movimentação
    //   2) Saiu de Pago (Pago → Pendente/Atrasado) → estornar (BUG-C09)
    //   3) Tornou-se Cancelado → cancelar movimentação
    // ═════════════════════════════════════════════════════════════════════
    public function updated(Mensalidade $mensalidade): void
    {
        $statusAnterior = $mensalidade->getOriginal('status');
        $statusAtual    = $mensalidade->status;

        // Se status não mudou e nem o valor, não há nada a fazer
        $statusMudou = $statusAnterior !== $statusAtual;
        $valorMudou  = $mensalidade->wasChanged(['valor', 'valor_pago', 'data_pagamento', 'metodo_pagamento']);

        if (!$statusMudou && !$valorMudou) {
            return;
        }

        DB::transaction(function () use ($mensalidade, $statusAnterior, $statusAtual) {

            // ─────────────────────────────────────────────────────────
            // CASO 1: Tornou-se Pago (ou continua Pago com alteração)
            // ─────────────────────────────────────────────────────────
            if ($statusAtual === 'Pago' && $mensalidade->data_pagamento) {
                $mov = MovimentacaoFinanceira::where('mensalidade_id', $mensalidade->id)
                    ->withTrashed()
                    ->first();

                if (!$mov) {
                    // Nunca houve movimentação → criar
                    $this->registrarEntrada($mensalidade);
                } else {
                    // Existia → atualizar
                    // BUG-C09 (parte 2): se estava cancelada, re-efetivar
                    if ($mov->status === 'cancelado') {
                        $dadosAnt = $mov->toArray();
                        $mov->update(['status' => 'efetivado']);
                        $mov->registrarAuditoria(
                            'reativacao',
                            $dadosAnt,
                            "Movimentação re-efetivada: mensalidade voltou ao status Pago"
                        );

                        Log::info('Movimentação re-efetivada', [
                            'mensalidade_id'  => $mensalidade->id,
                            'movimentacao_id' => $mov->id,
                        ]);
                    }

                    $this->atualizarEntrada($mov, $mensalidade);
                }
                return;
            }

            // ─────────────────────────────────────────────────────────
            // CASO 2 (NOVO — BUG-C09): Saiu de Pago → estornar
            // ─────────────────────────────────────────────────────────
            if ($statusAnterior === 'Pago' && $statusAtual !== 'Pago') {
                $mov = MovimentacaoFinanceira::where('mensalidade_id', $mensalidade->id)
                    ->where('status', 'efetivado')
                    ->first();

                if ($mov) {
                    $dadosAnt = $mov->toArray();
                    $mov->update([
                        'status'         => 'cancelado',
                        'alterado_por'   => auth()->id() ?? 1,
                        'data_alteracao' => now(),
                    ]);
                    $mov->registrarAuditoria(
                        'estorno',
                        $dadosAnt,
                        "Estornado automaticamente: mensalidade revertida de Pago para {$statusAtual}"
                    );

                    Log::warning('Movimentação estornada por reversão de mensalidade', [
                        'mensalidade_id'  => $mensalidade->id,
                        'movimentacao_id' => $mov->id,
                        'novo_status'     => $statusAtual,
                        'usuario'         => auth()->id() ?? 'sistema',
                    ]);
                }
                return;
            }

            // ─────────────────────────────────────────────────────────
            // CASO 3: Cancelado (sem ter sido Pago antes)
            // ─────────────────────────────────────────────────────────
            if ($statusAtual === 'Cancelado') {
                $mov = MovimentacaoFinanceira::where('mensalidade_id', $mensalidade->id)
                    ->where('status', '!=', 'cancelado')
                    ->first();

                if ($mov) {
                    $dadosAnt = $mov->toArray();
                    $mov->update(['status' => 'cancelado']);
                    $mov->registrarAuditoria(
                        'cancelamento',
                        $dadosAnt,
                        'Mensalidade cancelada'
                    );
                }
            }
        });
    }

    // ═════════════════════════════════════════════════════════════════════
    // CRIAÇÃO DE MOVIMENTAÇÃO FINANCEIRA
    // ═════════════════════════════════════════════════════════════════════
    private function registrarEntrada(Mensalidade $mensalidade): void
    {
        // Eager-load defensivo para evitar N+1 + null pointer
        $mensalidade->loadMissing(['matricula.user', 'matricula.curso']);

        $conta     = $this->determinarConta($mensalidade->metodo_pagamento);
        $categoria = $this->obterCategoriaMensalidades();

        $nomeAluno = $mensalidade->matricula?->user?->name ?? 'Aluno desconhecido';
        $nomeCurso = $mensalidade->matricula?->curso?->titulo ?? 'Curso N/A';

        // BUG-A07: usa valor_pago se existir (inclui multa/juros);
        // senão usa o valor nominal da mensalidade
        $valor = $mensalidade->valor_pago ?? $mensalidade->valor;

        $movimentacao = MovimentacaoFinanceira::create([
            'tipo'              => 'entrada',
            'conta_id'          => $conta->id,
            'categoria_id'      => $categoria?->id,
            'mensalidade_id'    => $mensalidade->id,
            'descricao'         => "Pagamento de mensalidade — {$nomeAluno} — {$nomeCurso}",
            'valor'             => $valor,
            'data_movimentacao' => $mensalidade->data_pagamento,
            'status'            => 'efetivado',
            'pagador'           => $nomeAluno,
            'observacoes'       => $mensalidade->observacao,
            'criado_por'        => auth()->id() ?? 1, // 1 = Sistema (seeded)
        ]);

        $movimentacao->registrarAuditoria(
            'criacao',
            null,
            'Criado automaticamente pelo sistema ao registrar pagamento de mensalidade'
        );

        Log::info('Movimentação financeira registrada', [
            'mensalidade_id'  => $mensalidade->id,
            'movimentacao_id' => $movimentacao->id,
            'valor'           => $valor,
            'conta'           => $conta->nome,
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════
    // ATUALIZAÇÃO DE MOVIMENTAÇÃO FINANCEIRA
    // ═════════════════════════════════════════════════════════════════════
    private function atualizarEntrada(MovimentacaoFinanceira $mov, Mensalidade $mensalidade): void
    {
        $dadosAnt = $mov->toArray();

        $conta = $this->determinarConta($mensalidade->metodo_pagamento);
        $valor = $mensalidade->valor_pago ?? $mensalidade->valor;

        $mov->update([
            'conta_id'          => $conta->id,
            'valor'             => $valor,
            'data_movimentacao' => $mensalidade->data_pagamento,
            'observacoes'       => $mensalidade->observacao,
            'alterado_por'      => auth()->id() ?? 1,
            'data_alteracao'    => now(),
        ]);

        // Só registra auditoria se realmente houve mudança
        $mudancas = array_diff_assoc(
            $mov->only(['conta_id', 'valor', 'data_movimentacao', 'observacoes']),
            collect($dadosAnt)->only(['conta_id', 'valor', 'data_movimentacao', 'observacoes'])->toArray()
        );

        if (!empty($mudancas)) {
            $mov->registrarAuditoria(
                'edicao',
                $dadosAnt,
                'Atualizado após alteração na mensalidade'
            );
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    // RESOLUÇÃO DE CONTA (BUG-M06: case-insensitive)
    // ═════════════════════════════════════════════════════════════════════
    private function determinarConta(?string $metodoPagamento): Conta
    {
        // BUG-A16: normaliza método (trim + lowercase)
        $metodo = strtolower(trim($metodoPagamento ?? 'dinheiro'));

        if (in_array($metodo, self::METODOS_BANCO, true)) {
            $nomeConta = 'Mercado Pago';
            $tipoConta = 'Banco';
        } else {
            $nomeConta = 'Caixa Secretaria';
            $tipoConta = 'Caixa';
        }

        // BUG-M06: busca case-insensitive para evitar duplicação
        $conta = Conta::whereRaw('LOWER(nome) = ?', [strtolower($nomeConta)])->first();

        if (!$conta) {
            $conta = Conta::create([
                'nome'          => $nomeConta,
                'tipo'          => $tipoConta,
                'saldo_inicial' => 0,
                'ativa'         => true,
            ]);

            Log::warning("Conta '{$nomeConta}' criada automaticamente pelo Observer", [
                'metodo_pagamento_origem' => $metodoPagamento,
                'recomendacao'            => 'Configure as contas manualmente em /admin/contas',
            ]);
        }

        return $conta;
    }

    // ═════════════════════════════════════════════════════════════════════
    // RESOLUÇÃO DE CATEGORIA (firstOrCreate para resilência)
    // ═════════════════════════════════════════════════════════════════════
    private function obterCategoriaMensalidades(): ?CategoriaFinanceira
    {
        return CategoriaFinanceira::firstOrCreate(
            ['nome' => 'Mensalidades'],
            [
                'tipo'  => 'Receita',
                'ativa' => true,
            ]
        );
    }
}
