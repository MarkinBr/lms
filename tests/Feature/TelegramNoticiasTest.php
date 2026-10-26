<?php

namespace Tests\Feature;

use App\Models\Noticia;
use App\Models\User;
use App\Services\Telegram\ConfirmacoesTelegram;
use App\Services\Telegram\NoticiasAdministrativasTelegram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TelegramNoticiasTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Notification::fake();
        config()->set('services.telegram.bot_token', 'token-de-teste');
        config()->set('services.telegram.webhook_secret', 'segredo-de-teste');
        $this->administrador = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        config()->set('services.telegram.admin_user_id', $this->administrador->id);
        DB::table('telegram_vinculos')->insert([
            'user_id' => $this->administrador->id, 'chat_id' => '707',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function preparar(string $conteudo = 'Nova turma | Inscrições abertas | Aulas começam em novembro.', int $updateId = 12001): array
    {
        return app(NoticiasAdministrativasTelegram::class)->preparar('707', '/novanoticia', $conteudo, $updateId);
    }

    private function dadosPendentes(): array
    {
        return json_decode(Crypt::decryptString(DB::table('telegram_publicacoes')->value('dados_criptografados')), true, 512, JSON_THROW_ON_ERROR);
    }

    private function executar(array $dados, string $acao = 'publicar_noticia'): ?Noticia
    {
        return DB::transaction(fn () => app(NoticiasAdministrativasTelegram::class)
            ->executar($acao, $dados, $this->administrador));
    }

    public function test_preparacao_exibe_conteudo_completo_e_cria_apenas_proposta_criptografada(): void
    {
        $resposta = $this->preparar();

        $this->assertStringContainsString('Nova turma', $resposta['text']);
        $this->assertStringContainsString('Inscrições abertas', $resposta['text']);
        $this->assertStringContainsString('Aulas começam em novembro.', $resposta['text']);
        $this->assertStringStartsWith('confirmar:', $resposta['reply_markup']['inline_keyboard'][0][0]['callback_data']);
        $this->assertStringStartsWith('cancelar:', $resposta['reply_markup']['inline_keyboard'][0][1]['callback_data']);
        $this->assertDatabaseCount('noticias', 0);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'publicar_noticia', 'status' => 'pendente']);
        $this->assertStringNotContainsString('Aulas começam', DB::table('telegram_publicacoes')->value('dados_criptografados'));
        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_executor_publica_com_autoria_somente_depois_da_preparacao(): void
    {
        $this->preparar();
        $noticia = $this->executar($this->dadosPendentes());

        $this->assertInstanceOf(Noticia::class, $noticia);
        $this->assertDatabaseCount('noticias', 1);
        $this->assertDatabaseHas('noticias', [
            'titulo' => 'Nova turma', 'resumo' => 'Inscrições abertas',
            'texto' => 'Aulas começam em novembro.', 'publicada' => true,
            'publicada_por' => $this->administrador->id,
        ]);
        $this->assertNotNull($noticia->publicada_em);
        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_webhook_confirma_publicacao_uma_unica_vez_e_registra_recurso(): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'segredo-de-teste')
            ->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => 12010, 'message' => [
                    'chat' => ['id' => 707, 'type' => 'private'], 'from' => ['id' => 707],
                    'text' => '/novanoticia Publicação pelo bot | Resumo da notícia | Texto integral.',
                ],
            ])->assertOk();
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'publicar_noticia', 'status' => 'pendente']);
        $this->assertDatabaseCount('noticias', 0);
        $codigo = Http::recorded()->last()[0]['reply_markup']['inline_keyboard'][0][0]['callback_data'];
        foreach ([12011, 12012] as $updateId) {
            $this->postJson('/api/telegram/cetsadp/webhook', [
                'update_id' => $updateId, 'callback_query' => [
                    'id' => 'noticia-'.$updateId, 'from' => ['id' => 707],
                    'message' => ['message_id' => 31, 'chat' => ['id' => 707, 'type' => 'private']],
                    'data' => $codigo,
                ],
            ])->assertOk();
        }

        $this->assertDatabaseCount('noticias', 1);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
        $this->assertDatabaseHas('telegram_confirmacoes', [
            'acao' => 'publicar_noticia', 'status' => 'confirmado',
            'recurso_tipo' => 'noticias', 'recurso_id' => Noticia::firstOrFail()->id,
        ]);
        $this->get(route('site.index'))->assertOk()->assertSee('Publicação pelo bot')->assertSee('Texto integral.');
    }

    public function test_cancelamento_e_expiracao_descartam_proposta_sem_publicar(): void
    {
        $resposta = $this->preparar();
        $codigo = substr($resposta['reply_markup']['inline_keyboard'][0][0]['callback_data'], strlen('confirmar:'));
        app(ConfirmacoesTelegram::class)->decidir('707', 'cancelar', $codigo, 12002);
        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'publicar_noticia', 'status' => 'cancelado']);

        $resposta = $this->preparar('Outra notícia | Resumo | Texto', 12003);
        $codigo = substr($resposta['reply_markup']['inline_keyboard'][0][0]['callback_data'], strlen('confirmar:'));
        DB::table('telegram_confirmacoes')->where('status', 'pendente')->update(['expira_em' => now()->subMinute()]);
        app(ConfirmacoesTelegram::class)->decidir('707', 'confirmar', $codigo, 12004);

        $this->assertDatabaseHas('telegram_confirmacoes', ['acao' => 'publicar_noticia', 'status' => 'expirado']);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
        $this->assertDatabaseCount('noticias', 0);
    }

    #[DataProvider('entradasInvalidas')]
    public function test_entrada_invalida_nao_prepara_publicacao(string $conteudo): void
    {
        $resposta = $this->preparar($conteudo);

        $this->assertStringContainsString('/novanoticia', $resposta['text']);
        $this->assertArrayNotHasKey('reply_markup', $resposta);
        $this->assertDatabaseCount('telegram_confirmacoes', 0);
        $this->assertDatabaseCount('telegram_publicacoes', 0);
        $this->assertDatabaseCount('noticias', 0);
    }

    public static function entradasInvalidas(): array
    {
        return [
            'sem separadores' => ['Notícia sem separadores'],
            'sem texto' => ['Título | Resumo |   '],
            'sem resumo' => ['Título |  | Texto'],
            'sem título' => [' | Resumo | Texto'],
            'título longo' => [str_repeat('á', 201).' | Resumo | Texto'],
            'resumo longo' => ['Título | '.str_repeat('á', 501).' | Texto'],
            'corpo longo' => ['Título | Resumo | '.str_repeat('á', 2001)],
            'controle no título' => ["Título\nquebrado | Resumo | Texto"],
            'controle no corpo' => ["Título | Resumo | Texto\x00oculto"],
        ];
    }

    public function test_limites_multibyte_e_separador_no_corpo_sao_preservados(): void
    {
        $texto = str_repeat('á', 1993)."\nA | B!";
        $this->preparar(str_repeat('á', 200).' | '.str_repeat('é', 500).' | '.$texto);
        $noticia = $this->executar($this->dadosPendentes());

        $this->assertSame($texto, $noticia->texto);
        $this->assertSame(200, mb_strlen($noticia->titulo));
        $this->assertSame(500, mb_strlen($noticia->resumo));
    }

    public function test_comando_desconhecido_nao_prepara_publicacao(): void
    {
        app(NoticiasAdministrativasTelegram::class)->preparar('707', '/novoaviso', 'Título | Resumo | Texto', 12001);

        $this->assertDatabaseCount('telegram_confirmacoes', 0);
    }

    public function test_payload_alterado_apos_previa_retorna_obsoleto(): void
    {
        $this->preparar();
        $dados = $this->dadosPendentes();
        $dados['texto'] = 'Texto diferente da confirmação';

        $this->assertNull($this->executar($dados));
        $this->assertDatabaseCount('noticias', 0);
    }

    public function test_executor_revalida_formato_mesmo_com_payload_incompleto(): void
    {
        $this->assertNull($this->executar(['titulo' => ['inválido'], 'texto' => 'texto']));
        $this->assertDatabaseCount('noticias', 0);
    }

    public function test_acao_desconhecida_nao_publica(): void
    {
        $this->preparar();

        $this->assertNull($this->executar($this->dadosPendentes(), 'publicar_aviso'));
        $this->assertDatabaseCount('noticias', 0);
    }

    public function test_executor_revalida_autor_que_perdeu_nivel_admin(): void
    {
        $this->preparar();
        $dados = $this->dadosPendentes();
        DB::table('users')->where('id', $this->administrador->id)->update(['nivel' => 'aluno']);

        $this->assertNull($this->executar($dados));
        $this->assertDatabaseCount('noticias', 0);
    }

    public function test_noticia_publicada_aparece_na_spa_real_escapada_sem_apagar_materias_existentes(): void
    {
        $this->preparar('<b>Comunicado</b> | <img src=x onerror=alert(1)> | <script>alert("teste")</script>'."\nSegunda linha.");
        $noticia = $this->executar($this->dadosPendentes());

        $this->get(route('site.index'))->assertOk()
            ->assertSee('data-page="noticias"', false)
            ->assertSee('id="noticia-'.$noticia->id.'"', false)
            ->assertSee('<b>Comunicado</b>')
            ->assertSee('<img src=x onerror=alert(1)>')
            ->assertSee('<script>alert("teste")</script>')
            ->assertSee('Segunda linha.')
            ->assertDontSee('<script>alert("teste")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('CETSADP abre matrículas para o segundo semestre de 2026')
            ->assertSee('Novo acervo teológico chega à biblioteca do centro');

        $dom = new \DOMDocument;
        @$dom->loadHTML(view('site.pages.noticias')->render());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//div[@class="news-grid"]/article[@id="noticia-'.$noticia->id.'" and @data-cat="inst"]//details/summary')->length);
    }

    public function test_site_exibe_apenas_publicadas_ate_agora_em_ordem_recente(): void
    {
        Noticia::create(['titulo' => 'Rascunho secreto', 'resumo' => 'Resumo', 'texto' => 'Texto', 'publicada' => false]);
        Noticia::create(['titulo' => 'Notícia futura', 'resumo' => 'Resumo', 'texto' => 'Texto', 'publicada' => true, 'publicada_em' => now()->addDay()]);
        Noticia::create(['titulo' => 'Sem data pública', 'resumo' => 'Resumo', 'texto' => 'Texto', 'publicada' => true]);
        Noticia::create(['titulo' => 'Publicação anterior', 'resumo' => 'Resumo', 'texto' => 'Texto', 'publicada' => true, 'publicada_em' => now()->subDay()]);
        Noticia::create(['titulo' => 'Publicação recente', 'resumo' => 'Resumo', 'texto' => 'Texto', 'publicada' => true, 'publicada_em' => now()]);

        $this->get(route('site.index'))->assertOk()
            ->assertDontSee('Rascunho secreto')->assertDontSee('Notícia futura')->assertDontSee('Sem data pública')
            ->assertSeeInOrder(['Publicação recente', 'Publicação anterior']);
    }

    public function test_site_permanece_disponivel_antes_da_migracao_de_noticias(): void
    {
        Schema::dropIfExists('noticias');

        try {
            $this->get(route('site.index'))->assertOk()->assertSee('NOTÍCIAS &amp;', false);
        } finally {
            (require database_path('migrations/2026_10_26_000005_create_noticias_table.php'))->up();
        }
    }
}
