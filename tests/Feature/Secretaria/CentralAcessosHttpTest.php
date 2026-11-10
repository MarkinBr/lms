<?php

namespace Tests\Feature\Secretaria;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CentralAcessosHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_e_exclusiva_da_secretaria_e_admin_ativos(): void
    {
        $this->get('/secretaria/central')->assertRedirect('/secretaria/login');
        $this->actingAs(User::factory()->create(['nivel' => 'aluno', 'ativo' => true]))->get('/secretaria/central')->assertForbidden();
        $this->actingAs(User::factory()->create(['nivel' => 'professor', 'ativo' => true]))->get('/secretaria/central/acessos')->assertForbidden();
        $this->actingAs(User::factory()->create(['nivel' => 'secretario', 'ativo' => true]))->get('/secretaria/central')->assertOk()->assertSee('Acessos dos alunos');
    }

    public function test_lista_somente_alunos_e_alvo_privilegiado_e_recusado(): void
    {
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $this->actingAs($ator)->get('/secretaria/central/acessos')->assertOk()->assertSee($aluno->name)->assertDontSee($admin->name);
        $this->get('/secretaria/central/acessos/'.$admin->id)->assertForbidden();
    }

    public function test_cadastro_tem_previa_e_confirmacao_nao_cria_matricula(): void
    {
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true, 'password' => Hash::make('segredo-do-ator')]);
        $dados = ['name' => 'Novo Aluno Atendimento', 'email' => 'novo@example.test', 'canal' => 'email', 'motivo' => 'Cadastro presencial', 'destino_conferido' => '1', 'senha_confirmacao' => 'segredo-do-ator'];
        $pre = $this->actingAs($ator)->post('/secretaria/central/acessos', $dados)->assertOk()->assertSee('Novo Aluno Atendimento');
        $this->assertDatabaseMissing('users', ['name' => 'Novo Aluno Atendimento']);
        $this->post('/secretaria/central/acessos', $dados + ['confirmar' => '1', 'operacao' => $pre->viewData('operacao')])->assertRedirect();
        $this->assertDatabaseHas('users', ['name' => 'Novo Aluno Atendimento', 'nivel' => 'aluno']);
        $this->assertDatabaseCount('matriculas', 0);
    }

    public function test_senha_de_confirmacao_nao_e_guardada_em_old_input(): void
    {
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $this->actingAs($ator)->post('/secretaria/central/acessos', ['name' => 'Nome Exemplo', 'senha_confirmacao' => 'incorreta'])->assertSessionHasErrors('senha_confirmacao')->assertSessionMissing('_old_input.senha_confirmacao');
    }
}
