<?php

namespace App\Http\Controllers;

use App\Models\Mensalidade;
use App\Models\MovimentacaoFinanceira;
use App\Models\TransacaoTeste;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(
        private readonly MercadoPagoService $mpService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | ENDPOINT PRINCIPAL — POST /api/webhook/mercadopago
    |--------------------------------------------------------------------------
    */

    public function mercadoPago(Request $request): JsonResponse
    {
        $requestId = $request->header('x-request-id', uniqid('rid_', true));

        // ─────────────────────────────────────────────────────────────────
        // FASE 0: Validação básica de payload
        // ─────────────────────────────────────────────────────────────────
        $type   = $request->input('type', $request->input('topic', ''));
        $dataId = (string) $request->input('data.id', $request->input('id', ''));

        // Eventos sem dados úteis: ack 200 (não retornar 404 → BUG-A05)
        if ($type !== 'payment' || $dataId === '') {
            Log::info('[Webhook MP] Evento ignorado (não-payment ou sem id)', [
                'type' => $type,
                'request_id' => $requestId,
            ]);
            return response()->json(['status' => 'ignored_non_payment'], 200);
        }

        // ─────────────────────────────────────────────────────────────────
        // FASE 1: BUG-C02 — Validação HMAC + anti-replay
        // ─────────────────────────────────────────────────────────────────
        $assinaturaResult = $this->validarAssinatura($request, $dataId, $requestId);
        if ($assinaturaResult !== true) {
            // Em PROD: rejeita; em DEV (sem secret): apenas loga e prossegue.
            if ($assinaturaResult === 'forbidden') {
                return response()->json(['status' => 'forbidden_signature'], 401);
            }
            // 'permissive_dev' — continua o fluxo
        }

        // ─────────────────────────────────────────────────────────────────
        // FASE 2: BUG-C13 — Idempotência (registra evento ANTES de processar)
        //
        // Estratégia: insertOrIgnore na tabela webhook_eventos. Se o INSERT
        // não criou registro (constraint UNIQUE atingida), o evento já foi
        // processado antes — devolvemos 200 sem reprocessar.
        // ─────────────────────────────────────────────────────────────────
        try {
            $inserido = DB::table('webhook_eventos')->insertOrIgnore([
                'provedor'      => 'mercadopago',
                'evento_id'     => $dataId,
                'tipo'          => $type,
                'status_mp'     => null, // será preenchido após consultar a API
                'payload'       => json_encode($request->all()),
                'processado_em' => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } catch (Throwable $e) {
            // Tabela ainda não criada? Loga e prossegue (degradação graciosa)
            Log::error('[Webhook MP] Tabela webhook_eventos indisponível', [
                'erro' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
            $inserido = 1; // segue como se fosse novo
        }

        if (!$inserido) {
            Log::info('[Webhook MP] Evento duplicado — ignorado por idempotência', [
                'evento_id' => $dataId,
                'request_id' => $requestId,
            ]);
            return response()->json(['status' => 'duplicate_ignored'], 200);
        }

        // ─────────────────────────────────────────────────────────────────
        // FASE 3: Consultar a API do MP para confirmar o status real
        //
        // (Confiar apenas no payload do webhook é arriscado — o MP recomenda
        //  consultar /v1/payments/{id} para obter o estado autoritativo.)
        // ─────────────────────────────────────────────────────────────────
        try {
            $dadosPagamento = $this->mpService->consultarPagamento($dataId);
        } catch (Throwable $e) {
            Log::error('[Webhook MP] Falha ao consultar pagamento', [
                'evento_id' => $dataId,
                'erro' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
            // Retorna 200 mesmo com falha — MP não deve retentar.
            // Job de reconciliação posterior trata pendências.
            return response()->json(['status' => 'consult_failed_will_reconcile'], 200);
        }

        if (!is_array($dadosPagamento) || empty($dadosPagamento)) {
            Log::notice('[Webhook MP] Pagamento desconhecido — ignorado', [
                'evento_id' => $dataId,
                'request_id' => $requestId,
            ]);
            // BUG-A05: jamais 404 — sempre 200 para o MP parar de retentar
            return response()->json(['status' => 'ignored_unknown_payment'], 200);
        }

        $statusMP = (string) ($dadosPagamento['status'] ?? '');

        // Atualiza o status_mp na tabela de auditoria
        try {
            DB::table('webhook_eventos')
                ->where('provedor', 'mercadopago')
                ->where('evento_id', $dataId)
                ->update(['status_mp' => $statusMP, 'updated_at' => now()]);
        } catch (Throwable $e) {
            // ignore — não-crítico
        }

        // ─────────────────────────────────────────────────────────────────
        // FASE 4: Localizar a Mensalidade vinculada (BUG-A23: lockForUpdate)
        // ─────────────────────────────────────────────────────────────────
        return DB::transaction(function () use ($dataId, $statusMP, $dadosPagamento, $requestId) {

            // Tenta achar a Mensalidade por transacao_id (com lock)
            $mensalidade = Mensalidade::where('transacao_id', $dataId)
                ->lockForUpdate()
                ->first();

            if ($mensalidade) {
                return $this->processarMensalidade($mensalidade, $statusMP, $dadosPagamento, $requestId);
            }

            // Fallback: tabela legacy de testes
            return $this->processarTransacaoTeste($dataId, $statusMP, $dadosPagamento, $requestId);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | PROCESSAMENTO DE MENSALIDADE — BUG-A19 (refund + chargeback)
    |--------------------------------------------------------------------------
    */

    private function processarMensalidade(
        Mensalidade $m,
        string $statusMP,
        array $dadosPagamento,
        string $requestId
    ): JsonResponse {

        // BUG-A23: Idempotência local — se já está no estado final, no-op.
        if ($m->estaPaga() && in_array($statusMP, ['approved'], true)) {
            return response()->json(['status' => 'already_paid_noop'], 200);
        }

        $valorRecebido = (float) ($dadosPagamento['transaction_amount'] ?? $m->valor);

        // ───────────────── APPROVED → marcar como pago ─────────────────
        if ($statusMP === 'approved') {
            $m->marcarComoPaga(
                dataPagamento:   isset($dadosPagamento['date_approved'])
                                 ? \Illuminate\Support\Carbon::parse($dadosPagamento['date_approved'])
                                 : now(),
                metodoPagamento: $this->canonicalMetodo($dadosPagamento['payment_method_id'] ?? $m->metodo_pagamento),
                valorPago:       $valorRecebido,
            );

            Log::info('[Webhook MP] Mensalidade marcada como Paga', [
                'mensalidade_id' => $m->id,
                'evento_id' => $dadosPagamento['id'] ?? null,
                'request_id' => $requestId,
            ]);

            return response()->json(['status' => 'success_paid'], 200);
        }

        // ───────── REFUNDED / CHARGED_BACK → reverter pagamento ──────
        // BUG-A19 [v1.1]: o MP devolveu o dinheiro. A mensalidade volta a ser
        // Pendente/Atrasado, e o Observer (Patch 01) detecta a transição
        // Pago→Pendente e ESTORNA a MovimentacaoFinanceira.
        if (in_array($statusMP, ['refunded', 'charged_back'], true)) {
            if ($m->estaPaga()) {
                $motivo = $statusMP === 'refunded'
                    ? 'Reembolso confirmado pelo Mercado Pago'
                    : 'Chargeback confirmado pelo Mercado Pago';

                $m->reverterPagamento($motivo);

                // Garantia adicional: força estorno da MovimentacaoFinanceira
                // (caso o Observer não esteja registrado ainda)
                $this->forcarEstornoMovimentacao($m, $motivo, $requestId);

                Log::warning('[Webhook MP] Pagamento revertido', [
                    'mensalidade_id' => $m->id,
                    'status_mp' => $statusMP,
                    'request_id' => $requestId,
                ]);
            }

            return response()->json([
                'status' => 'success_reverted',
                'reason' => $statusMP,
            ], 200);
        }

        // ───────── REJECTED / CANCELLED → liberar para nova tentativa ─────
        if (in_array($statusMP, ['rejected', 'cancelled'], true)) {
            // Não muda o status da mensalidade — apenas libera para nova
            // tentativa (BUG-C04: incrementa pagamento_tentativa).
            if ($m->estaPaga()) {
                // Borda: já estava paga — não reverter (pode ser rejeição
                // de uma 2ª tentativa de pagamento)
                Log::notice('[Webhook MP] Rejected/cancelled em mensalidade já paga — ignorando', [
                    'mensalidade_id' => $m->id,
                    'status_mp' => $statusMP,
                ]);
            } else {
                $m->incrementarTentativaPagamento();
            }

            return response()->json([
                'status' => 'success_released_for_retry',
                'reason' => $statusMP,
            ], 200);
        }

        // ───────── PENDING / IN_PROCESS / IN_MEDIATION → log & ack ──────
        Log::info('[Webhook MP] Status intermediário — sem ação', [
            'mensalidade_id' => $m->id,
            'status_mp' => $statusMP,
            'request_id' => $requestId,
        ]);

        return response()->json(['status' => 'success_intermediate'], 200);
    }

    /**
     * Garantia adicional: cancelar a movimentação se o Observer falhar.
     * Combina com a UNIQUE PARCIAL do Patch 03 (uq_movf_unique_per_mensalidade).
     */
    private function forcarEstornoMovimentacao(Mensalidade $m, string $motivo, string $requestId): void
    {
        try {
            $mov = MovimentacaoFinanceira::where('mensalidade_id', $m->id)
                ->where('status', 'efetivado')
                ->lockForUpdate()
                ->first();

            if ($mov) {
                $dadosAnt = $mov->toArray();
                $mov->update([
                    'status'        => 'cancelado',
                    'observacoes'   => trim(($mov->observacoes ?? '') . " [Estorno via Webhook] $motivo"),
                    'data_alteracao'=> now(),
                ]);

                if (method_exists($mov, 'registrarAuditoria')) {
                    $mov->registrarAuditoria(
                        'estorno',
                        $dadosAnt,
                        "Webhook MP $motivo (request_id: $requestId)"
                    );
                }
            }
        } catch (Throwable $e) {
            Log::error('[Webhook MP] Falha no estorno forçado', [
                'mensalidade_id' => $m->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FALLBACK — TransacaoTeste (modo legacy, mantido)
    |--------------------------------------------------------------------------
    */

    private function processarTransacaoTeste(
        string $dataId,
        string $statusMP,
        array $dadosPagamento,
        string $requestId
    ): JsonResponse {
        if (!class_exists(TransacaoTeste::class)) {
            Log::notice('[Webhook MP] Pagamento sem mensalidade vinculada — ignorado', [
                'evento_id' => $dataId,
                'request_id' => $requestId,
            ]);
            // BUG-A05: 200 OK para evitar retry do MP
            return response()->json(['status' => 'ignored_no_mensalidade'], 200);
        }

        $teste = TransacaoTeste::where('transacao_id', $dataId)->first();
        if (!$teste) {
            Log::notice('[Webhook MP] Pagamento desconhecido (real e teste)', [
                'evento_id' => $dataId,
                'request_id' => $requestId,
            ]);
            return response()->json(['status' => 'ignored_no_match'], 200);
        }

        $teste->update([
            'status'           => $statusMP,
            'payload_completo' => $dadosPagamento,
        ]);

        return response()->json(['status' => 'success_teste'], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | ENDPOINT DE TESTE — GET /api/webhook/test
    |--------------------------------------------------------------------------
    */

    public function test(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'time'   => now()->toIso8601String(),
            'has_webhook_secret' => !empty(config('services.mercadopago.webhook_secret')),
            'idempotency_table'  => $this->verificarTabelaWebhookEventos(),
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * BUG-C02 — Valida assinatura HMAC do MP + anti-replay.
     *
     * Formato do header MP: "ts=1234567890,v1=abc123def456..."
     * Manifest:             "id:DATA_ID;request-id:REQUEST_ID;ts:TS;"
     *
     * Retornos:
     *   true            → assinatura válida
     *   'forbidden'     → assinatura inválida ou ausente em PROD
     *   'permissive_dev'→ secret não configurado, modo permissivo (DEV)
     */
    private function validarAssinatura(Request $request, string $dataId, string $requestId): true|string
    {
        $secret = config('services.mercadopago.webhook_secret');

        // Sem secret configurado: bloqueia em PROD, permissivo em outros envs
        if (empty($secret)) {
            if (app()->environment('production')) {
                Log::error('[Webhook MP] SECRET ausente em produção — rejeitando');
                return 'forbidden';
            }
            Log::warning('[Webhook MP] SECRET ausente — modo permissivo (não-prod)', [
                'env' => app()->environment(),
            ]);
            return 'permissive_dev';
        }

        $signature = $request->header('x-signature', '');
        if (empty($signature)) {
            Log::warning('[Webhook MP] Header x-signature ausente', [
                'ip' => $request->ip(),
            ]);
            return 'forbidden';
        }

        // Parser do formato MP: "ts=...,v1=..."
        if (!preg_match('/ts=(\d+)\s*,\s*v1=([a-f0-9]+)/i', $signature, $m)) {
            Log::warning('[Webhook MP] Formato de assinatura inválido', [
                'signature' => substr($signature, 0, 50),
            ]);
            return 'forbidden';
        }

        $ts   = (int) $m[1];
        $hash = strtolower($m[2]);

        // Anti-replay: rejeita timestamps fora da janela de 5 minutos
        $delta = abs(time() - $ts);
        if ($delta > 300) {
            Log::warning('[Webhook MP] Timestamp fora da janela (replay?)', [
                'delta_segundos' => $delta,
                'ts' => $ts,
            ]);
            return 'forbidden';
        }

        // Idempotência local extra: rejeita assinatura repetida em <1h
        // (defesa em profundidade contra replay com timestamp válido roubado)
        $cacheKey = 'mp_sig:' . $hash;
        if (Cache::has($cacheKey)) {
            Log::warning('[Webhook MP] Assinatura já vista recentemente', [
                'hash_prefix' => substr($hash, 0, 12),
            ]);
            // NÃO bloqueia — apenas loga, pois evento idempotente já é tratado
            // pela tabela webhook_eventos (Fase 2). Mas registra no Sentry.
        } else {
            Cache::put($cacheKey, true, now()->addHour());
        }

        // Cálculo do manifest e comparação timing-safe
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        if (!hash_equals($expected, $hash)) {
            Log::warning('[Webhook MP] HMAC inválido', [
                'manifest_preview' => substr($manifest, 0, 80),
                'ip' => $request->ip(),
            ]);
            return 'forbidden';
        }

        return true;
    }

    /**
     * BUG-A16 — Canoniza o método de pagamento.
     * MP retorna 'bolbradesco' mas o sistema interno usa 'boleto'.
     */
    private function canonicalMetodo(?string $metodo): ?string
    {
        if (!$metodo) return null;

        return match (strtolower($metodo)) {
            'pix'                                => 'pix',
            'bolbradesco', 'boleto'              => 'boleto',
            'master', 'visa', 'amex', 'elo',
            'hipercard', 'cabal', 'diners'       => 'cartao_credito',
            'debmaster', 'debvisa', 'debelo'     => 'cartao_debito',
            default                              => $metodo,
        };
    }

    private function verificarTabelaWebhookEventos(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('webhook_eventos');
        } catch (Throwable $e) {
            return false;
        }
    }
}
