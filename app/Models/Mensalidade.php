<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Mensalidade extends Model
{
    use HasFactory, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | CONFIGURAÇÃO BÁSICA
    |--------------------------------------------------------------------------
    */

    protected $table = 'mensalidades';

    /**
     * BUG-C06/A18: $fillable MÍNIMO. Campos sensíveis (status, data_pagamento,
     * transacao_id, link_pagamento, pix_*) NÃO entram aqui — são alterados
     * exclusivamente via métodos dedicados abaixo.
     */
    protected $fillable = [
        'matricula_id',
        'valor',
        'data_vencimento',
        'observacao',
    ];

    /**
     * Lista completa de colunas que existem no banco. Mantida apenas para
     * documentação — NÃO repassar ao $fillable.
     *
     * NÃO-FILLABLE intencionalmente:
     *   - status                  (via marcarComoPaga/cancelar/reverterPagamento)
     *   - data_pagamento          (via marcarComoPaga)
     *   - valor_pago              (via marcarComoPaga, com multa)
     *   - metodo_pagamento        (via marcarComoPaga)
     *   - transacao_id            (via registrarTransacao)
     *   - link_pagamento / link_boleto / linha_digitavel
     *   - pix_copia_cola / pix_qr_base64
     *   - pix_expira_em           (BUG-C04: para detectar PIX expirado)
     *   - pagamento_tentativa     (BUG-C04: contador para idempotency-key)
     *   - codigo_validacao        (BUG-C05: auto-gerado UUID)
     *   - comprovante             (via attachment)
     */

    protected $casts = [
        'data_vencimento'    => 'date',
        'data_pagamento'     => 'date',
        'pix_expira_em'      => 'datetime',
        'valor'              => 'decimal:2',
        'valor_pago'         => 'decimal:2',
        'pagamento_tentativa'=> 'integer',
    ];

    protected $appends = [
        'dias_atraso',
        'dias_ate_vencer',
        'valor_formatado',
        'valor_com_multa',
    ];

    /*
    |--------------------------------------------------------------------------
    | HOOKS DE CICLO DE VIDA
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        // BUG-C05: auto-gerar codigo_validacao (UUID) para uso em rota pública
        static::creating(function (Mensalidade $m) {
            if (empty($m->codigo_validacao)) {
                $m->codigo_validacao = (string) Str::uuid();
            }

            // BUG-C04: tentativa inicial = 1
            if (empty($m->pagamento_tentativa)) {
                $m->pagamento_tentativa = 1;
            }
        });

        // NOTA: NÃO marcamos automaticamente como 'Atrasado' aqui.
        // Isso é feito pelo Command `mensalidades:atualizar-vencidas`
        // (Patch 07) em horário agendado, garantindo consistência.
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function movimentacao(): HasOne
    {
        return $this->hasOne(MovimentacaoFinanceira::class)
            ->where('status', 'efetivado');
    }

    public function cobrancas()
    {
        // Patch 08 cria a tabela mensalidade_cobrancas
        return $this->hasMany(MensalidadeCobranca::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS  (Attribute API do Laravel 11+)
    |--------------------------------------------------------------------------
    */

    /**
     * BUG-A01 [v1.1] — Quantidade de dias EM ATRASO.
     *
     *  Regras:
     *    • 0 se ainda não venceu OU se está paga
     *    • Sempre INTEIRO e SEMPRE >= 0
     *    • Usa startOfDay() para evitar problemas de hora/timezone
     *
     *  Por que NÃO abs():
     *    abs() faz mensalidade FUTURA aparecer como "atrasada" → cobra multa
     *    indevida. max(0, ...) preserva a direção (positivo = atraso real).
     */
    protected function diasAtraso(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->estaPaga() || $this->estaCancelada()) {
                return 0;
            }

            $vencimento = optional($this->data_vencimento)?->startOfDay();
            if (!$vencimento) return 0;

            $hoje = Carbon::now(config('app.timezone'))->startOfDay();

            // diffInDays sem abs (Carbon 3 retorna float com sinal):
            //   venc < hoje  → diff positivo (atraso)
            //   venc > hoje  → diff negativo (futuro)
            //   venc == hoje → 0
            return max(0, (int) $vencimento->diffInDays($hoje, false));
        });
    }

    /**
     * BUG-A12 [v1.1] — Dias até vencer (positivo se ainda no futuro).
     */
    protected function diasAteVencer(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->estaPaga() || $this->estaCancelada()) {
                return 0;
            }

            $vencimento = optional($this->data_vencimento)?->startOfDay();
            if (!$vencimento) return 0;

            $hoje = Carbon::now(config('app.timezone'))->startOfDay();
            return max(0, (int) $hoje->diffInDays($vencimento, false));
        });
    }

    protected function valorFormatado(): Attribute
    {
        return Attribute::get(fn () => 'R$ ' . number_format((float) $this->valor, 2, ',', '.'));
    }

    protected function valorComMulta(): Attribute
    {
        return Attribute::get(fn () => $this->calcularValorComMulta());
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePendentes($query)
    {
        return $query->where('status', 'Pendente');
    }

    public function scopePagas($query)
    {
        return $query->where('status', 'Pago');
    }

    public function scopeAtrasadas($query)
    {
        return $query->where('status', 'Atrasado');
    }

    public function scopeCanceladas($query)
    {
        return $query->where('status', 'Cancelado');
    }

    /**
     * BUG-M07 [v1.1] — Vencidas DE VERDADE (status pendente E data passada).
     * Usado pelo command `mensalidades:atualizar-vencidas` do Patch 07.
     */
    public function scopeVencidas($query)
    {
        return $query
            ->where('status', 'Pendente')
            ->whereDate('data_vencimento', '<', Carbon::now(config('app.timezone'))->startOfDay());
    }

    /**
     * BUG-A12 [v1.1] — Mensalidades PAGAS em determinado mês (regime de caixa).
     * Para os widgets MensalidadeStats / KPIs de "Recebido no mês".
     */
    public function scopePagasNoMes($query, int $mes, int $ano)
    {
        return $query
            ->where('status', 'Pago')
            ->whereYear('data_pagamento', $ano)
            ->whereMonth('data_pagamento', $mes);
    }

    /**
     * REL-06 — Mensalidades em determinado bucket de aging.
     * Usado pelo RelatorioInadimplenciaController do Patch 10.
     */
    public function scopeAgingBucket($query, int $minDias, int $maxDias)
    {
        $now = Carbon::now(config('app.timezone'))->startOfDay();
        return $query
            ->whereIn('status', ['Pendente', 'Atrasado'])
            ->whereDate('data_vencimento', '<=', $now->copy()->subDays($minDias))
            ->whereDate('data_vencimento', '>',  $now->copy()->subDays($maxDias + 1));
    }

    public function scopePorAluno($query, int $userId)
    {
        return $query->whereHas('matricula', fn ($q) => $q->where('user_id', $userId));
    }

    public function scopePorTurma($query, int $turmaId)
    {
        return $query->whereHas('matricula', fn ($q) => $q->where('turma_id', $turmaId));
    }

    /*
    |--------------------------------------------------------------------------
    | PREDICADOS
    |--------------------------------------------------------------------------
    */

    public function estaPaga(): bool
    {
        return $this->status === 'Pago';
    }

    public function estaPendente(): bool
    {
        return $this->status === 'Pendente';
    }

    public function estaAtrasada(): bool
    {
        if ($this->estaPaga() || $this->estaCancelada()) return false;

        $venc = optional($this->data_vencimento)?->startOfDay();
        if (!$venc) return false;

        return $venc->lt(Carbon::now(config('app.timezone'))->startOfDay());
    }

    public function estaCancelada(): bool
    {
        return $this->status === 'Cancelado';
    }

    public function temPagamentoGerado(): bool
    {
        return !empty($this->transacao_id);
    }

    /**
     * BUG-C04 [v1.1] — Detecta se o PIX/Boleto já expirou.
     * Usado por MercadoPagoService::gerarPix() para decidir se incrementa
     * o contador `pagamento_tentativa` antes de gerar a chave de idempotência.
     */
    public function pixExpirado(): bool
    {
        if (empty($this->pix_expira_em)) {
            // sem coluna preenchida: usar fallback (24h após criação do PIX)
            $criadoEm = $this->updated_at ?? $this->created_at;
            return $criadoEm
                ? $criadoEm->copy()->addHours(24)->isPast()
                : true;
        }
        return $this->pix_expira_em->isPast();
    }

    public function podeGerarPagamento(): bool
    {
        return !$this->estaPaga() && !$this->estaCancelada();
    }

    public function podeCancelar(): bool
    {
        return !$this->estaCancelada();
    }

    /*
    |--------------------------------------------------------------------------
    | OPERAÇÕES DE NEGÓCIO  (sem mass-assignment)
    |--------------------------------------------------------------------------
    */

    /**
     * BUG-A07 [v1.1] — Marca como paga e registra valor_pago (com multa).
     *
     * O valor_pago é o que realmente entrou no caixa (incluindo multa/juros).
     * O valor é o nominal da mensalidade. Eles podem divergir e DEVEM ser
     * registrados separadamente para conciliação contábil.
     */
    public function marcarComoPaga(
        ?Carbon $dataPagamento = null,
        ?string $metodoPagamento = null,
        ?float $valorPago = null
    ): void {
        $this->forceFill([
            'status'            => 'Pago',
            'data_pagamento'    => $dataPagamento ?? Carbon::now(config('app.timezone')),
            'metodo_pagamento'  => $metodoPagamento ?? $this->metodo_pagamento,
            'valor_pago'        => $valorPago ?? $this->calcularValorComMulta(),
        ])->save();
    }

    /**
     * BUG-C09 — Reverte um pagamento (corrige erro da secretaria).
     * O Observer (Patch 01) detecta isso e ESTORNA a MovimentacaoFinanceira.
     */
    public function reverterPagamento(string $motivo = ''): void
    {
        $this->forceFill([
            'status'         => $this->estaAtrasada() ? 'Atrasado' : 'Pendente',
            'data_pagamento' => null,
            'valor_pago'     => null,
            'observacao'     => trim(($this->observacao ?? '') . " [Estorno] $motivo"),
        ])->save();
    }

    public function cancelar(?string $motivo = null): void
    {
        $this->forceFill([
            'status'     => 'Cancelado',
            'observacao' => trim(($this->observacao ?? '') . ' ' . ($motivo ? "[Cancelado] $motivo" : '')),
        ])->save();
    }

    /**
     * Registra os dados retornados pelo MP (transação, links, QR).
     * Usado apenas pelo MercadoPagoService (Patch 05).
     */
    public function registrarTransacao(string $transacaoId, array $dados): void
    {
        $payload = array_merge(['transacao_id' => $transacaoId], $dados);
        $this->forceFill($payload)->save();
    }

    /**
     * BUG-C04 [v1.1] — Incrementa o contador de tentativa.
     * Chamado APENAS quando o PIX/Boleto anterior expirou e o aluno pede
     * uma nova 2ª via (MF-25). Garante chave de idempotência única.
     */
    public function incrementarTentativaPagamento(): int
    {
        $this->increment('pagamento_tentativa');
        return $this->pagamento_tentativa;
    }

    /*
    |--------------------------------------------------------------------------
    | CÁLCULOS FINANCEIROS
    |--------------------------------------------------------------------------
    */

    /**
     * BUG-A02 [v1.1] — Valor total a pagar (com multa + juros).
     *
     * Fórmula (configurável em config/financeiro.php — Patch 09):
     *   valor_com_multa = valor + (valor * multa%) + (valor * juros% / 30 * dias)
     *
     *  multa%        — padrão 2%   (config 'financeiro.multa.percentual')
     *  juros%/mês    — padrão 1%   (config 'financeiro.juros.percentual_mes')
     *  Truncado em max(0, dias)    — proteção contra BUG-A01
     *
     *  [v1.1] BUG-A20: este método retorna float (precisão limitada).
     *  Para conciliação bancária exata, ver Patch 15 (centavos inteiros).
     */
    public function calcularValorComMulta(): float
    {
        if ($this->estaPaga() || $this->estaCancelada()) {
            return (float) $this->valor;
        }

        $dias = max(0, (int) $this->dias_atraso);
        if ($dias === 0) {
            return (float) $this->valor;
        }

        return (float) ($this->valor + $this->calcularMulta() + $this->calcularJuros());
    }

    /**
     * Multa única (2% sobre o valor) — aplicada APENAS se houver atraso.
     */
    public function calcularMulta(): float
    {
        if (!$this->estaAtrasada()) return 0.0;
        $pct = (float) config('financeiro.multa.percentual', 0.02);
        return round((float) $this->valor * $pct, 2);
    }

    /**
     * Juros pro-rata (1% ao mês ÷ 30 × dias_atraso).
     */
    public function calcularJuros(): float
    {
        if (!$this->estaAtrasada()) return 0.0;
        $dias = max(0, (int) $this->dias_atraso);
        if ($dias === 0) return 0.0;

        $pctMes = (float) config('financeiro.juros.percentual_mes', 0.01);
        $jurosDia = ((float) $this->valor * $pctMes) / 30.0;
        return round($jurosDia * $dias, 2);
    }

    /**
     * BUG-A20 [v1.1] — Helper para futura migração para centavos inteiros.
     * Mantido aqui para que o código consumidor já possa trabalhar com int.
     */
    public function valorEmCentavos(): int
    {
        return (int) round(((float) $this->valor) * 100);
    }

    public function valorComMultaEmCentavos(): int
    {
        return (int) round($this->calcularValorComMulta() * 100);
    }

    /*
    |--------------------------------------------------------------------------
    | ROUTE BINDING — usar codigo_validacao em rotas públicas (BUG-C05)
    |--------------------------------------------------------------------------
    */

    public function resolveRouteBinding($value, $field = null)
    {
        // Em rotas que recebem {codigo_validacao}, usar esse campo (UUID)
        if ($field === 'codigo_validacao' || Str::isUuid($value)) {
            return $this->where('codigo_validacao', $value)->firstOrFail();
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
