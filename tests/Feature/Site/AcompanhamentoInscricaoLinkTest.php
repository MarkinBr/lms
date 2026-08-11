<?php

namespace Tests\Feature\Site;

use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AcompanhamentoInscricaoLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_assinado_exibe_apenas_dados_seguros_da_inscricao(): void
    {
        $inscricao = $this->criarInscricao();

        $url = URL::temporarySignedRoute(
            'site.acompanhamento-link',
            now()->addDays(30),
            ['application' => $inscricao],
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Curso de Teologia')
            ->assertSee('Presencial')
            ->assertSee('INSC-TESTE-20260811')
            ->assertSee('Em análise pela secretaria')
            ->assertDontSee('12345678900')
            ->assertDontSee('candidato@example.test')
            ->assertDontSee('Rua Privada');
    }

    public function test_link_invalido_ou_expirado_e_recusado(): void
    {
        $inscricao = $this->criarInscricao();

        $urlExpirada = URL::temporarySignedRoute(
            'site.acompanhamento-link',
            now()->subMinute(),
            ['application' => $inscricao],
        );

        $this->get($urlExpirada)->assertForbidden();
        $this->get('/acompanhar-inscricao/'.$inscricao->id)->assertForbidden();
    }

    private function criarInscricao(): Application
    {
        $curso = Curso::query()->create([
            'titulo' => 'Curso de Teologia',
            'ativo' => true,
        ]);

        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'Presencial',
            'ativo' => true,
        ]);

        return Application::query()->create([
            'protocol' => 'INSC-TESTE-20260811',
            'nome_completo' => 'Candidato de Teste',
            'cpf' => '12345678900',
            'email' => 'candidato@example.test',
            'telefone' => '21999999999',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'nacionalidade' => 'Brasileira',
            'cep' => '20000-000',
            'logradouro' => 'Rua Privada',
            'numero' => '10',
            'bairro' => 'Centro',
            'cidade' => 'Rio de Janeiro',
            'estado' => 'RJ',
            'escolaridade' => 'medio_completo',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);
    }
}
