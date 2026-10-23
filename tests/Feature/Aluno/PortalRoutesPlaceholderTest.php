<?php

namespace Tests\Feature\Aluno;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PortalRoutesPlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_aluno_ativo_abre_as_tres_paginas_sem_erro_500(): void
    {
        $this->actingAs($this->usuario('aluno'))
            ->get(route('aluno.forum'))
            ->assertOk()
            ->assertSee('Fórum de estudos')
            ->assertSee('Recurso em preparação')
            ->assertSee(route('aluno.comunicacao'), false);

        $this->get(route('aluno.configuracoes'))
            ->assertOk()
            ->assertSee('Configurações do portal')
            ->assertSee(route('aluno.perfil'), false);

        $this->get(route('aluno.comunicacao.mensagens'))
            ->assertOk()
            ->assertSee('Mensagens diretas')
            ->assertSee('caixa de entrada ativa');
    }

    public function test_mensagens_nao_e_capturada_pela_rota_de_aviso_por_id(): void
    {
        $rota = app('router')->getRoutes()->match(
            Request::create('/portal/comunicacao/mensagens', 'GET')
        );

        $this->assertSame('aluno.comunicacao.mensagens', $rota->getName());
    }

    public function test_visitante_e_outros_perfis_nao_veem_as_paginas_protegidas(): void
    {
        $this->get(route('aluno.forum'))->assertRedirect();

        $this->actingAs($this->usuario('admin'))
            ->get(route('aluno.forum'))
            ->assertForbidden();

        $this->actingAs($this->usuario('aluno', false))
            ->get(route('aluno.configuracoes'))
            ->assertForbidden();
    }

    private function usuario(string $nivel, bool $ativo = true): User
    {
        return User::factory()->create([
            'nivel' => $nivel,
            'ativo' => $ativo,
        ]);
    }
}
