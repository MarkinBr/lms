<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FilamentAlunoEntradaTest extends PortalAuthTestCase
{
    use RefreshDatabase;

    public function test_login_filament_encaminha_para_a_politica_unica_do_portal(): void
    {
        $this->get('/aluno/login')->assertRedirect(route('aluno.login'));
        $this->assertGuest();
    }

    public function test_email_nao_confirmado_nao_obtem_formulario_filament_alternativo(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true,
            'email_verified_at' => null, 'matricula_login' => 'mat-entrada', 'password' => 'SenhaAluno#2026']);
        $this->get('/aluno/login')->assertRedirect(route('aluno.login'));
        $this->post(route('aluno.login.submit'), ['email' => $aluno->email, 'password' => 'SenhaAluno#2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_painel_filament_continua_acessivel_por_matricula_sem_email(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true,
            'email' => null, 'email_verified_at' => null, 'matricula_login' => 'mat-entrada', 'password' => 'SenhaAluno#2026']);
        $this->get('/aluno')->assertRedirect('/aluno/login');
        $this->get('/aluno/login')->assertRedirect(route('aluno.login'));
        $this->post(route('aluno.login.submit'), ['email' => 'mat-entrada', 'password' => 'SenhaAluno#2026'])
            ->assertRedirect('/aluno');
        $this->assertAuthenticatedAs($aluno);
        $this->get('/aluno')->assertOk();
    }
}
