<?php

namespace Tests\Feature\Secretaria;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralAcessosFormasTest extends TestCase
{
    use RefreshDatabase;

    public function test_botao_da_busca_abre_resumo_com_identificadores_e_sem_senha(): void
    {
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $aluno = User::factory()->create([
            'nivel' => 'aluno',
            'ativo' => true,
            'cpf' => '12345678901',
            'matricula_login' => 'MAT-2026-0088',
            'whatsapp' => '5521999999999',
        ]);

        $this->actingAs($ator)
            ->get(route('secretaria.acessos.index', ['busca' => $aluno->name]))
            ->assertOk()
            ->assertSee('Ver formas de acesso')
            ->assertSee(route('secretaria.acessos.formas', $aluno));

        $resposta = $this->get(route('secretaria.acessos.formas', $aluno))
            ->assertOk()
            ->assertSee('Formas de acesso')
            ->assertSee('12345678901')
            ->assertSee($aluno->matricula_login)
            ->assertSee($aluno->email)
            ->assertSee('5521999999999')
            ->assertSee('Protegida — não pode ser visualizada')
            ->assertSee('Recuperar acesso ou gerar código')
            ->assertSee(route('secretaria.acessos.show', $aluno).'#acesso')
            ->assertDontSee($aluno->password)
            ->assertDontSee($aluno->remember_token);

        $this->assertStringContainsString('no-store', $resposta->headers->get('Cache-Control', ''));
        $this->assertDatabaseCount('acesso_credenciais', 0);
        $this->assertDatabaseCount('acesso_entregas', 0);
    }

    public function test_consulta_recusa_visitante_aluno_professor_e_secretario_inativo(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $url = route('secretaria.acessos.formas', $aluno);

        $this->get($url)->assertRedirect('/secretaria/login');

        foreach (['aluno', 'professor'] as $nivel) {
            $ator = User::factory()->create(['nivel' => $nivel, 'ativo' => true]);
            $this->actingAs($ator)->get($url)->assertForbidden();
        }

        $inativo = User::factory()->create(['nivel' => 'secretario', 'ativo' => false]);
        $this->actingAs($inativo)->get($url)->assertForbidden();
    }

    public function test_consulta_recusa_visualizacao_de_perfis_privilegiados(): void
    {
        $secretario = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $administrador = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);

        $this->actingAs($secretario)
            ->get(route('secretaria.acessos.formas', $administrador))
            ->assertForbidden();
    }

    public function test_administrador_pode_consultar_resumo_sem_modificar_aluno(): void
    {
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $aluno = User::factory()->create([
            'nivel' => 'aluno',
            'ativo' => false,
            'senha_definitiva_pendente' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('secretaria.acessos.formas', $aluno))
            ->assertOk()
            ->assertSee('Bloqueada')
            ->assertSee('Troca de senha definitiva pendente');

        $this->assertFalse($aluno->fresh()->ativo);
        $this->assertTrue($aluno->fresh()->senha_definitiva_pendente);
    }
}
