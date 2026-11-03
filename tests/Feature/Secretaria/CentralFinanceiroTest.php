<?php

namespace Tests\Feature\Secretaria;

use App\Jobs\EnviarFinanceiroAgendado;
use App\Models\Curso;
use App\Models\FinanceiroEnvio;
use App\Models\Matricula;
use App\Models\Mensalidade;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

        $mensalidade->reverterPagamento('Conferência de lançamento');
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
        ])->assertSessionHasErrors('modelo');
        $this->assertDatabaseCount('financeiro_envios', 0);
    }
}
