<?php

namespace App\Helpers;

class ErroMpHelper
{
    public static function sanitizar(\Throwable $e): string
    {
        $msg = $e->getMessage();

        $mapeamento = [
            'cpf'               => 'Há um problema com o CPF cadastrado. Entre em contato com a secretaria.',
            'email'             => 'Há um problema com o e-mail cadastrado. Atualize seu perfil.',
            'endereco'          => 'Há um problema com o endereço cadastrado. Atualize seu perfil.',
            'cep'               => 'Há um problema com o CEP cadastrado. Atualize seu perfil.',
            'rejected'          => 'Pagamento recusado pelo gateway. Tente novamente em alguns minutos.',
            'invalid_collector' => 'Configuração de pagamento indisponível. Aguarde — equipe foi notificada.',
            'idempotency'       => 'Pagamento já processado recentemente. Verifique seu histórico.',
            'connection'        => 'Falha de conexão com o gateway. Tente novamente em instantes.',
        ];

        foreach ($mapeamento as $palavra => $mensagemAmigavel) {
            if (stripos($msg, $palavra) !== false) {
                return $mensagemAmigavel;
            }
        }

        return 'Não foi possível processar o pagamento agora. Aguarde alguns minutos e tente novamente. ' .
               'Se persistir, entre em contato com a secretaria.';
    }
}


// ═════════════════════════════════════════════════════════════════════════
// PATCH-I: trait com darBaixaManual() com lock pessimista — BUG-A23 (v1.1)
// Aplicar em FinanceiroController/Action que faz baixa manual
// ═════════════════════════════════════════════════════════════════════════

namespace App\Support;

use App\Models\Mensalidade;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait BaixaManualMensalidade
{
    /**
     * BUG-A23: baixa manual com lock pessimista contra corrida com webhook.
     *
     * Fluxo:
     *   - DB::transaction + lockForUpdate
     *   - se já está Paga, retorna already_paid
     *   - se está Cancelada, lança Exception
     *   - persiste pagamento via método dedicado do Model (registrarPagamentoManual)
     *
     * Pré-requisito: Patch 02 v1.1 com Mensalidade::registrarPagamentoManual()
     */
    protected function darBaixaManual(int $mensalidadeId, array $dados): array
    {
        $valorPago = isset($dados['valor_pago']) ? (float) $dados['valor_pago'] : null;
        $metodo    = (string) ($dados['metodo_pagamento'] ?? 'dinheiro');
        $dataPg    = $dados['data_pagamento'] ?? now(config('app.timezone', 'America/Sao_Paulo'));
        $obs       = $dados['observacao'] ?? null;

        return DB::transaction(function () use ($mensalidadeId, $valorPago, $metodo, $dataPg, $obs) {
            $m = Mensalidade::query()->whereKey($mensalidadeId)->lockForUpdate()->first();

            if (!$m) {
                throw new Exception('Mensalidade não encontrada.');
            }

            if ($m->status === 'Pago') {
                Log::info('Baixa manual ignorada — mensalidade já paga', [
                    'mensalidade_id' => $m->id,
                ]);

                return [
                    'success'     => true,
                    'already_paid' => true,
                    'mensalidade_id' => $m->id,
                ];
            }

            if ($m->status === 'Cancelado') {
                throw new Exception('Mensalidade cancelada — não permite baixa manual.');
            }

            if (method_exists($m, 'registrarPagamentoManual')) {
                $m->registrarPagamentoManual([
                    'valor_pago'      => $valorPago ?? (float) $m->valor,
                    'data_pagamento'  => $dataPg,
                    'metodo_pagamento'=> $metodo,
                    'observacao'      => $obs,
                ]);
            } else {
                $m->status = 'Pago';
                $m->valor_pago = $valorPago ?? (float) $m->valor;
                $m->data_pagamento = $dataPg;
                $m->metodo_pagamento = $metodo;
                if ($obs) {
                    $m->observacao = trim(($m->observacao ?? '') . ' ' . $obs);
                }
                $m->save();
            }

            Log::info('Baixa manual realizada', [
                'mensalidade_id' => $m->id,
                'metodo'         => $metodo,
                'valor_pago'     => $valorPago ?? (float) $m->valor,
            ]);

            return [
                'success'        => true,
                'already_paid'   => false,
                'mensalidade_id' => $m->id,
            ];
        });
    }
}