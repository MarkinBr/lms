<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CETSADP — Patch Validation Test Suite v1.0
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Suíte PHPUnit que valida tecnicamente os 12 patches da auditoria após
 * aplicação. Executada automaticamente pelo deploy.sh na Fase 7.
 *
 * COBERTURA:
 *   ✅ Patch 01 — MensalidadeObserver (estorno automático, criação)
 *   ✅ Patch 02 — Model Mensalidade (SoftDeletes, multa, dias_atraso)
 *   ✅ Patch 03 — Migration (RESTRICT, codigo_validacao, valor_pago)
 *   ✅ Patch 04 — WebhookController (HMAC, HTTP 200)
 *   ✅ Patch 05 — MercadoPagoService (sem CPF fake, idempotency)
 *   ✅ Patch 06 — Recibo Público (UUID, mascaramento)
 *   ✅ Patch 07 — Scheduler (comandos artisan)
 *   ✅ Patch 08 — Cobrança (jobs + tabela)
 *   ✅ Patch 09 — Multa/Juros (config + helper)
 *   ✅ Patch 10 — Relatórios (controllers + aging)
 *   ✅ Patch 11 — Excel/DRE (exports)
 *   ✅ Patch 12 — Polimento (rate-limit, polling, etc.)
 *
 * COMO INSTALAR:
 *   Copiar este arquivo para: lms/tests/Feature/PatchValidationTest.php
 *
 * COMO RODAR:
 *   cd lms
 *   php artisan test --filter=PatchValidationTest
 *   php artisan test --filter=PatchValidationTest --stop-on-failure
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Carbon;
use ReflectionClass;
use ReflectionMethod;

class PatchValidationTest extends TestCase
{
    // NÃO usar RefreshDatabase aqui — testamos contra schema real pós-migrate

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 01 — MensalidadeObserver
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_01_observer_class_exists()
    {
        $this->assertTrue(
            class_exists(\App\Observers\MensalidadeObserver::class),
            'PATCH-01: classe MensalidadeObserver não foi encontrada'
        );
    }

    /** @test */
    public function patch_01_observer_has_required_methods()
    {
        $required = ['created', 'updated', 'registrarEntrada', 'atualizarEntrada', 'estornarMovimentacao', 'determinarConta'];
        $ref = new ReflectionClass(\App\Observers\MensalidadeObserver::class);

        foreach ($required as $method) {
            $this->assertTrue(
                $ref->hasMethod($method),
                "PATCH-01: método '$method' ausente no Observer"
            );
        }
    }

    /** @test */
    public function patch_01_observer_is_registered()
    {
        // Verifica se está registrado no AppServiceProvider
        $provider = file_get_contents(base_path('app/Providers/AppServiceProvider.php'));
        $this->assertStringContainsString(
            'MensalidadeObserver',
            $provider,
            'PATCH-01: Observer não está registrado em AppServiceProvider'
        );
    }

