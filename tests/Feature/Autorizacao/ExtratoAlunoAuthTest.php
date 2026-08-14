<?php

namespace Tests\Feature\Autorizacao;

use App\Models\Curso;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #49 — Extrato do aluno (PDF): só o próprio aluno ou gestão
 * (admin/secretário ativo) podem gerar. Terceiros recebem 404 sem
 * confirmação de existência e sem vazamento de dados pessoais.
 */
class ExtratoAlunoAuthTest extends TestCase
{
    use RefreshDatabase;

    private static int $cpfSequence = 0;

    private function usuario(string $nivel, bool $ativo = true): User
    {
        return User::query()->create([
            'name' => 'Usuário '.ucfirst($nivel),
            'email' => $nivel.uniqid().'@cetsadp.test',
            'password' => 'segredo',
            'nivel' => $nivel,
            'ativo' => $ativo,
            'cpf' => '000000'.str_pad((string) (++self::$cpfSequence), 5, '0', STR_PAD_LEFT),
            'telefone' => '1199999'.random_int(1000, 9999),
            'endereco' => 'Rua Teste, 123',
        ]);
    }

    private function curso(): Curso
    {
        return Curso::query()->create(['titulo' => 'Teologia', 'ativo' => true]);
    }

    private function matricula(User $aluno, Curso $curso): Matricula
    {
        return Matricula::query()->create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'turma_id' => null,
            'data_matricula' => now()->toDateString(),
            'status' => 'ativo',
            'numero_matricula' => 'MAT-'.uniqid(),
            'dia_vencimento' => 10,
        ]);
    }

    // 1. Próprio aluno gera o próprio extrato (permitido).
    public function test_aluno_gera_o_proprio_extrato(): void
    {
        $aluno = $this->usuario('aluno');
        $this->matricula($aluno, $this->curso());

        $this->actingAs($aluno)
            ->get(route('relatorios.aluno.extrato', ['userId' => $aluno->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    // 2. Aluno recebe 404 ao pedir extrato de outro aluno (negado, sem confirmar existência).
    public function test_aluno_recebe_404_ao_pedir_extrato_de_terceiro(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $this->matricula($outroAluno, $this->curso());

        $this->actingAs($aluno)
            ->get(route('relatorios.aluno.extrato', ['userId' => $outroAluno->id]))
            ->assertNotFound();
    }

    // 3. Secretário ativo gera extrato de qualquer aluno (permitido).
    public function test_secretario_gera_extrato_de_aluno(): void
    {
        $secretario = $this->usuario('secretario');
        $aluno = $this->usuario('aluno');
        $this->matricula($aluno, $this->curso());

        $this->actingAs($secretario)
            ->get(route('relatorios.aluno.extrato', ['userId' => $aluno->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    // 4. Admin ativo gera extrato de qualquer aluno (permitido).
    public function test_admin_gera_extrato_de_aluno(): void
    {
        $admin = $this->usuario('admin');
        $aluno = $this->usuario('aluno');
        $this->matricula($aluno, $this->curso());

        $this->actingAs($admin)
            ->get(route('relatorios.aluno.extrato', ['userId' => $aluno->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    // 5. Professor não gera extrato (negado).
    public function test_professor_nao_gera_extrato(): void
    {
        $professor = $this->usuario('professor');
        $aluno = $this->usuario('aluno');
        $this->matricula($aluno, $this->curso());

        $this->actingAs($professor)
            ->get(route('relatorios.aluno.extrato', ['userId' => $aluno->id]))
            ->assertNotFound();
    }

    // 6. Secretário inativo é negado (Issue #49 — usuário inativo).
    public function test_secretario_inativo_e_negado(): void
    {
        $secretario = $this->usuario('secretario', ativo: false);
        $aluno = $this->usuario('aluno');
        $this->matricula($aluno, $this->curso());

        $this->actingAs($secretario)
            ->get(route('relatorios.aluno.extrato', ['userId' => $aluno->id]))
            ->assertForbidden();
    }

    // 7. Resposta de erro não vaza dados pessoais (CPF, e-mail, telefone, endereço).
    public function test_resposta_de_erro_nao_vaza_dados_pessoais(): void
    {
        $aluno = $this->usuario('aluno');
        $outroAluno = $this->usuario('aluno');
        $this->matricula($outroAluno, $this->curso());

        $response = $this->actingAs($aluno)
            ->get(route('relatorios.aluno.extrato', ['userId' => $outroAluno->id]));

        $response->assertNotFound();

        $corpo = $response->getContent();
        $this->assertStringNotContainsString($outroAluno->cpf, $corpo);
        $this->assertStringNotContainsString($outroAluno->email, $corpo);
        $this->assertStringNotContainsString($outroAluno->telefone, $corpo);
        $this->assertStringNotContainsString($outroAluno->endereco, $corpo);
    }

    // 8. Extrato de usuário que não é aluno retorna 404 (gestão não gera "extrato" de secretário).
    public function test_gestao_nao_gera_extrato_de_nao_aluno(): void
    {
        $admin = $this->usuario('admin');
        $outroSecretario = $this->usuario('secretario');

        $this->actingAs($admin)
            ->get(route('relatorios.aluno.extrato', ['userId' => $outroSecretario->id]))
            ->assertNotFound();
    }
}
