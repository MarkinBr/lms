<?php

namespace App\Helpers;

use App\Models\CategoriaFinanceira;
use App\Models\Conta;
use App\Models\Mensalidade;
use App\Models\MovimentacaoFinanceira;
use Illuminate\Support\Facades\Log;

class FinanceiroHelper
{
    /**
     * Registra entrada de multa/juros como movimentação separada da mensalidade.
     * Isso permite ao gestor ver no relatório quanto recebeu de multa.
     *
     * Chamado AUTOMATICAMENTE após `marcarComoPaga()` quando há diferença
     * entre valor recebido e valor nominal da mensalidade.
     *
     * @return MovimentacaoFinanceira|null A movimentação criada (ou null se não houve multa)
     */
    public static function registrarMultaJuros(
        Mensalidade $mensalidade,
        float $valorMulta
    ): ?MovimentacaoFinanceira {
        if ($valorMulta <= 0.01) {
            return null;
        }

        $conta = self::resolverConta($mensalidade->metodo_pagamento);
        $categoria = CategoriaFinanceira::firstWhere('nome', config('financeiro.categoria_multas_juros'))
            ?? CategoriaFinanceira::firstOrCreate(
                ['nome' => config('financeiro.categoria_multas_juros')],
                ['tipo' => 'Receita', 'ativa' => true]
            );

        $mensalidade->loadMissing('matricula.user');
        $nomeAluno = $mensalidade->matricula?->user?->name ?? 'Aluno';

        $movimentacao = MovimentacaoFinanceira::create([
            'tipo'              => 'entrada',
            'conta_id'          => $conta->id,
            'categoria_id'      => $categoria->id,
            'mensalidade_id'    => $mensalidade->id,
            'descricao'         => "Multa/juros — mensalidade #{$mensalidade->id} ({$nomeAluno})",
            'valor'             => $valorMulta,
            'data_movimentacao' => $mensalidade->data_pagamento ?? now(),
            'status'            => 'efetivado',
            'pagador'           => $nomeAluno,
            'observacoes'       => sprintf(
                'Multa de R$ %s aplicada por atraso de %d dia(s). Valor base R$ %s.',
                number_format($valorMulta, 2, ',', '.'),
                $mensalidade->dias_atraso,
                number_format($mensalidade->valor, 2, ',', '.')
            ),
            'criado_por'        => auth()->id() ?? 1,
        ]);

        $movimentacao->registrarAuditoria(
            'criacao',
            null,
            'Multa/juros registrados automaticamente ao confirmar pagamento atrasado'
        );

        Log::info('Multa/juros registrados', [
            'mensalidade_id'  => $mensalidade->id,
            'movimentacao_id' => $movimentacao->id,
            'valor_multa'     => $valorMulta,
        ]);

        return $movimentacao;
    }

    /**
     * Resolve conta com base no método de pagamento.
     * Espelha lógica do MensalidadeObserver para consistência.
     */
    public static function resolverConta(?string $metodoPagamento): Conta
    {
        $metodo = strtolower(trim($metodoPagamento ?? 'dinheiro'));

        $banco = config('financeiro.metodos_banco', [
            'pix', 'mercadopago', 'boleto', 'bolbradesco',
            'cartao_credito', 'cartao_debito', 'transferencia',
        ]);

        $nomeConta = in_array($metodo, $banco, true)
            ? config('financeiro.conta_banco_padrao', 'Mercado Pago')
            : config('financeiro.conta_caixa_padrao', 'Caixa Secretaria');

        $tipoConta = in_array($metodo, $banco, true) ? 'Banco' : 'Caixa';

        $conta = Conta::whereRaw('LOWER(nome) = ?', [strtolower($nomeConta)])->first();

        if (!$conta) {
            $conta = Conta::create([
                'nome'          => $nomeConta,
                'tipo'          => $tipoConta,
                'saldo_inicial' => 0,
                'ativa'         => true,
            ]);
        }

        return $conta;
    }

    /**
     * Formata valor em BRL.
     */
    public static function formatarBRL(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}