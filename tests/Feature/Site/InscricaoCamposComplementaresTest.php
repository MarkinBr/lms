<?php

namespace Tests\Feature\Site;

use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class InscricaoCamposComplementaresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public static function endpoints(): array
    {
        return [
            'individual' => ['/api/v1/applications', 1],
            'lote de dois cursos' => ['/api/v1/applications/batch', 2],
        ];
    }

    private function payload(int $quantidade): array
    {
        $inscricoes = [];
        for ($indice = 0; $indice < $quantidade; $indice++) {
            $curso = Curso::query()->create(['titulo' => 'Curso de teste '.$indice, 'ativo' => true]);
            $modalidade = CourseModality::query()->create([
                'curso_id' => $curso->id,
                'nome' => 'EAD',
                'ativo' => true,
            ]);
            $inscricoes[] = ['curso_id' => $curso->id, 'course_modality_id' => $modalidade->id];
        }

        return [
            'nome_completo' => 'Candidata de Teste',
            'email' => 'inscricao@example.test',
            'telefone' => '21999999999',
            'cpf' => '52998224725',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'escolaridade' => 'medio_completo',
            'cep' => '01001-000',
            'logradouro' => 'Rua de teste',
            'numero' => '1',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            ...($quantidade === 1 ? $inscricoes[0] : ['inscricoes' => $inscricoes]),
        ];
    }

    private function complementares(): array
    {
        return [
            'ano_conclusao' => 2008,
            'formacao_teologica' => true,
            'instituicao_anterior' => 'Instituto Teológico de Teste',
            'como_conheceu' => 'indicacao',
            'indicacao_nome' => 'Pessoa de Teste',
        ];
    }

    #[DataProvider('endpoints')]
    public function test_persiste_complementares_por_curso_e_aceite_com_versao_do_servidor(string $endpoint, int $quantidade): void
    {
        $this->travelTo(now()->startOfSecond());
        $payload = [...$this->payload($quantidade), ...$this->complementares(),
            'aceite_termos' => true,
            'aceite_termos_em' => '2000-01-01 00:00:00',
            'termos_versao' => 'versao-injetada',
        ];

        $this->postJson($endpoint, $payload)->assertCreated();

        $inscricoes = Application::all();
        $this->assertCount($quantidade, $inscricoes);
        $this->assertCount($quantidade, $inscricoes->pluck('protocol')->unique());
        $this->assertCount($quantidade, $inscricoes->pluck('curso_id')->unique());
        foreach ($inscricoes as $inscricao) {
            foreach ($this->complementares() as $campo => $valor) {
                $this->assertSame($valor, $inscricao->{$campo}, $campo);
            }
            $this->assertTrue($inscricao->aceite_termos_em?->equalTo(now()) ?? false);
            $this->assertNotEmpty($inscricao->termos_versao);
            $this->assertNotSame('versao-injetada', $inscricao->termos_versao);
        }
    }

    #[DataProvider('endpoints')]
    public function test_preserva_resposta_negativa_em_vez_de_converter_para_nulo(string $endpoint, int $quantidade): void
    {
        $this->postJson($endpoint, [...$this->payload($quantidade),
            'formacao_teologica' => false,
            'ano_conclusao' => (int) now()->year,
        ])->assertCreated();

        $this->assertDatabaseCount('applications', $quantidade);
        foreach (Application::all() as $inscricao) {
            $this->assertSame(false, $inscricao->formacao_teologica);
            $this->assertSame((int) now()->year, $inscricao->ano_conclusao);
        }
    }

    #[DataProvider('endpoints')]
    public function test_payload_legado_continua_valido_sem_inventar_dados_ou_consentimento(string $endpoint, int $quantidade): void
    {
        $this->postJson($endpoint, [...$this->payload($quantidade),
            'aceite_termos_em' => '2020-01-01 00:00:00',
            'termos_versao' => 'nao-aceito',
        ])->assertCreated();

        $this->assertDatabaseCount('applications', $quantidade);
        foreach (Application::all() as $inscricao) {
            $this->assertComplementaresNulos($inscricao);
        }
    }

    #[DataProvider('endpoints')]
    public function test_complementares_explicitamente_nulos_sao_opcionais(string $endpoint, int $quantidade): void
    {
        $this->postJson($endpoint, [...$this->payload($quantidade),
            ...array_fill_keys(array_keys($this->complementares()), null),
        ])->assertCreated();

        foreach (Application::all() as $inscricao) {
            $this->assertComplementaresNulos($inscricao);
        }
    }

    private function assertComplementaresNulos(Application $inscricao): void
    {
        foreach ([...array_keys($this->complementares()), 'aceite_termos_em', 'termos_versao'] as $campo) {
            $this->assertArrayHasKey($campo, $inscricao->getAttributes());
            $this->assertNull($inscricao->{$campo}, $campo);
        }
    }

    public static function valoresInvalidos(): iterable
    {
        $casos = [
            ['ano_conclusao', 1899],
            ['ano_conclusao', 9999],
            ['ano_conclusao', 2008.5],
            ['ano_conclusao', 'ano'],
            ['formacao_teologica', 'nao'],
            ['formacao_teologica', 2],
            ['instituicao_anterior', str_repeat('a', 256)],
            ['instituicao_anterior', ['Instituto']],
            ['como_conheceu', 'outro'],
            ['como_conheceu', ['site']],
            ['indicacao_nome', str_repeat('a', 256)],
            ['indicacao_nome', 123],
            ['aceite_termos', false],
            ['aceite_termos', null],
            ['aceite_termos', 'sim'],
        ];
        foreach (self::endpoints() as $nome => [$endpoint, $quantidade]) {
            foreach ($casos as $indice => [$campo, $valor]) {
                yield $nome.' '.$campo.' '.$indice => [$endpoint, $quantidade, $campo, $valor];
            }
        }
    }

    #[DataProvider('valoresInvalidos')]
    public function test_rejeita_dados_invalidos_antes_de_criar_qualquer_inscricao(string $endpoint, int $quantidade, string $campo, mixed $valor): void
    {
        $resposta = $this->postJson($endpoint, [...$this->payload($quantidade), $campo => $valor]);

        $resposta->assertUnprocessable();
        $this->assertNotEmpty($resposta->json('error.details.'.$campo) ?? $resposta->json('errors.'.$campo));
        $this->assertDatabaseCount('applications', 0);
    }

    public static function origens(): array
    {
        return [['indicacao'], ['redes_sociais'], ['igreja'], ['site']];
    }

    #[DataProvider('origens')]
    public function test_aceita_as_origens_oferecidas_no_formulario(string $origem): void
    {
        $this->postJson('/api/v1/applications', [...$this->payload(1), 'como_conheceu' => $origem])->assertCreated();

        $this->assertSame($origem, Application::sole()->como_conheceu);
    }

    public function test_migracao_aditiva_preserva_inscricao_antiga_com_novos_campos_nulos(): void
    {
        $this->postJson('/api/v1/applications', $this->payload(1))->assertCreated();
        $inscricao = Application::sole();
        $migracao = require database_path('migrations/2026_10_30_000012_add_campos_complementares_inscricao.php');

        $migracao->down();
        $migracao->up();

        $this->assertSame($inscricao->protocol, $inscricao->fresh()->protocol);
        $this->assertComplementaresNulos($inscricao->fresh());
    }

    public function test_formulario_renderizado_nomeia_campos_e_oferece_valores_compativeis_com_api(): void
    {
        $html = view('site.pages.inscricao')->render();
        $documento = new \DOMDocument;
        @$documento->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($documento);

        foreach ([...array_keys($this->complementares()), 'aceite_termos'] as $campo) {
            $this->assertSame(1, $xpath->query('//*[@id="inscForm"]//*[@name="'.$campo.'"]')->length, $campo);
        }
        foreach (['1' => 'Sim', '0' => 'Não'] as $valor => $rotulo) {
            $opcao = $xpath->query('//select[@name="formacao_teologica"]/option[@value="'.$valor.'"]')->item(0);
            $this->assertSame($rotulo, $opcao?->textContent);
        }
        foreach (['indicacao' => 'Indicação', 'redes_sociais' => 'Redes sociais', 'igreja' => 'Igreja', 'site' => 'Site'] as $valor => $rotulo) {
            $opcao = $xpath->query('//select[@name="como_conheceu"]/option[@value="'.$valor.'"]')->item(0);
            $this->assertSame($rotulo, $opcao?->textContent);
        }
        $this->assertSame(1, $xpath->query('//input[@name="aceite_termos" and @type="checkbox" and @required]')->length);
    }

    public function test_javascript_envia_complementares_e_preserva_rascunho_para_retentativa(): void
    {
        // Executa o handler real com DOM/transporte locais, sem servidor nem envio externo.
        $processo = new Process(['node'], base_path());
        $processo->setInput(<<<'JS'
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/js/site/app.js', 'utf8');
const start = source.indexOf('/* inscrição: envio em lote');
const end = source.indexOf('/* acompanhamento:', start);
const fields = Object.fromEntries(Object.entries({
  ano_conclusao:'2008', formacao_teologica:'0', instituicao_anterior:'Instituto de Teste',
  como_conheceu:'indicacao', indicacao_nome:'Pessoa de Teste'
}).map(([key,value])=>[key,{value}]));
fields.aceite_termos={checked:true,value:'1'};
const button = {disabled:false,innerHTML:'Enviar'};
let submit;
const form = {
  reportValidity:()=>true,
  addEventListener:(event,handler)=>{if(event==='submit')submit=handler;},
  querySelector:selector=>selector==='button[type="submit"]'
    ?button:fields[selector.match(/name="([^"]+)"/)?.[1]]||null,
  querySelectorAll:()=>[1,2].map(id=>({querySelector:()=>({value:String(id)})}))
};
const sent=[];
const sandbox = {
  document:{getElementById:id=>id==='inscForm'?form:null}, toast:()=>{},
  fetch:async(url,opts)=>{
    assert.equal(url,'/api/v1/applications/batch');
    sent.push(JSON.parse(opts.body));
    if(sent.length===1)throw Error('offline');
    return {status:422,json:async()=>({error:{details:{ano_conclusao:['Verifique o ano']}}})};
  }
};
vm.runInNewContext(source.slice(start,end),sandbox);
(async()=>{
  await submit({preventDefault(){}});
  assert.equal(sent[0].formacao_teologica,false);
  assert.equal(sent[0].ano_conclusao,2008);
  assert.equal(sent[0].instituicao_anterior,'Instituto de Teste');
  assert.equal(sent[0].como_conheceu,'indicacao');
  assert.equal(sent[0].indicacao_nome,'Pessoa de Teste');
  assert.equal(sent[0].aceite_termos,true);
  assert.equal(sent[0].inscricoes.length,2);
  assert.equal(button.disabled,false);
  assert.equal(button.innerHTML,'Enviar');
  assert.equal(fields.formacao_teologica.value,'0');
  await submit({preventDefault(){}});
  assert.deepEqual(sent[1],sent[0]);
  assert.equal(button.disabled,false);
  fields.formacao_teologica.value='1';
  await submit({preventDefault(){}});
  assert.equal(sent[2].formacao_teologica,true);
  fields.formacao_teologica.value='';
  fields.ano_conclusao.value='';
  fields.aceite_termos.checked=false;
  await submit({preventDefault(){}});
  assert.equal(Object.hasOwn(sent[3],'formacao_teologica'),false);
  assert.equal(Object.hasOwn(sent[3],'ano_conclusao'),false);
  assert.equal(Object.hasOwn(sent[3],'aceite_termos'),false);
})().catch(error=>{console.error(error);process.exitCode=1;});
JS);
        $processo->run();

        $this->assertTrue($processo->isSuccessful(), $processo->getOutput().$processo->getErrorOutput());
    }
}
