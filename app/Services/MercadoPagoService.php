<?php

namespace App\Services;

use App\Models\Mensalidade;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoService
{
    public function __construct()
    {
        $token = config('services.mercadopago.token');

        if (empty($token)) {
            throw new Exception('Token do Mercado Pago não configurado em config/services.php.');
        }

        MercadoPagoConfig::setAccessToken($token);
    }

    /**
     * @return array{success: bool, already_existed: bool, mensalidade_id: int, transacao_id?: string|null, link_pagamento?: string|null}
     */
    public function gerarPix(Mensalidade $mensalidade): array
    {
        return DB::transaction(function () use ($mensalidade) {
            $m = Mensalidade::query()->whereKey($mensalidade->id)->lockForUpdate()->first();

            if (!$m) {
                throw new Exception('Mensalidade não encontrada.');
            }

            if ($this->isStatusFinal($m->status ?? null)) {
                throw new Exception('Esta mensalidade não permite nova cobrança.');
            }

            if ($this->deveReutilizarPix($m)) {
                Log::info('MercadoPagoService: reutilizando PIX ainda válido', [
                    'mensalidade_id' => $m->id,
                    'transacao_id' => $m->transacao_id,
                    'tentativa' => $this->tentativaAtual($m),
                ]);

                return [
                    'success' => true,
                    'already_existed' => true,
                    'mensalidade_id' => $m->id,
                    'transacao_id' => (string) ($m->transacao_id ?? ''),
                    'link_pagamento' => $m->link_pagamento ?? null,
                ];
            }

            if ($this->precisaNovaTentativaPix($m)) {
                $m->incrementarTentativaPagamento();
                $m->refresh();
            }

            $pagador = $this->resolverPagador($m);
            $nomeCompleto = $this->resolverNomeCompleto($pagador);
            $primeiroNome = explode(' ', $nomeCompleto)[0];
            $cpf = $this->validarCPF($pagador->cpf ?? '', $nomeCompleto);
            $email = $this->validarEmail($pagador->email ?? null, $nomeCompleto);

            $request = [
                'transaction_amount' => $this->normalizarValor($m->valor),
                'description' => sprintf('Mensalidade %s — %s', $m->data_vencimento->format('m/Y'), $nomeCompleto),
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $email,
                    'first_name' => $primeiroNome,
                    'identification' => [
                        'type' => 'CPF',
                        'number' => $cpf,
                    ],
                ],
            ];

            $opts = new RequestOptions();
            $opts->setCustomHeaders([
                'X-Idempotency-Key: ' . $this->gerarIdempotencyKey('pix', $m),
            ]);

            try {
                $payment = (new PaymentClient())->create($request, $opts);

                if (empty($payment->id) || ($payment->status ?? null) === 'rejected') {
                    throw new Exception('Pagamento PIX rejeitado pelo Mercado Pago.');
                }

                $expiraEm = $payment->date_of_expiration
                    ?? $payment->point_of_interaction->transaction_data->expiration_date
                    ?? null;

                $m->registrarTransacao(
                    transacaoId: (string) $payment->id,
                    metodo: 'pix',
                    extras: [
                        'pix_copia_cola' => $payment->point_of_interaction->transaction_data->qr_code ?? null,
                        'pix_qr_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64 ?? null,
                        'link_pagamento' => $payment->point_of_interaction->transaction_data->ticket_url ?? null,
                        'pix_expira_em' => $expiraEm,
                    ]
                );

                Log::info('MercadoPagoService: PIX gerado', [
                    'mensalidade_id' => $m->id,
                    'transacao_id' => $payment->id,
                    'tentativa' => $this->tentativaAtual($m),
                ]);

                return [
                    'success' => true,
                    'already_existed' => false,
                    'mensalidade_id' => $m->id,
                    'transacao_id' => (string) $payment->id,
                    'link_pagamento' => $payment->point_of_interaction->transaction_data->ticket_url ?? null,
                ];
            } catch (MPApiException $e) {
                Log::error('MercadoPagoService: falha ao gerar PIX', [
                    'mensalidade_id' => $m->id,
                    'tentativa' => $this->tentativaAtual($m),
                    'erro' => $this->mensagemApi($e),
                ]);

                throw new Exception('Erro Mercado Pago ao gerar PIX: ' . $this->mensagemApi($e));
            }
        });
    }

    /**
     * @return array{success: bool, already_existed: bool, mensalidade_id: int, transacao_id?: string|null, link_boleto?: string|null}
     */
    public function gerarBoleto(Mensalidade $mensalidade): array
    {
        return DB::transaction(function () use ($mensalidade) {
            $m = Mensalidade::query()->whereKey($mensalidade->id)->lockForUpdate()->first();

            if (!$m) {
                throw new Exception('Mensalidade não encontrada.');
            }

            if ($this->isStatusFinal($m->status ?? null)) {
                throw new Exception('Esta mensalidade não permite nova cobrança.');
            }

            if ($this->deveReutilizarBoleto($m)) {
                return [
                    'success' => true,
                    'already_existed' => true,
                    'mensalidade_id' => $m->id,
                    'transacao_id' => (string) ($m->transacao_id ?? ''),
                    'link_boleto' => $m->link_boleto ?? null,
                ];
            }

            if ($this->precisaNovaTentativaBoleto($m)) {
                $m->incrementarTentativaPagamento();
                $m->refresh();
            }

            $pagador = $this->resolverPagador($m);
            $nomeCompleto = $this->resolverNomeCompleto($pagador);
            $partes = preg_split('/\s+/', $nomeCompleto) ?: [$nomeCompleto];
            $firstName = $partes[0] ?? $nomeCompleto;
            $lastName = count($partes) > 1 ? implode(' ', array_slice($partes, 1)) : 'Sobrenome';

            $cpf = $this->validarCPF($pagador->cpf ?? '', $nomeCompleto);
            $email = $this->validarEmail($pagador->email ?? null, $nomeCompleto);
            $cep = $this->validarCEP($pagador->cep ?? '', $nomeCompleto);
            $this->validarEnderecoCompleto($pagador, $nomeCompleto);

            $request = [
                'transaction_amount' => $this->normalizarValor($m->valor),
                'description' => sprintf('Mensalidade %s — %s', $m->data_vencimento->format('m/Y'), $nomeCompleto),
                'payment_method_id' => 'bolbradesco',
                'payer' => [
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'identification' => [
                        'type' => 'CPF',
                        'number' => $cpf,
                    ],
                    'address' => [
                        'zip_code' => $cep,
                        'street_name' => $pagador->endereco,
                        'street_number' => (string) ($pagador->numero ?? 'S/N'),
                        'neighborhood' => $pagador->bairro ?? 'Centro',
                        'city' => $pagador->cidade,
                        'federal_unit' => strtoupper((string) $pagador->estado),
                    ],
                ],
            ];

            $opts = new RequestOptions();
            $opts->setCustomHeaders([
                'X-Idempotency-Key: ' . $this->gerarIdempotencyKey('bol', $m),
            ]);

            try {
                $payment = (new PaymentClient())->create($request, $opts);

                if (empty($payment->id)) {
                    throw new Exception('Mercado Pago não retornou ID do boleto.');
                }

                $linkBoleto = $payment->transaction_details->external_resource_url
                    ?? $payment->point_of_interaction->transaction_data->ticket_url
                    ?? null;

                $m->registrarTransacao(
                    transacaoId: (string) $payment->id,
                    metodo: 'boleto',
                    extras: [
                        'link_boleto' => $linkBoleto,
                        'linha_digitavel' => $payment->barcode->content ?? null,
                        'link_pagamento' => $linkBoleto,
                    ]
                );

                Log::info('MercadoPagoService: boleto gerado', [
                    'mensalidade_id' => $m->id,
                    'transacao_id' => $payment->id,
                    'tentativa' => $this->tentativaAtual($m),
                ]);

                return [
                    'success' => true,
                    'already_existed' => false,
                    'mensalidade_id' => $m->id,
                    'transacao_id' => (string) $payment->id,
                    'link_boleto' => $linkBoleto,
                ];
            } catch (MPApiException $e) {
                Log::error('MercadoPagoService: falha ao gerar boleto', [
                    'mensalidade_id' => $m->id,
                    'tentativa' => $this->tentativaAtual($m),
                    'erro' => $this->mensagemApi($e),
                ]);

                throw new Exception('Erro Mercado Pago ao gerar boleto: ' . $this->mensagemApi($e));
            }
        });
    }

    public function consultarPagamento(string|int $id): ?array
    {
        try {
            $payment = (new PaymentClient())->get($id);
            return json_decode(json_encode($payment), true);
        } catch (\Throwable $e) {
            Log::warning('MercadoPagoService: falha ao consultar pagamento', [
                'pagamento_id' => $id,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function cancelarPagamento(Mensalidade $mensalidade): bool
    {
        return DB::transaction(function () use ($mensalidade) {
            $m = Mensalidade::query()->whereKey($mensalidade->id)->lockForUpdate()->first();

            if (!$m || empty($m->transacao_id)) {
                throw new Exception('Mensalidade não possui transação associada.');
            }

            (new PaymentClient())->cancel($m->transacao_id);

            if (method_exists($m, 'cancelar')) {
                $m->cancelar('Pagamento cancelado manualmente via Mercado Pago');
            } else {
                $m->status = 'Cancelado';
                $m->observacao = trim(($m->observacao ?? '') . ' [Pagamento cancelado manualmente via Mercado Pago]');
                $m->save();
            }

            Log::info('MercadoPagoService: pagamento cancelado', [
                'mensalidade_id' => $m->id,
                'transacao_id' => $m->transacao_id,
            ]);

            return true;
        });
    }

    private function deveReutilizarPix(Mensalidade $m): bool
    {
        if ($this->isStatusFinal($m->status ?? null)) {
            return false;
        }

        if (empty($m->pix_copia_cola) || empty($m->transacao_id)) {
            return false;
        }

        if (method_exists($m, 'pixExpirado') && $m->pixExpirado()) {
            return false;
        }

        return true;
    }

    private function precisaNovaTentativaPix(Mensalidade $m): bool
    {
        if ($this->isStatusFinal($m->status ?? null)) {
            return false;
        }

        if (empty($m->transacao_id) && empty($m->pix_copia_cola)) {
            return false;
        }

        return method_exists($m, 'pixExpirado') ? (bool) $m->pixExpirado() : false;
    }

    private function deveReutilizarBoleto(Mensalidade $m): bool
    {
        if ($this->isStatusFinal($m->status ?? null)) {
            return false;
        }

        if (empty($m->transacao_id) || empty($m->link_boleto)) {
            return false;
        }

        if (!in_array((string) $m->metodo_pagamento, ['boleto', 'bolbradesco'], true)) {
            return false;
        }

        return !$this->boletoExpirado($m);
    }

    private function precisaNovaTentativaBoleto(Mensalidade $m): bool
    {
        if ($this->isStatusFinal($m->status ?? null)) {
            return false;
        }

        if (empty($m->transacao_id) && empty($m->link_boleto)) {
            return false;
        }

        return $this->boletoExpirado($m);
    }

    private function boletoExpirado(Mensalidade $m): bool
    {
        $vencimento = $m->data_vencimento;

        if (!$vencimento) {
            return false;
        }

        return $vencimento->copy()->endOfDay()->lt(now(config('app.timezone', 'America/Sao_Paulo')));
    }

    private function isStatusFinal(?string $status): bool
    {
        return in_array((string) $status, ['Pago', 'Cancelado'], true);
    }

    private function tentativaAtual(Mensalidade $m): int
    {
        return max(1, (int) ($m->pagamento_tentativa ?? 1));
    }

    private function gerarIdempotencyKey(string $tipo, Mensalidade $m): string
    {
        return sprintf('%s_mens_%d_v%d', $tipo, $m->id, $this->tentativaAtual($m));
    }

    private function resolverPagador(Mensalidade $m): object
    {
        $pagador = $m->matricula?->user ?? $m->matricula?->aluno;

        if (!$pagador) {
            throw new Exception('Aluno não encontrado na matrícula.');
        }

        return $pagador;
    }

    private function resolverNomeCompleto(object $pagador): string
    {
        $nome = trim((string) ($pagador->name ?? $pagador->nome ?? ''));

        if ($nome === '') {
            throw new Exception('Nome do aluno está vazio. Atualize o cadastro.');
        }

        return $nome;
    }

    private function validarCPF(?string $cpf, string $nomeAluno): string
    {
        $cpfLimpo = preg_replace('/\D+/', '', (string) $cpf);

        if (strlen($cpfLimpo) !== 11) {
            throw new Exception("O aluno {$nomeAluno} não possui CPF válido cadastrado. Atualize o cadastro antes de gerar PIX/Boleto.");
        }

        if (preg_match('/^(\d)\1{10}$/', $cpfLimpo)) {
            throw new Exception("O CPF cadastrado para {$nomeAluno} é inválido.");
        }

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($c = 0; $c < $t; $c++) {
                $soma += ((int) $cpfLimpo[$c]) * (($t + 1) - $c);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpfLimpo[$t] !== $digito) {
                throw new Exception("O CPF cadastrado para {$nomeAluno} é matematicamente inválido.");
            }
        }

        return $cpfLimpo;
    }

    private function validarEmail(?string $email, string $nomeAluno): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("O aluno {$nomeAluno} não possui e-mail válido cadastrado.");
        }

        return (string) $email;
    }

    private function validarCEP(?string $cep, string $nomeAluno): string
    {
        $cepLimpo = preg_replace('/\D+/', '', (string) $cep);

        if (strlen($cepLimpo) !== 8) {
            throw new Exception("O aluno {$nomeAluno} não possui CEP válido cadastrado.");
        }

        return $cepLimpo;
    }

    private function validarEnderecoCompleto(object $pagador, string $nomeAluno): void
    {
        $faltando = [];

        foreach (['endereco' => 'logradouro', 'cidade' => 'cidade', 'estado' => 'UF'] as $campo => $rotulo) {
            if (empty($pagador->{$campo})) {
                $faltando[] = $rotulo;
            }
        }

        if ($faltando !== []) {
            throw new Exception('Endereço incompleto para ' . $nomeAluno . '. Faltam: ' . implode(', ', $faltando) . '.');
        }
    }

    private function normalizarValor(mixed $valor): float
    {
        if (is_string($valor)) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }

        return round((float) $valor, 2);
    }

    private function mensagemApi(MPApiException $e): string
    {
        $content = $e->getApiResponse()?->getContent();

        if (is_array($content)) {
            $mensagem = (string) ($content['message'] ?? 'erro desconhecido');
            if (isset($content['cause'][0])) {
                $mensagem .= ' — ' . (string) ($content['cause'][0]['description'] ?? $content['cause'][0]['code'] ?? 'sem detalhe');
            }
            return $mensagem;
        }

        return $e->getMessage();
    }
}
