<?php

namespace Tests\Feature\Site;

use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class AcompanhamentoInscricaoLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_assinado_exibe_apenas_dados_publicos_e_impede_cache(): void
    {
        $inscricao = $this->criarInscricao();
        $url = URL::temporarySignedRoute(
            'site.acompanhamento-link',
            now()->addDays(30),
            ['application' => $inscricao->getKey()],
        );

        $this->get($url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertSee('Candidato de Teste')
            ->assertSee('Curso de Teologia')
            ->assertSee('Híbrida')
            ->assertSee('INSC-TESTE-20260814')
            ->assertSee('Em análise pela secretaria')
            ->assertDontSee('52998224725')
            ->assertDontSee('candidato@example.test')
            ->assertDontSee('21999999999')
            ->assertDontSee('Rua Privada')
            ->assertDontSee('Observação interna');
    }

    public function test_link_sem_assinatura_adulterado_ou_expirado_e_recusado(): void
    {
        $inscricao = $this->criarInscricao();
        $url = URL::temporarySignedRoute(
            'site.acompanhamento-link',
            now()->addDays(30),
            ['application' => $inscricao->getKey()],
        );
        $expirado = URL::temporarySignedRoute(
            'site.acompanhamento-link',
            now()->subMinute(),
            ['application' => $inscricao->getKey()],
        );

        $this->get('/acompanhar-inscricao/'.$inscricao->getKey())->assertForbidden();
        $this->get($url.'&alterado=1')->assertForbidden();
        $this->get(str_replace($inscricao->getKey(), (string) Str::uuid(), $url))->assertForbidden();
        $this->get($expirado)->assertForbidden();
    }

    private function criarInscricao(): Application
    {
        $curso = Curso::query()->create([
            'titulo' => 'Curso de Teologia',
            'ativo' => true,
        ]);

        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'Híbrida',
            'ativo' => true,
        ]);

        return Application::query()->create([
            'protocol' => 'INSC-TESTE-20260814',
            'nome_completo' => 'Candidato de Teste',
            'cpf' => '52998224725',
            'rg' => '99.888.777-6',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'nacionalidade' => 'Brasileira',
            'email' => 'candidato@example.test',
            'telefone' => '21999999999',
            'cep' => '20000-000',
            'logradouro' => 'Rua Privada',
            'numero' => '10',
            'bairro' => 'Centro',
            'cidade' => 'Rio de Janeiro',
            'estado' => 'RJ',
            'escolaridade' => 'medio_completo',
            'observacoes_secretaria' => 'Observação interna',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);
    }
}