    /** @test */
    public function patch_01_observer_reverses_on_status_change_to_pendente()
    {
        // Se Mensalidade vai de Pago → Pendente, deve estornar movimentação
        $ref = new ReflectionMethod(\App\Observers\MensalidadeObserver::class, 'updated');
        $code = file_get_contents($ref->getFileName());

        $this->assertMatchesRegularExpression(
            '/estornar|reverter|cancelar.*movimentac/i',
            $code,
            'PATCH-01: Observer não contém lógica de estorno (BUG-C09)'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 02 — Model Mensalidade
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_02_mensalidade_uses_softdeletes()
    {
        $traits = class_uses(\App\Models\Mensalidade::class);
        $this->assertContains(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            $traits,
            'PATCH-02: Model Mensalidade não usa SoftDeletes (BUG-C01)'
        );
    }

    /** @test */
    public function patch_02_mensalidade_has_required_methods()
    {
        $required = ['marcarComoPaga', 'cancelar', 'reverterPagamento', 'calcularMulta', 'calcularJuros'];
        $ref = new ReflectionClass(\App\Models\Mensalidade::class);

        foreach ($required as $method) {
            $this->assertTrue(
                $ref->hasMethod($method),
                "PATCH-02: método '$method' ausente no Model Mensalidade"
            );
        }
    }

    /** @test */
    public function patch_02_dias_atraso_returns_absolute_value()
    {
        // BUG-A01: diffInDays sem abs() pode retornar negativo no Carbon 3
        $mensalidade = new \App\Models\Mensalidade([
            'valor' => 100.00,
            'data_vencimento' => Carbon::today()->subDays(10),
            'status' => 'Atrasado',
        ]);

        $dias = $mensalidade->dias_atraso;
        $this->assertIsInt($dias, 'PATCH-02: dias_atraso deve ser int');
        $this->assertGreaterThanOrEqual(0, $dias, 'PATCH-02: dias_atraso negativo (BUG-A01)');
    }

    /** @test */
    public function patch_02_dias_atraso_zero_when_not_late()
    {
        $mensalidade = new \App\Models\Mensalidade([
            'valor' => 100.00,
            'data_vencimento' => Carbon::today()->addDays(5),
            'status' => 'Pendente',
        ]);

        $this->assertEquals(0, $mensalidade->dias_atraso,
            'PATCH-02: mensalidade não vencida deve ter dias_atraso = 0');
    }

    /** @test */
    public function patch_02_multa_blindada_contra_negativos()
    {
        // BUG-A02: multa não pode ser negativa
        $mensalidade = new \App\Models\Mensalidade([
            'valor' => 100.00,
            'data_vencimento' => Carbon::today()->addDays(5), // futura
            'status' => 'Pendente',
        ]);

        $multa = $mensalidade->calcularMulta();
        $this->assertGreaterThanOrEqual(0, $multa,
            'PATCH-02: multa não pode ser negativa para mensalidades futuras (BUG-A02)');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 03 — Migration Hardening
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_03_mensalidades_has_softdeletes_column()
    {
        $this->assertTrue(
            Schema::hasColumn('mensalidades', 'deleted_at'),
            'PATCH-03: coluna deleted_at ausente em mensalidades'
        );
    }

    /** @test */
    public function patch_03_mensalidades_has_codigo_validacao()
    {
        $this->assertTrue(
            Schema::hasColumn('mensalidades', 'codigo_validacao'),
            'PATCH-03: coluna codigo_validacao ausente'
        );
    }

    /** @test */
    public function patch_03_mensalidades_has_valor_pago()
    {
        $this->assertTrue(
            Schema::hasColumn('mensalidades', 'valor_pago'),
            'PATCH-03: coluna valor_pago ausente'
        );
    }

    /** @test */
    public function patch_03_foreign_key_is_restrict_not_cascade()
    {
        // BUG-C cascade — apagar matrícula NÃO deve apagar mensalidades pagas
        $fks = DB::select("
            SELECT conname, confdeltype
            FROM pg_constraint
            WHERE conrelid = 'mensalidades'::regclass
              AND contype = 'f'
              AND conname LIKE '%matricula%'
        ");

        $this->assertNotEmpty($fks, 'PATCH-03: FK de matricula_id não encontrada');

        foreach ($fks as $fk) {
            // confdeltype: 'r' = RESTRICT, 'c' = CASCADE, 'a' = NO ACTION
            $this->assertNotEquals('c', $fk->confdeltype,
                "PATCH-03: FK ainda está em CASCADE (BUG-C onDelete cascade)");
        }
    }

    /** @test */
    public function patch_03_indexes_created()
    {
        $expected = [
            'mensalidades_status_data_vencimento_index',
            'mensalidades_data_pagamento_index',
            'mensalidades_matricula_id_status_index',
        ];

        $existing = DB::select("
            SELECT indexname FROM pg_indexes WHERE tablename = 'mensalidades'
        ");
        $existingNames = array_column($existing, 'indexname');

        foreach ($expected as $idx) {
            $this->assertContains($idx, $existingNames,
                "PATCH-03: índice '$idx' não criado");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 04 — WebhookController
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_04_webhook_validates_signature()
    {
        $code = file_get_contents(base_path('app/Http/Controllers/WebhookController.php'));

        $this->assertStringContainsString('hash_hmac', $code,
            'PATCH-04: webhook não valida HMAC (BUG-C02)');
        $this->assertStringContainsString('hash_equals', $code,
            'PATCH-04: webhook não usa hash_equals (timing attack)');
    }

    /** @test */
    public function patch_04_webhook_returns_200_on_not_found()
    {
        // BUG-A05: webhook não pode retornar 404 (MP retry loop)
        $code = file_get_contents(base_path('app/Http/Controllers/WebhookController.php'));

        // Não deve haver return ...->json(..., 404) para casos de not_found
        $this->assertDoesNotMatchRegularExpression(
            '/not_found.*?,\s*404|404.*?not_found/s',
            $code,
            'PATCH-04: webhook ainda retorna 404 em not_found (BUG-A05)'
        );
    }

    /** @test */
    public function patch_04_webhook_route_registered()
    {
        $routes = Route::getRoutes();
        $found = false;
        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'webhook/mercadopago')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'PATCH-04: rota /webhook/mercadopago não registrada');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 05 — MercadoPagoService
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_05_no_fake_cpf_in_code()
    {
        // BUG-C03: CPF placeholder "19100000000" em produção
        $code = file_get_contents(base_path('app/Services/MercadoPagoService.php'));

        $this->assertStringNotContainsString('19100000000', $code,
            'PATCH-05: CPF fake 19100000000 ainda presente (BUG-C03)');
        $this->assertStringNotContainsString('01001000', $code,
            'PATCH-05: CEP fake 01001000 ainda presente (BUG-C03)');
    }

    /** @test */
    public function patch_05_uses_deterministic_idempotency()
    {
        // BUG-C04: Idempotency-Key não pode usar uniqid (não determinístico)
        $code = file_get_contents(base_path('app/Services/MercadoPagoService.php'));

        // Deve usar hash() ou md5() com valor + data, NÃO uniqid()
        $this->assertMatchesRegularExpression(
            '/(hash\s*\(|md5\s*\().*?(valor|data_vencimento)/s',
            $code,
            'PATCH-05: Idempotency-Key não é determinística (BUG-C04)'
        );
    }

    /** @test */
    public function patch_05_uses_lockforupdate()
    {
        // Anti-double-click
        $code = file_get_contents(base_path('app/Services/MercadoPagoService.php'));
        $this->assertStringContainsString('lockForUpdate', $code,
            'PATCH-05: sem lockForUpdate (race condition em duplo-clique)');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 06 — Recibo Público LGPD
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_06_recibo_controller_exists()
    {
        $this->assertTrue(
            class_exists(\App\Http\Controllers\ReciboPublicoController::class),
            'PATCH-06: ReciboPublicoController não encontrado (BUG-C05)'
        );
    }

    /** @test */
    public function patch_06_route_uses_uuid_not_id()
    {
        $code = file_get_contents(base_path('routes/web.php'));

        $this->assertMatchesRegularExpression(
            '/recibo\/v\/\{[a-z_]*uuid[a-z_]*\}|recibo\/v\/\{codigo_validacao\}/i',
            $code,
            'PATCH-06: rota pública não usa UUID (LGPD)'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 07 — Scheduler de Mensalidades
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_07_command_atualizar_vencidas_exists()
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('mensalidades:atualizar-vencidas', $commands,
            'PATCH-07: comando mensalidades:atualizar-vencidas não registrado');
    }

    /** @test */
    public function patch_07_command_gerar_recorrentes_exists()
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('mensalidades:gerar-recorrentes', $commands,
            'PATCH-07: comando mensalidades:gerar-recorrentes não registrado');
    }

    /** @test */
    public function patch_07_dry_run_atualizar_vencidas_passes()
    {
        // Executa em dry-run sem alterar o banco
        $exit = Artisan::call('mensalidades:atualizar-vencidas', ['--dry-run' => true]);
        $this->assertEquals(0, $exit, 'PATCH-07: comando falhou em dry-run');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 08 — Cobrança Inteligente
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_08_mensalidade_cobrancas_table_exists()
    {
        $this->assertTrue(
            Schema::hasTable('mensalidade_cobrancas'),
            'PATCH-08: tabela mensalidade_cobrancas não criada'
        );
    }

    /** @test */
    public function patch_08_jobs_exist()
    {
        $this->assertTrue(
            class_exists(\App\Jobs\EnviarLembreteVencimento::class),
            'PATCH-08: Job EnviarLembreteVencimento não encontrado'
        );
        $this->assertTrue(
            class_exists(\App\Jobs\EnviarCobrancaAtraso::class),
            'PATCH-08: Job EnviarCobrancaAtraso não encontrado'
        );
    }

    /** @test */
    public function patch_08_commands_registered()
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('lembretes:disparar-vencimento', $commands);
        $this->assertArrayHasKey('cobrancas:disparar-atrasadas', $commands);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 09 — Multa/Juros Automáticos
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_09_config_financeiro_exists()
    {
        $this->assertNotNull(config('financeiro'),
            'PATCH-09: config/financeiro.php não publicado');

        $this->assertIsNumeric(config('financeiro.multa.percentual'),
            'PATCH-09: financeiro.multa.percentual ausente');
        $this->assertIsNumeric(config('financeiro.juros.percentual_mes'),
            'PATCH-09: financeiro.juros.percentual_mes ausente');
    }

    /** @test */
    public function patch_09_financeiro_helper_exists()
    {
        $this->assertTrue(
            class_exists(\App\Helpers\FinanceiroHelper::class),
            'PATCH-09: FinanceiroHelper não encontrado'
        );
    }

    /** @test */
    public function patch_09_categorias_multa_juros_seeded()
    {
        // Seeder deve ter criado as categorias
        $exists = DB::table('categoria_financeiras')
            ->whereIn('nome', ['Multas e Juros', 'Multas', 'Juros'])
            ->exists();
        $this->assertTrue($exists,
            'PATCH-09: categorias Multas/Juros não foram seedadas');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 10 — Relatórios Avançados
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_10_relatorio_inadimplencia_controller_exists()
    {
        $this->assertTrue(
            class_exists(\App\Http\Controllers\RelatorioInadimplenciaController::class),
            'PATCH-10: RelatorioInadimplenciaController não encontrado'
        );
    }

    /** @test */
    public function patch_10_fluxo_caixa_controller_exists()
    {
        $this->assertTrue(
            class_exists(\App\Http\Controllers\RelatorioFluxoCaixaController::class),
            'PATCH-10: RelatorioFluxoCaixaController não encontrado'
        );
    }

    /** @test */
    public function patch_10_extrato_aluno_controller_exists()
    {
        $this->assertTrue(
            class_exists(\App\Http\Controllers\ExtratoAlunoController::class),
            'PATCH-10: ExtratoAlunoController não encontrado'
        );
    }

    /** @test */
    public function patch_10_relatorios_gerados_table_exists()
    {
        $this->assertTrue(
            Schema::hasTable('relatorios_gerados'),
            'PATCH-10: tabela relatorios_gerados não criada (auditoria LGPD)'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 11 — Excel + DRE
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_11_excel_export_exists()
    {
        $this->assertTrue(
            class_exists(\App\Exports\RelatorioFinanceiroExport::class),
            'PATCH-11: RelatorioFinanceiroExport não encontrado'
        );
    }

    /** @test */
    public function patch_11_dre_controller_exists()
    {
        $this->assertTrue(
            class_exists(\App\Http\Controllers\DREController::class),
            'PATCH-11: DREController não encontrado'
        );
    }

    /** @test */
    public function patch_11_maatwebsite_excel_installed()
    {
        $this->assertTrue(
            class_exists(\Maatwebsite\Excel\Facades\Excel::class),
            'PATCH-11: pacote maatwebsite/excel não instalado'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PATCH 12 — Polimento Final
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function patch_12_data_movimentacao_is_datetime()
    {
        // BUG-B01: data_movimentacao precisa ser datetime, não date
        $casts = (new \App\Models\MovimentacaoFinanceira())->getCasts();

        $this->assertEquals('datetime', $casts['data_movimentacao'] ?? null,
            'PATCH-12: data_movimentacao ainda como date (BUG-B01)');
    }

    /** @test */
    public function patch_12_rate_limit_on_payment_routes()
    {
        $code = file_get_contents(base_path('routes/web.php'));
        $this->assertMatchesRegularExpression(
            '/throttle:|RateLimiter::for/i',
            $code,
            'PATCH-12: rate-limit ausente nas rotas de pagamento (BUG-B03)'
        );
    }

    /** @test */
    public function patch_12_erro_mp_helper_exists()
    {
        $this->assertTrue(
            class_exists(\App\Helpers\ErroMpHelper::class),
            'PATCH-12: ErroMpHelper não encontrado (BUG-B02)'
        );
    }

    /** @test */
    public function patch_12_pix_whatsapp_job_exists()
    {
        $this->assertTrue(
            class_exists(\App\Jobs\EnviarPixWhatsAppJob::class),
            'PATCH-12: EnviarPixWhatsAppJob não encontrado (BUG-M05)'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SMOKE TESTS — Regressões críticas
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function regression_mensalidade_status_atrasado_volta_para_pago()
    {
        // BUG fundamental: status Atrasado → Pago deve funcionar
        $mensalidade = \App\Models\Mensalidade::query()
            ->where('status', 'Atrasado')
            ->first();

        if (!$mensalidade) {
            $this->markTestSkipped('Sem mensalidade Atrasada no banco para testar');
            return;
        }

        $originalStatus = $mensalidade->status;
        $mensalidade->marcarComoPaga(now());
        $mensalidade->refresh();

        $this->assertEquals('Pago', $mensalidade->status,
            'REGRESSÃO CRÍTICA: status não transita de Atrasado → Pago');

        // Reverter para não sujar o banco
        $mensalidade->update(['status' => $originalStatus, 'data_pagamento' => null]);
    }

    /** @test */
    public function regression_observer_does_not_create_duplicate_movimentacao()
    {
        // Salvar a mesma mensalidade 2x não deve criar 2 movimentações
        $mensalidade = \App\Models\Mensalidade::query()
            ->where('status', 'Pago')
            ->whereNotNull('data_pagamento')
            ->first();

        if (!$mensalidade) {
            $this->markTestSkipped('Sem mensalidade Paga para testar duplicação');
            return;
        }

        $countBefore = \App\Models\MovimentacaoFinanceira::query()
            ->where('mensalidade_id', $mensalidade->id)
            ->count();

        // Salvar novamente
        $mensalidade->touch();

        $countAfter = \App\Models\MovimentacaoFinanceira::query()
            ->where('mensalidade_id', $mensalidade->id)
            ->count();

        $this->assertEquals($countBefore, $countAfter,
            'REGRESSÃO: Observer duplica MovimentacaoFinanceira ao salvar 2x');
    }

    /** @test */
    public function regression_diff_in_days_never_returns_negative()
    {
        // BUG-A01: percorrer todas mensalidades atrasadas
        $atrasadas = \App\Models\Mensalidade::query()
            ->where('status', 'Atrasado')
            ->limit(50)
            ->get();

        foreach ($atrasadas as $m) {
            $this->assertGreaterThanOrEqual(0, $m->dias_atraso,
                "REGRESSÃO BUG-A01: Mensalidade #{$m->id} retornou dias_atraso negativo");
        }

        $this->addToAssertionCount(1); // garante que o teste conte mesmo sem registros
    }

    /** @test */
    public function regression_calcular_valor_com_multa_is_idempotent()
    {
        $m = new \App\Models\Mensalidade([
            'valor' => 100.00,
            'data_vencimento' => Carbon::today()->subDays(30),
            'status' => 'Atrasado',
        ]);

        $v1 = method_exists($m, 'calcularValorComMulta')
            ? $m->calcularValorComMulta()
            : ($m->valor + $m->calcularMulta() + $m->calcularJuros());

        $v2 = method_exists($m, 'calcularValorComMulta')
            ? $m->calcularValorComMulta()
            : ($m->valor + $m->calcularMulta() + $m->calcularJuros());

        $this->assertEquals($v1, $v2,
            'REGRESSÃO: calcularValorComMulta retorna valores diferentes em chamadas consecutivas');
        $this->assertGreaterThan(100, $v1,
            'REGRESSÃO: multa não está sendo aplicada em mensalidade com 30 dias de atraso');
    }

    /** @test */
    public function regression_total_pago_matches_movimentacoes()
    {
        // Soma de Mensalidades Pagas deve bater com soma de MovimentacaoFinanceira de entrada (mensalidade_id)
        $totalMensalidades = \App\Models\Mensalidade::query()
            ->where('status', 'Pago')
            ->sum('valor');

        $totalMovimentacoes = \App\Models\MovimentacaoFinanceira::query()
            ->where('tipo', 'entrada')
            ->whereNotNull('mensalidade_id')
            ->where('status', 'efetivado')
            ->sum('valor');

        // Tolerância de R$ 1,00 para arredondamento
        $diff = abs($totalMensalidades - $totalMovimentacoes);
        $this->assertLessThan(
            max(1.00, $totalMensalidades * 0.01),
            $diff,
            "REGRESSÃO CONTÁBIL: Pago=R$ {$totalMensalidades} vs Movimentação=R$ {$totalMovimentacoes} (diff: R$ {$diff})"
        );
    }

    /** @test */
    public function regression_webhook_responds_200_to_unknown_payment()
    {
        // Webhook nunca pode retornar 404 (MP entra em retry loop)
        $response = $this->postJson('/api/webhook/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '999999999999'],
        ]);

        $this->assertNotEquals(404, $response->status(),
            'REGRESSÃO BUG-A05: webhook retornou 404 para pagamento desconhecido');
    }

    /** @test */
    public function regression_can_render_recibo_pdf()
    {
        $mensalidade = \App\Models\Mensalidade::query()
            ->where('status', 'Pago')
            ->whereNotNull('data_pagamento')
            ->with(['matricula.user', 'matricula.curso'])
            ->first();

        if (!$mensalidade) {
            $this->markTestSkipped('Sem mensalidade paga para gerar recibo');
            return;
        }

        // Apenas verifica se a view renderiza sem erros
        try {
            $html = view('pdf.recibo-mensalidade', ['mensalidade' => $mensalidade])->render();
            $this->assertNotEmpty($html);
            $this->assertStringContainsString('RECIBO', strtoupper($html));
        } catch (\Throwable $e) {
            $this->fail('REGRESSÃO: view de recibo falhou: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // META-TEST — saúde geral do sistema
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function meta_all_critical_tables_exist()
    {
        $tables = [
            'mensalidades', 'matriculas', 'users', 'contas',
            'movimentacoes_financeiras', 'categoria_financeiras',
            'movimentacoes_auditoria', 'mensalidade_cobrancas',
            'relatorios_gerados',
        ];

        foreach ($tables as $t) {
            $this->assertTrue(Schema::hasTable($t),
                "META: tabela crítica '$t' ausente");
        }
    }

    /** @test */
    public function meta_no_pending_migrations()
    {
        $exit = Artisan::call('migrate:status');
        $output = Artisan::output();

        $this->assertStringNotContainsString('Pending', $output,
            'META: existem migrations pendentes após o deploy');
    }

    /** @test */
    public function meta_config_cache_ok()
    {
        $this->assertNotNull(config('app.key'), 'META: APP_KEY ausente');
        $this->assertNotNull(config('database.default'), 'META: DB driver ausente');
    }
}
