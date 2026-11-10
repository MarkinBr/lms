<?php

namespace Tests\Feature\Secretaria;

use App\Jobs\EnviarFinanceiroAgendado;
use App\Models\Curso;
use App\Models\FinanceiroEnvio;
use App\Models\Matricula;
use App\Models\Mensalidade;
use App\Models\User;
use App\Services\MercadoPagoService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Resources\Payment;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CentralFinanceiroTest extends TestCase
{
    use RefreshDatabase;

    private function equipe(string $nivel = 'secretario'): User
    {
        return User::factory()->create(['nivel' => $nivel, 'ativo' => true]);
    }

    private function mensalidade(): Mensalidade
    {
        $curso = Curso::query()->create(['titulo' => 'Seminário Médio em Teologia', 'ativo' => true]);
        $aluno = $this->equipe('aluno');
        $matricula = Matricula::query()->create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'numero_matricula' => 'MAT-FIN-2026',
            'data_matricula' => today()->toDateString(),
            'status' => 'ativa',
            'dia_vencimento' => 10,
        ]);

        return Mensalidade::query()->create([
            'matricula_id' => $matricula->id,
            'valor' => 180,
            'data_vencimento' => today()->addDays(12)->toDateString(),
        ]);
    }

    public function test_painel_do_financeiro_usa_layout_da_central_sem_menu_lateral(): void
    {
        $mensalidade = $this->mensalidade();
        $this->get(route('secretaria.financeiro.painel'))->assertRedirect('/secretaria/login');
        $this->actingAs($this->equipe('aluno'))
            ->get(route('secretaria.financeiro.painel'))->assertForbidden();

        $this->actingAs($this->equipe());
        $this->get(route('secretaria.central'))
            ->assertOk()->assertSee(route('secretaria.financeiro.painel'));
        $this->get(route('secretaria.financeiro.painel'))
            ->assertOk()->assertSee('Financeiro')
            ->assertSee('Mensalidades')
            ->assertSee($mensalidade->matricula->user->name)
            ->assertSee('Mensagens agendadas');
    }

    public function test_nao_gera_pagamento_real_sem_homologacao(): void
    {
        $mensalidade = $this->mensalidade();
        $this->actingAs($this->equipe());

        $this->post(route('secretaria.financeiro.pix', $mensalidade), [
            'conferi_cobranca' => '1',
        ])->assertSessionHasErrors('conferi_cobranca');
        $this->post(route('secretaria.financeiro.boleto', $mensalidade), [
            'conferi_cobranca' => '1',
        ])->assertSessionHasErrors('conferi_cobranca');

        $this->assertNull($mensalidade->fresh()->transacao_id);
        $this->assertSame('Pendente', $mensalidade->fresh()->status);
    }

    public function test_emissao_real_usa_gateway_existente_quando_habilitada_sem_quitar_mensalidade(): void
    {
        $mensalidade = $this->mensalidade();
        config()->set('financeiro.habilitar_emissao_mercadopago_secretaria', true);
        config()->set('services.mercadopago.token', 'TEST-CREDENTIAL-NOT-REAL');
        $mock = $this->mock(MercadoPagoService::class);
        $mock->shouldReceive('gerarPix')->once()
            ->andReturn(['success' => true, 'already_existed' => false, 'mensalidade_id' => $mensalidade->id]);

        $this->actingAs($this->equipe())->post(route('secretaria.financeiro.pix', $mensalidade), [
            'conferi_cobranca' => '1',
        ])->assertRedirect();

        $this->assertSame('Pendente', $mensalidade->fresh()->status);
    }

    public function test_recibo_e_verificacao_necessitam_pagamento_confirmado(): void
    {
        $mensalidade = $this->mensalidade();
        $this->actingAs($this->equipe());
        $this->get(route('secretaria.financeiro.recibo', $mensalidade))->assertNotFound();

        $mensalidade->marcarComoPaga(metodoPagamento: 'dinheiro', valorPago: 180);
        $this->get(route('secretaria.financeiro.recibo', $mensalidade))
            ->assertOk()->assertSee($mensalidade->codigo_validacao)->assertSee('RECIBO');
        $this->get(route('recibo.publico.form', $mensalidade->codigo_validacao))->assertOk();

        // Simula o estado posterior ao estorno para testar o verificador.
        // O fluxo legado de auditoria do estorno tem restrição de enum própria.
        $mensalidade->forceFill(['status' => 'Pendente', 'data_pagamento' => null, 'valor_pago' => null])->saveQuietly();
        $this->get(route('secretaria.financeiro.recibo', $mensalidade))->assertNotFound();
        $this->get(route('recibo.publico.form', $mensalidade->codigo_validacao))->assertNotFound();
    }

    public function test_relatorio_a4_contem_somente_pagamentos_confirmados_do_periodo(): void
    {
        $mensalidade = $this->mensalidade();
        $mensalidade->marcarComoPaga(metodoPagamento: 'dinheiro', valorPago: 180);
        $this->actingAs($this->equipe());
        $this->get(route('secretaria.financeiro.relatorio', [
            'inicio' => today()->subDay()->toDateString(),
            'fim' => today()->addDay()->toDateString(),
        ]))->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->get(route('secretaria.financeiro.relatorio', [
            'inicio' => '2024-01-01',
            'fim' => '2026-01-01',
        ]))->assertSessionHasErrors('fim');
    }

    public function test_programacao_de_mensagem_exige_habilitacao_e_permite_cancelar(): void
    {
        Queue::fake();
        $mensalidade = $this->mensalidade();
        $this->actingAs($this->equipe());
        $dados = [
            'canal' => 'email', 'modelo' => 'lembrete',
            'agendado_para' => now()->addHour()->format('Y-m-d H:i:s'),
            'confirmou_contato' => '1',
            'contato_exibido' => ['email' => FinanceiroEnvio::referenciaContato($mensalidade->matricula->user, 'email')],
        ];
        $this->post(route('secretaria.financeiro.agendar', $mensalidade), $dados)
            ->assertSessionHasErrors('canal');
        $this->assertDatabaseCount('financeiro_envios', 0);

        config()->set('financeiro.habilitar_envios_automaticos_secretaria', true);
        $this->post(route('secretaria.financeiro.agendar', $mensalidade), $dados)
            ->assertRedirect();
        $envio = FinanceiroEnvio::firstOrFail();
        $this->assertSame('agendado', $envio->status);
        $this->assertSame($mensalidade->matricula->user->email, $envio->destinatario);
        Queue::assertPushed(EnviarFinanceiroAgendado::class, 1);

        $this->post(route('secretaria.financeiro.envio.cancelar', $envio))->assertRedirect();
        $this->assertSame('cancelado', $envio->fresh()->status);
    }

    public function test_nao_envia_recibo_de_mensalidade_ainda_pendente(): void
    {
        $mensalidade = $this->mensalidade();
        config()->set('financeiro.habilitar_envios_automaticos_secretaria', true);
        $this->actingAs($this->equipe());

        $this->post(route('secretaria.financeiro.agendar', $mensalidade), [
            'canal' => 'email', 'modelo' => 'recibo',
            'agendado_para' => now()->addHour()->format('Y-m-d H:i:s'),
            'confirmou_contato' => '1',
            'contato_exibido' => ['email' => FinanceiroEnvio::referenciaContato($mensalidade->matricula->user, 'email')],
        ])->assertSessionHasErrors('modelo');
        $this->assertDatabaseCount('financeiro_envios', 0);
    }

    public static function boletoAnterior(): array
    {
        return [
            'pendente' => ['pending', true],
            'em compensacao' => ['in_process', false],
            'aprovado' => ['approved', false],
            'desconhecido' => ['status_novo', false],
            'falha da API' => [null, false],
        ];
    }

    #[DataProvider('boletoAnterior')]
    public function test_boleto_vencido_preserva_transacao(?string $status, bool $reutiliza): void
    {
        $mensalidade = $this->boletoVinculado('bolbradesco');
        $client = $this->clienteMercadoPago();
        $client->shouldNotReceive('create');
        $consulta = $client->shouldReceive('get')->with('101')->twice();
        if ($status === null) {
            $consulta->andThrow(new \RuntimeException('Falha simulada'));
        } else {
            $consulta->andReturn($this->pagamento(101, $status));
        }

        for ($i = 0; $i < 2; $i++) {
            $resposta = $this->post(route('secretaria.financeiro.boleto', $mensalidade), ['conferi_cobranca' => '1']);
            if ($reutiliza) {
                $resposta->assertSessionHasNoErrors();
            } else {
                $resposta->assertSessionHasErrors('conferi_cobranca');
            }
        }

        $this->assertSame('101', $mensalidade->fresh()->transacao_id);
        $this->assertSame(1, (int) $mensalidade->fresh()->pagamento_tentativa);
    }

    public static function meiosParaReemissao(): array
    {
        return [
            'boleto legado' => ['boleto', 'bolbradesco', 'bol', 'cancelled'],
            'pix expirado' => ['pix', 'pix', 'pix', 'expired'],
        ];
    }

    #[DataProvider('meiosParaReemissao')]
    public function test_cobranca_encerrada_permite_nova_tentativa_com_a_mesma_chave_apos_timeout(
        string $rota, string $meio, string $prefixo, string $status,
    ): void {
        $mensalidade = $this->boletoVinculado(null);
        $mensalidade->matricula->user->forceFill([
            'cpf' => '52998224725', 'cep' => '20040002', 'endereco' => 'Rua A', 'cidade' => 'Rio de Janeiro', 'estado' => 'RJ',
        ])->save();
        $client = $this->clienteMercadoPago();
        $anterior = $this->pagamento(101, $status);
        $anterior->payment_method_id = $meio;
        $criado = $this->pagamento(102, 'pending');
        $criado->payment_method_id = $meio;
        $criado->transaction_details = (object) ['external_resource_url' => 'https://example.test/102'];
        $criado->point_of_interaction = (object) ['transaction_data' => (object) ['qr_code' => 'PIX-NOVO']];
        $client->shouldReceive('get')->with('101')->twice()->andReturn($anterior);
        $client->shouldReceive('get')->with('102')->once()->andReturn($criado);
        $chave = "X-Idempotency-Key: {$prefixo}_mens_{$mensalidade->id}_v2";
        $tentativas = 0;
        $client->shouldReceive('create')->twice()->andReturnUsing(function ($dados, $opcoes) use (&$tentativas, $meio, $chave, $criado) {
            $this->assertSame($meio, $dados['payment_method_id']);
            $this->assertSame(180.0, $dados['transaction_amount']);
            $this->assertSame([$chave], $opcoes->getCustomHeaders());
            if (++$tentativas === 1) {
                throw new \RuntimeException('Timeout simulado');
            }

            return $criado;
        });

        $url = route('secretaria.financeiro.'.$rota, $mensalidade);
        $this->post($url, ['conferi_cobranca' => '1'])->assertSessionHasErrors('conferi_cobranca');
        $this->assertSame('101', $mensalidade->fresh()->transacao_id);
        $this->assertSame(1, (int) $mensalidade->fresh()->pagamento_tentativa);
        $this->flushSession();
        $this->post($url, ['conferi_cobranca' => '1'])->assertSessionHasNoErrors();
        $this->post($url, ['conferi_cobranca' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('102', $mensalidade->fresh()->transacao_id);
        $this->assertSame(2, (int) $mensalidade->fresh()->pagamento_tentativa);
        $this->assertFalse($mensalidade->fresh()->estaPaga());
    }

    public function test_servico_rele_meio_vinculado_com_modelo_desatualizado(): void
    {
        $mensalidade = $this->boletoVinculado('boleto');
        $client = $this->clienteMercadoPago();
        $client->shouldNotReceive('create');
        $client->shouldNotReceive('get');
        $this->expectExceptionMessage('outro meio');

        app(MercadoPagoService::class)->gerarPix($mensalidade);
    }

    public static function mudancasNoEnvio(): array
    {
        return [
            'email alterado' => ['email', ['email' => 'outro@example.test'], []],
            'verificacao revogada' => ['email', ['email_verified_at' => null], []],
            'notificacoes desativadas' => ['email', ['receber_notificacoes_email' => false], []],
            'whatsapp alterado' => ['whatsapp', ['whatsapp' => '5511998765432'], []],
            'versao revogada' => ['whatsapp', ['acesso_versao' => 1], []],
            'envio legado' => ['email', [], ['aluno_id' => null, 'acesso_versao' => null]],
            'identidade divergente' => ['email', [], ['aluno_id' => 'autor']],
        ];
    }

    #[DataProvider('mudancasNoEnvio')]
    public function test_job_ignora_envio_que_perdeu_autorizacao(string $canal, array $alunoDados, array $envioDados): void
    {
        Mail::shouldReceive('send')->never();
        $whatsapp = $this->mock(WhatsAppService::class);
        $whatsapp->shouldNotReceive('enviarMensagem');
        $mensalidade = $this->mensalidade();
        $aluno = $mensalidade->matricula->user;
        $aluno->forceFill(['whatsapp' => '5511987654321'])->save();
        $this->habilitarAgendamento();
        $this->agendar($mensalidade, $canal)->assertSessionHasNoErrors();
        $envio = FinanceiroEnvio::firstOrFail();
        $aluno->forceFill($alunoDados)->save();
        if (($envioDados['aluno_id'] ?? null) === 'autor') {
            $envioDados['aluno_id'] = $envio->criado_por;
        }
        $envio->update(['agendado_para' => now()->subMinute()] + $envioDados);

        (new EnviarFinanceiroAgendado($envio->id))->handle($whatsapp);

        $this->assertSame('ignorado', $envio->fresh()->status);
    }

    public function test_agendamento_rejeita_canais_sem_destino_autorizado(): void
    {
        $mensalidade = $this->mensalidade();
        $mensalidade->matricula->user->forceFill(['email_verified_at' => null, 'telefone' => '11987654321'])->save();
        $this->habilitarAgendamento();
        $this->agendar($mensalidade, 'email')->assertSessionHasErrors('canal');
        $this->agendar($mensalidade, 'whatsapp')->assertSessionHasErrors('canal');
        foreach (['87654321', '987654321'] as $numero) {
            $mensalidade->matricula->user->forceFill(['whatsapp' => $numero])->save();
            $this->agendar($mensalidade, 'whatsapp')->assertSessionHasErrors('canal');
        }
        $this->assertDatabaseCount('financeiro_envios', 0);
        Queue::assertNothingPushed();
    }

    public function test_agendamento_rejeita_contato_alterado_apos_exibicao(): void
    {
        $mensalidade = $this->mensalidade();
        $referencia = FinanceiroEnvio::referenciaContato($mensalidade->matricula->user, 'email');
        $mensalidade->matricula->user->forceFill(['email' => 'novo@example.test'])->save();
        $this->habilitarAgendamento();

        $this->agendar($mensalidade, 'email', $referencia)->assertSessionHasErrors('canal');
        Queue::assertNothingPushed();
    }

    public static function destinosWhatsapp(): array
    {
        return [
            'internacional' => ['351912345678', '351912345678'],
            'legado com dez digitos' => ['1134567890', '551134567890'],
            'legado com onze digitos' => ['11987654321', '5511987654321'],
        ];
    }

    #[DataProvider('destinosWhatsapp')]
    public function test_whatsapp_envia_pix_uma_vez_ao_numero_normalizado(string $numero, string $destino): void
    {
        config()->set('services.whatsapp.api_url', 'https://whatsapp.example.test');
        config()->set('services.whatsapp.token', 'teste');
        config()->set('services.whatsapp.instance_id', 'teste');
        Http::preventStrayRequests();
        Http::fake(['https://whatsapp.example.test/*' => Http::response()]);
        $mensalidade = $this->mensalidade();
        $mensalidade->matricula->user->forceFill(['whatsapp' => $numero])->save();
        $this->habilitarAgendamento();
        $mensalidade->forceFill(['pix_copia_cola' => 'PIX-CODIGO-TESTE'])->save();
        $this->agendar($mensalidade, 'whatsapp', modelo: 'pix')->assertSessionHasNoErrors();
        $envio = FinanceiroEnvio::firstOrFail();
        $envio->update(['agendado_para' => now()->subMinute()]);
        $job = new EnviarFinanceiroAgendado($envio->id);
        $job->handle(app(WhatsAppService::class));
        $job->handle(app(WhatsAppService::class));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['number'] === $destino
            && str_contains($request['text'], 'PIX-CODIGO-TESTE'));
        $this->assertSame('enviado', $envio->fresh()->status);
    }

    #[DataProvider('boletoAnterior')]
    public function test_pix_com_validade_local_passada_preserva_transacao(?string $status, bool $reutiliza): void
    {
        $mensalidade = $this->boletoVinculado('pix');
        $mensalidade->fresh()->forceFill([
            'pix_expira_em' => now()->subMinute(), 'pix_copia_cola' => 'PIX-CODIGO-TESTE',
        ])->save();
        $client = $this->clienteMercadoPago();
        $client->shouldNotReceive('create');
        $consulta = $client->shouldReceive('get')->with('101')->once();
        if ($status === null) {
            $consulta->andThrow(new \RuntimeException('Falha simulada'));
        } else {
            $pagamento = $this->pagamento(101, $status);
            $pagamento->payment_method_id = 'pix';
            $consulta->andReturn($pagamento);
        }

        $resposta = $this->post(route('secretaria.financeiro.pix', $mensalidade), ['conferi_cobranca' => '1']);
        if ($reutiliza) {
            $resposta->assertSessionHasNoErrors();
        } else {
            $resposta->assertSessionHasErrors('conferi_cobranca');
        }
        $this->assertSame('101', $mensalidade->fresh()->transacao_id);
        $this->assertSame(1, (int) $mensalidade->fresh()->pagamento_tentativa);
    }

    private function agendar(Mensalidade $mensalidade, string $canal, ?string $referencia = null, string $modelo = 'lembrete'): TestResponse
    {
        return $this->post(route('secretaria.financeiro.agendar', $mensalidade), [
            'canal' => $canal, 'modelo' => $modelo, 'confirmou_contato' => '1',
            'agendado_para' => now()->addHour()->format('Y-m-d H:i:s'),
            'contato_exibido' => [$canal => $referencia ?? FinanceiroEnvio::referenciaContato($mensalidade->matricula->user, $canal)],
        ]);
    }

    private function habilitarAgendamento(): void
    {
        Queue::fake();
        config()->set('financeiro.habilitar_envios_automaticos_secretaria', true);
        $this->actingAs($this->equipe());
    }

    private function boletoVinculado(?string $metodo): Mensalidade
    {
        config()->set('financeiro.habilitar_emissao_mercadopago_secretaria', true);
        config()->set('services.mercadopago.token', 'TEST-CREDENTIAL-NOT-REAL');
        $this->actingAs($this->equipe());
        $mensalidade = $this->mensalidade();
        $mensalidade->fresh()->forceFill([
            'data_vencimento' => today()->subDay(), 'transacao_id' => '101', 'metodo_pagamento' => $metodo,
            'link_boleto' => 'https://example.test/101', 'pagamento_tentativa' => 1,
        ])->save();

        return $mensalidade;
    }

    private function clienteMercadoPago(): MockInterface
    {
        $client = \Mockery::mock();
        $this->app->instance(PaymentClient::class, $client);

        return $client;
    }

    private function pagamento(int $id, string $status): Payment
    {
        $pagamento = new Payment;
        $pagamento->id = $id;
        $pagamento->status = $status;
        $pagamento->payment_method_id = 'bolbradesco';

        return $pagamento;
    }
}
