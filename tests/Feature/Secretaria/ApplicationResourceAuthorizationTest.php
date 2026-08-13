<?php

namespace Tests\Feature\Secretaria;

use App\Filament\Resources\ApplicationResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garante que somente perfis autorizados (Secretaria/Admin)
 * podem acionar o fluxo de aprovação pela camada Filament.
 */
class ApplicationResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function novoUser(string $nivel): User
    {
        return User::query()->create([
            'name'     => 'User ' . $nivel,
            'email'    => $nivel . '@cetsadp.test',
            'password' => 'x',
            'nivel'    => $nivel,
            'ativo'    => true,
        ]);
    }

    public function test_secretaria_e_admin_podem_gerenciar(): void
    {
        $this->actingAs($this->novoUser('secretario'));
        $this->assertTrue(ApplicationResource::usuarioPodeGerenciar());
        $this->assertTrue(ApplicationResource::canAccess());

        auth()->logout();

        $this->actingAs($this->novoUser('admin'));
        $this->assertTrue(ApplicationResource::usuarioPodeGerenciar());
        $this->assertTrue(ApplicationResource::canAccess());
    }

    public function test_aluno_e_professor_nao_podem_gerenciar(): void
    {
        $this->actingAs($this->novoUser('aluno'));
        $this->assertFalse(ApplicationResource::usuarioPodeGerenciar());
        $this->assertFalse(ApplicationResource::canAccess());

        auth()->logout();

        $this->actingAs($this->novoUser('professor'));
        $this->assertFalse(ApplicationResource::usuarioPodeGerenciar());
        $this->assertFalse(ApplicationResource::canAccess());
    }

    public function test_visitante_nao_pode_gerenciar(): void
    {
        $this->assertFalse(ApplicationResource::usuarioPodeGerenciar());
        $this->assertFalse(ApplicationResource::canAccess());
    }
}
