<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAlunoPublicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_publico_aponta_para_login_real_sem_renderizar_portais_simulados(): void
    {
        $this->get(route('site.index'))
            ->assertOk()
            ->assertSee('alunoLogin')
            ->assertSee('\/portal\/login', false)
            ->assertDontSee('data-page="login-aluno"', false)
            ->assertDontSee('data-page="dash-aluno"', false)
            ->assertDontSee('data-page="login-secretaria"', false)
            ->assertDontSee('data-page="dash-secretaria"', false)
            ->assertDontSee('PAINÉIS INTERNOS');
    }

    public function test_javascript_publico_nao_intercepta_mais_formularios_de_login(): void
    {
        $javascript = file_get_contents(public_path('js/site/app.js'));

        $this->assertIsString($javascript);
        $this->assertStringNotContainsString('loginAluForm', $javascript);
        $this->assertStringNotContainsString('loginSecForm', $javascript);
        $this->assertStringNotContainsString("go('dash-aluno')", $javascript);
        $this->assertStringNotContainsString("go('dash-secretaria')", $javascript);
        $this->assertStringContainsString('ALUNO_LOGIN_URL', $javascript);
    }
}
