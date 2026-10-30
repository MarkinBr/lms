<?php

namespace Tests\Feature\Secretaria;

use App\Models\User;
use App\Services\Acessos\AtendimentoAluno;
use App\Services\Acessos\CredenciaisAluno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CredenciaisAcessoTest extends TestCase
{
    use RefreshDatabase;

    private User $ator;

    private User $aluno;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        if (! Route::has('aluno.assistido.formulario') && is_file(base_path('routes/acesso-assistido.php'))) {
            Route::middleware('web')->group(base_path('routes/acesso-assistido.php'));
        }
        Route::middleware(['web', 'auth'])->get('/teste-sessao-acesso', fn () => 'autorizado');
        Route::getRoutes()->refreshNameLookups();
        $this->ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $this->aluno = User::factory()->create([
            'nivel' => 'aluno', 'ativo' => true, 'matricula_login' => 'cets126001',
            'cpf' => '52998224725', 'password' => Hash::make('SenhaAntiga#2026'),
        ]);
        $this->aluno->forceFill(['whatsapp' => '5511999990000'])->save();
    }

    public function test_schema_persiste_somente_hash_e_controle_de_uso(): void
    {
        $this->assertTrue(Schema::hasTable('acesso_credenciais'));
        $this->assertTrue(Schema::hasColumns('acesso_credenciais', ['segredo_hash', 'tentativas', 'acesso_versao', 'consumida_em', 'revogada_em', 'concluida_em']));
    }

    public function test_filament_recusa_sessao_com_versao_revogada(): void
    {
        $this->actingAs($this->aluno)->withSession(['aluno_acesso_versao' => 0]);
        $this->get('/aluno')->assertOk();
        AtendimentoAluno::revogar($this->aluno->fresh());
        $this->get('/aluno')->assertRedirect(route('aluno.login'));
        $this->assertGuest();
    }

    public function test_login_legitimo_registra_versao_e_abre_filament_apos_revogacoes(): void
    {
        AtendimentoAluno::revogar($this->aluno->fresh());
        $this->post(route('aluno.login.submit'), ['email' => $this->aluno->matricula_login, 'password' => 'SenhaAntiga#2026'])->assertRedirect(route('aluno.dashboard'))->assertSessionHas('aluno_acesso_versao', 1);
        $this->get('/aluno')->assertOk();
    }

    #[DataProvider('rotasRemember')]
    public function test_remember_valido_restaura_sessao_com_a_versao_atual(string $rota): void
    {
        AtendimentoAluno::revogar($this->aluno->fresh());
        $aluno = $this->aluno->fresh();
        $guard = Auth::guard('web');
        $recaller = $aluno->id.'|'.$aluno->remember_token.'|'.$guard->hashPasswordForCookie($aluno->password);
        $this->withCookie($guard->getRecallerName(), $recaller)->get($rota)->assertOk()->assertSessionHas('aluno_acesso_versao', 1);
        $this->assertAuthenticatedAs($aluno);
    }

    public static function rotasRemember(): array
    {
        return [['/teste-sessao-acesso'], ['/aluno']];
    }

    public function test_remember_anterior_a_revogacao_nao_restaura_autenticacao(): void
    {
        $guard = Auth::guard('web');
        $recaller = $this->aluno->id.'|'.$this->aluno->remember_token.'|'.$guard->hashPasswordForCookie($this->aluno->password);
        AtendimentoAluno::revogar($this->aluno->fresh());
        $this->withCookie($guard->getRecallerName(), $recaller)->get('/teste-sessao-acesso')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_reset_convencional_conclui_pendencia_e_revoga_credencial_assistida(): void
    {
        $emissao = $this->emitir();
        $token = Password::broker()->createToken($this->aluno->fresh());
        $this->post(route('password.update'), ['email' => $this->aluno->email, 'token' => $token] + $this->senha())->assertRedirect(route('aluno.login'));
        $atual = $this->aluno->fresh();
        $this->assertFalse((bool) $atual->senha_definitiva_pendente);
        $this->assertSame(2, (int) $atual->acesso_versao);
        $this->assertFalse(app(CredenciaisAluno::class)->vigente($emissao['id']));
        $this->assertNull(app(CredenciaisAluno::class)->consumirTemporaria($atual, $emissao['segredo']));
        $this->post(route('aluno.login.submit'), ['email' => $atual->matricula_login, 'password' => 'SenhaDefinitiva#2026'])->assertRedirect(route('aluno.dashboard'));
    }

    public function test_link_da_mensagem_fica_em_linha_propria_sem_pontuacao(): void
    {
        $emissao = $this->emitir('ativacao', 'whatsapp');
        $this->assertContains($emissao['url'], explode("\n", $emissao['mensagem']));
    }

    public function test_foto_privada_do_proprio_aluno_e_avatar_nao_usam_url_publica(): void
    {
        $foto = 'acessos/fotos/aluno126.png';
        Storage::fake('credenciais-fotos');
        Storage::set('local', Storage::disk('credenciais-fotos'));
        Storage::disk('local')->put($foto, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1cAAAAASUVORK5CYII='));
        $this->aluno->forceFill(['foto' => $foto])->save();
        $this->assertNotNull($this->aluno->foto_url);
        $this->assertStringNotContainsString('/storage/', $this->aluno->foto_url);
        $this->actingAs($this->aluno)->get($this->aluno->foto_url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame($this->aluno->foto_url, $this->aluno->getFilamentAvatarUrl());
        $this->get(route('aluno.perfil'))->assertOk()->assertSee($this->aluno->foto_url, false)->assertDontSee(asset('storage/'.$foto), false);
    }

    public function test_foto_privada_nao_e_exposta_a_outro_aluno_staff_ou_visitante(): void
    {
        $this->aluno->forceFill(['foto' => 'acessos/fotos/privada126.png'])->save();
        $this->assertNotNull($this->aluno->foto_url);
        $this->get($this->aluno->foto_url)->assertRedirect(route('login'));
        $outro = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $this->actingAs($outro)->get($this->aluno->foto_url)->assertForbidden();
        $this->actingAs($this->ator)->get($this->aluno->foto_url)->assertForbidden();
    }

    public function test_foto_recusa_traversal_e_documentos_privados_e_preserva_foto_publica_legada(): void
    {
        $this->aluno->forceFill(['foto' => 'alunos/legado126.jpg'])->save();
        $this->assertSame(asset('storage/alunos/legado126.jpg'), $this->aluno->foto_url);
        $this->actingAs($this->aluno);
        foreach (['acessos/fotos/../documento.png', 'applications/documento.png'] as $foto) {
            $this->aluno->forceFill(['foto' => $foto])->save();
            $this->get('/portal/perfil/foto-privada/'.$this->aluno->id)->assertNotFound();
        }
    }

    public function test_codigo_com_byte_nulo_nao_e_aceito_por_truncamento_do_bcrypt(): void
    {
        $emissao = $this->emitir();
        $this->assertNull(app(CredenciaisAluno::class)->consumirTemporaria($this->aluno, $emissao['segredo']."\0indevido"));
        $this->assertNull(DB::table('acesso_credenciais')->find($emissao['id'])->consumida_em);
        $this->assertSame(1, (int) DB::table('acesso_credenciais')->find($emissao['id'])->tentativas);
    }

    public function test_sessao_inativa_e_encerrada_preservando_resposta_403(): void
    {
        $this->actingAs($this->aluno)->withSession(['aluno_acesso_versao' => 0]);
        AtendimentoAluno::revogar($this->aluno->fresh());
        DB::table('users')->where('id', $this->aluno->id)->update(['ativo' => false]);
        $this->get('/teste-sessao-acesso')->assertForbidden();
        $this->assertGuest();
    }

    #[DataProvider('canaisCredenciais')]
    public function test_canais_tipos_e_preservacao_de_email(string $tipo, string $canal): void
    {
        $this->aluno->forceFill(['email_verified_at' => null])->save();
        if ($tipo === 'recuperacao' && $canal === 'email') {
            $this->aluno->forceFill(['email_verified_at' => now()])->save();
        }
        $emissao = $this->emitir($tipo, $canal);
        $this->assertTrue(app(CredenciaisAluno::class)->vigente($emissao['id']));
        $this->get($emissao['url'])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        if ($tipo === 'temporaria') {
            $this->post(route('aluno.assistido.entrar'), ['identificador' => $this->aluno->matricula_login, 'codigo' => $emissao['segredo']])->assertRedirect();
        } else {
            $this->post(route('aluno.assistido.comprovar', $emissao['id']), ['segredo' => $emissao['segredo']])->assertRedirect();
        }
        $this->assertFalse(app(CredenciaisAluno::class)->vigente($emissao['id']));
        $this->post(route('aluno.assistido.definir'), $this->senha())->assertRedirect(route('aluno.login'));
        $verificado = $this->aluno->fresh()->email_verified_at !== null;
        $this->assertSame($canal === 'email' && $tipo !== 'temporaria', $verificado);
    }

    public static function canaisCredenciais(): array
    {
        return [['ativacao', 'email'], ['ativacao', 'whatsapp'], ['recuperacao', 'email'], ['recuperacao', 'whatsapp'], ['temporaria', 'email'], ['temporaria', 'whatsapp']];
    }

    public function test_bloqueio_ou_troca_de_perfil_rejeita_credenciais_e_autorizacao_restrita(): void
    {
        $emissao = $this->emitir();
        $this->post(route('aluno.assistido.entrar'), ['identificador' => $this->aluno->matricula_login, 'codigo' => $emissao['segredo']])->assertRedirect();
        $this->aluno->forceFill(['ativo' => false])->save();
        $this->get(route('aluno.assistido.senha'))->assertForbidden();
        $this->post(route('aluno.assistido.definir'), $this->senha())->assertForbidden();
        $this->aluno->forceFill(['ativo' => true, 'nivel' => 'professor'])->save();
        $this->post(route('aluno.assistido.definir'), $this->senha())->assertForbidden();
        $this->assertGuest();
    }

    public function test_servico_recusa_aluno_inativo_e_alvo_nao_aluno(): void
    {
        foreach ([['ativo' => false], ['ativo' => true, 'nivel' => 'admin']] as $campos) {
            $this->aluno->forceFill($campos)->save();
            try {
                $this->emitir();
                $this->fail('Alvo inelegível deve ser negado.');
            } catch (HttpException $erro) {
                $this->assertSame(403, $erro->getStatusCode());
            }
        }
        $this->assertDatabaseCount('acesso_credenciais', 0);
    }

    public function test_email_alterado_apos_prova_invalida_definicao_e_nao_verifica_novo_endereco(): void
    {
        $this->aluno->forceFill(['email_verified_at' => null])->save();
        $emissao = $this->emitir('ativacao', 'email');
        $this->post(route('aluno.assistido.comprovar', $emissao['id']), ['segredo' => $emissao['segredo']])->assertRedirect();
        $this->aluno->forceFill(['email' => 'outro126@example.test'])->save();
        $this->post(route('aluno.assistido.definir'), $this->senha())->assertForbidden();
        $this->assertNull($this->aluno->fresh()->email_verified_at);
    }

    public function test_reemissao_invalida_link_e_revogacao_do_ator_cancela_entrega(): void
    {
        $primeira = $this->emitir('ativacao', 'whatsapp');
        $this->travel(61)->seconds();
        $segunda = $this->emitir('recuperacao', 'whatsapp');
        $this->assertFalse(app(CredenciaisAluno::class)->vigente($primeira['id']));
        $this->assertNull(app(CredenciaisAluno::class)->consumirLink($primeira['id'], $primeira['segredo']));
        $this->ator->forceFill(['ativo' => false])->save();
        $this->assertFalse(app(CredenciaisAluno::class)->vigente($segunda['id']));
    }

    public function test_emissao_limitada_e_erro_nao_grava_segredo_na_sessao(): void
    {
        $emissao = $this->emitir();
        try {
            $this->emitir();
            $this->fail('Emissão consecutiva deve ser limitada.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('acesso_credenciais', 1);
        }
        $codigo = substr($emissao['segredo'], 0, 4).'-'.substr($emissao['segredo'], 4);
        $this->post(route('aluno.assistido.entrar'), ['identificador' => $this->aluno->matricula_login, 'codigo' => $codigo])->assertSessionHasErrors('codigo')->assertSessionMissing('_old_input.codigo');
        $this->assertStringNotContainsString($codigo, json_encode(session()->all()));
    }

    public function test_temporaria_armazena_so_hash_e_revoga_senha_sessoes_e_broker(): void
    {
        $token = Password::broker()->createToken($this->aluno);
        $emissao = $this->emitir();
        $this->assertMatchesRegularExpression('/^[0-9]{8}$/D', $emissao['segredo']);
        $this->assertEqualsWithDelta(now()->addMinutes(30)->timestamp, $emissao['expira_em']->timestamp, 2);
        $registro = DB::table('acesso_credenciais')->find($emissao['id']);
        $this->assertTrue(Hash::check($emissao['segredo'], $registro->segredo_hash));
        $this->assertStringNotContainsString($emissao['segredo'], json_encode($registro));
        $this->assertStringNotContainsString($emissao['segredo'], DB::table('acesso_auditorias')->get()->toJson());
        $this->assertFalse(Hash::check('SenhaAntiga#2026', $this->aluno->fresh()->password));
        $this->assertTrue((bool) $this->aluno->fresh()->senha_definitiva_pendente);
        $this->assertSame(1, (int) $this->aluno->fresh()->acesso_versao);
        $this->assertFalse(Password::broker()->tokenExists($this->aluno, $token));
    }

    public function test_temporaria_cria_somente_sessao_restrita_e_exige_senha_forte(): void
    {
        $emissao = $this->emitir();
        $this->post(route('aluno.login.submit'), ['email' => $this->aluno->matricula_login, 'password' => chunk_split($emissao['segredo'], 4, ' ')])
            ->assertRedirect(route('aluno.assistido.senha'));
        $this->assertGuest();
        $this->get('/teste-sessao-acesso')->assertRedirect(route('login'));
        $this->post(route('aluno.assistido.definir'), $this->senha('12345678'))->assertSessionHasErrors('password');
        $this->post(route('aluno.assistido.definir'), $this->senha())->assertRedirect(route('aluno.login'));
        $this->assertGuest();
        $this->assertFalse((bool) $this->aluno->fresh()->senha_definitiva_pendente);
        $this->assertTrue(Hash::check('SenhaDefinitiva#2026', $this->aluno->fresh()->password));
        $this->post(route('aluno.login.submit'), ['email' => $this->aluno->matricula_login, 'password' => 'SenhaDefinitiva#2026'])
            ->assertRedirect(route('aluno.dashboard'));
        $this->get('/teste-sessao-acesso')->assertOk();
    }

    public function test_cinco_erros_invalidam_credencial_mesmo_com_codigo_correto_depois(): void
    {
        $emissao = $this->emitir();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('aluno.assistido.entrar'), ['identificador' => $this->aluno->matricula_login, 'codigo' => 'errado'])->assertSessionHasErrors('codigo');
        }
        $this->assertSame(5, (int) DB::table('acesso_credenciais')->find($emissao['id'])->tentativas);
        $this->assertNull(app(CredenciaisAluno::class)->consumirTemporaria($this->aluno, $emissao['segredo']));
        $this->assertGuest();
    }

    public function test_reemissao_nao_apaga_limite_agregado_da_conta(): void
    {
        $this->emitir();
        $servico = app(CredenciaisAluno::class);
        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($servico->consumirTemporaria($this->aluno, 'invalido'));
        }
        $this->travel(61)->seconds();
        $nova = $this->emitir();
        $this->assertNull($servico->consumirTemporaria($this->aluno, $nova['segredo']));
    }

    public function test_expiracao_e_consumo_unico_sao_verificados_no_servidor(): void
    {
        $emissao = $this->emitir();
        $servico = app(CredenciaisAluno::class);
        $this->assertNotNull($servico->consumirTemporaria($this->aluno, $emissao['segredo']));
        $this->assertNull($servico->consumirTemporaria($this->aluno, $emissao['segredo']));
        $this->travel(61)->seconds();
        $outra = $this->emitir();
        $this->travel(30)->minutes();
        $this->assertNull($servico->consumirTemporaria($this->aluno, $outra['segredo']));
    }

    public function test_reemissao_e_revogacao_invalidam_links_e_sessoes_restritas(): void
    {
        $primeira = $this->emitir();
        $this->post(route('aluno.assistido.entrar'), ['identificador' => $this->aluno->cpf, 'codigo' => $primeira['segredo']])->assertRedirect();
        $this->travel(61)->seconds();
        $segunda = $this->emitir();
        $this->post(route('aluno.assistido.definir'), $this->senha())->assertForbidden();
        $this->assertNull(app(CredenciaisAluno::class)->consumirTemporaria($this->aluno, $primeira['segredo']));
        AtendimentoAluno::revogar($this->aluno->fresh());
        $this->assertNull(app(CredenciaisAluno::class)->consumirTemporaria($this->aluno, $segunda['segredo']));
    }

    public function test_whatsapp_ativa_sem_confirmar_email_e_link_nao_consume_por_get(): void
    {
        $this->aluno->forceFill(['email_verified_at' => null, 'primeiro_acesso_pendente' => true])->save();
        $emissao = $this->emitir('ativacao', 'whatsapp');
        $this->assertGreaterThanOrEqual(64, strlen($emissao['segredo']));
        $this->assertStringContainsString('#segredo=', $emissao['url']);
        $this->get(route('aluno.assistido.link', $emissao['id']))->assertOk();
        $this->assertNull(DB::table('acesso_credenciais')->find($emissao['id'])->consumida_em);
        $this->post(route('aluno.assistido.comprovar', $emissao['id']), ['segredo' => $emissao['segredo']])->assertRedirect(route('aluno.assistido.senha'));
        $this->post(route('aluno.assistido.definir'), $this->senha())->assertRedirect(route('aluno.login'));
        $this->assertNull($this->aluno->fresh()->email_verified_at);
        $this->assertFalse($this->aluno->fresh()->primeiro_acesso_pendente);
        $this->post(route('aluno.assistido.comprovar', $emissao['id']), ['segredo' => $emissao['segredo']])->assertForbidden();
    }

    public function test_email_ativacao_confirma_apenas_endereco_provado_pelo_token(): void
    {
        $this->aluno->forceFill(['email_verified_at' => null, 'primeiro_acesso_pendente' => true])->save();
        $emissao = $this->emitir('ativacao', 'email');
        $this->assertNull($this->aluno->fresh()->email_verified_at);
        $this->post(route('aluno.assistido.comprovar', $emissao['id']), ['segredo' => $emissao['segredo']])->assertRedirect();
        $this->post(route('aluno.assistido.definir'), $this->senha())->assertRedirect(route('aluno.login'));
        $this->assertNotNull($this->aluno->fresh()->email_verified_at);
    }

    public function test_destino_alterado_invalida_entrega_e_consumo(): void
    {
        $emissao = $this->emitir('recuperacao', 'whatsapp');
        $this->assertTrue(app(CredenciaisAluno::class)->vigenteParaEntrega($emissao['id']));
        $this->aluno->forceFill(['whatsapp' => '5511888880000'])->save();
        $this->assertFalse(app(CredenciaisAluno::class)->vigenteParaEntrega($emissao['id']));
        $this->post(route('aluno.assistido.comprovar', $emissao['id']), ['segredo' => $emissao['segredo']])->assertForbidden();
    }

    public function test_recuperacao_email_exige_email_confirmado_e_exclusivo(): void
    {
        $this->aluno->forceFill(['email_verified_at' => null])->save();
        try {
            $this->emitir('recuperacao', 'email');
            $this->fail('E-mail não confirmado deve ser recusado.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('acesso_credenciais', 0);
        }
        $this->aluno->forceFill(['email_verified_at' => now()])->save();
        User::factory()->create(['email' => mb_strtoupper($this->aluno->email), 'nivel' => 'professor']);
        $this->expectException(ValidationException::class);
        $this->emitir('recuperacao', 'email');
    }

    public function test_whatsapp_exige_campo_explicito_e_conta_ativa(): void
    {
        $this->aluno->forceFill(['whatsapp' => null, 'telefone' => '11999990000'])->save();
        $this->expectException(ValidationException::class);
        $this->emitir('ativacao', 'whatsapp');
    }

    public function test_servico_reautoriza_ator_e_aluno_do_banco(): void
    {
        DB::table('users')->where('id', $this->ator->id)->update(['ativo' => false]);
        $this->expectException(HttpException::class);
        $this->emitir();
    }

    public function test_sessao_antiga_sem_versao_so_e_aceita_na_versao_zero(): void
    {
        $this->actingAs($this->aluno)->get('/teste-sessao-acesso')->assertOk();
        AtendimentoAluno::revogar($this->aluno->fresh());
        $this->get('/teste-sessao-acesso')->assertRedirect(route('aluno.login'));
        $this->assertGuest();
    }

    public function test_sessao_com_versao_obsoleta_nao_acessa_rota_web_generica(): void
    {
        $this->actingAs($this->aluno)->withSession(['aluno_acesso_versao' => 0]);
        AtendimentoAluno::revogar($this->aluno->fresh());
        $this->get('/teste-sessao-acesso')->assertRedirect(route('aluno.login'));
    }

    public function test_senha_pendente_nao_permite_login_normal_mesmo_com_hash_valido(): void
    {
        $this->aluno->forceFill(['senha_definitiva_pendente' => true])->save();
        $this->post(route('aluno.login.submit'), ['email' => $this->aluno->matricula_login, 'password' => 'SenhaAntiga#2026'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sso_nao_contorna_pendencia_nem_muda_perfil_de_usuario_existente(): void
    {
        config(['services.cetsadp.sso_secret' => 'segredo-sintetico-isolado']);
        Http::fake(['*' => Http::response(['valid' => true, 'user' => ['identifier' => $this->aluno->cpf, 'name' => 'Aluno', 'type' => 'aluno']])]);
        $this->aluno->forceFill(['senha_definitiva_pendente' => true])->save();
        $this->get('/sso/callback?token='.str_repeat('a', 64))->assertRedirect();
        $this->assertGuest();
        $this->aluno->forceFill(['senha_definitiva_pendente' => false, 'nivel' => 'secretario'])->save();
        $this->get('/sso/callback?token='.str_repeat('b', 64))->assertRedirect();
        $this->assertGuest();
        $this->assertSame('secretario', $this->aluno->fresh()->nivel);
    }

    private function emitir(string $tipo = 'temporaria', string $canal = 'presencial'): array
    {
        return app(CredenciaisAluno::class)->emitir($this->ator, $this->aluno, $tipo, $canal, 'Identidade conferida no atendimento');
    }

    private function senha(string $senha = 'SenhaDefinitiva#2026'): array
    {
        return ['password' => $senha, 'password_confirmation' => $senha];
    }
}
