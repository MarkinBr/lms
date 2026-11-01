<?php

namespace Tests\Feature\Secretaria;

use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CentralMatriculasTest extends TestCase
{
    use RefreshDatabase;

    private function ator(string $nivel = 'secretario', bool $ativo = true): User
    {
        return User::factory()->create([
            'nivel' => $nivel,
            'ativo' => $ativo,
            'password' => Hash::make('SenhaAdministrativa!2026'),
        ]);
    }

    private function curso(string $titulo = 'Teologia'): Curso
    {
        return Curso::create(['titulo' => $titulo, 'ativo' => true]);
    }

    private function matricula(Curso $curso, ?User $aluno = null, string $numero = 'MAT-2026-001'): Matricula
    {
        $aluno ??= User::factory()->create([
            'nivel' => 'aluno', 'ativo' => true,
            'matricula_login' => 'ACESSO-'.strtoupper(\\Illuminate\\Support\\Str::random(10)),
        ]);

        return Matricula::create([
            'user_id' => $aluno->id,
            'curso_id' => $curso->id,
            'numero_matricula' => $numero,
            'data_matricula' => now()->toDateString(),
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);
    }

    private function turma(Curso $curso, array $outros = []): Turma
    {
        return Turma::create(array_merge([
            'curso_id' => $curso->id,
            'nome' => 'Turma Sabado',
            'ano_letivo' => 2026,
            'status' => Turma::STATUS_ABERTA,
            'ativa' => true,
            'valor_total_curso' => 1200,
            'qtd_parcelas' => 12,
        ], $outros));
    }

    private function confirmacao(array $dados = []): array
    {
        return array_merge([
            'motivo' => 'Correção solicitada após conferência do documento',
            'senha_confirmacao' => 'SenhaAdministrativa!2026',
        ], $dados);
    }

    public function test_card_da_central_abre_as_listas_de_matriculas_e_turmas(): void
    {
        $curso = $this->curso();
        $matricula = $this->matricula($curso);
        $turma = $this->turma($curso);
        $this->actingAs($this->ator());

        $this->get(route('secretaria.central'))
            ->assertOk()
            ->assertSee(route('secretaria.matriculas.index'))
            ->assertSee('Matrículas e turmas');

        $this->get(route('secretaria.matriculas.index', ['busca' => $matricula->numero_matricula]))
            ->assertOk()->assertSee($matricula->numero_matricula)->assertSee($matricula->user->name)
            ->assertSee(route('secretaria.matriculas.show', $matricula));

        $this->get(route('secretaria.matriculas.turmas', ['busca' => 'Sabado']))
            ->assertOk()->assertSee('Turma Sabado')
            ->assertSee(route('secretaria.matriculas.turma', $turma));

        $this->get(route('secretaria.matriculas.turma', $turma))
            ->assertOk()->assertSee($curso->titulo);

        $this->get(route('secretaria.matriculas.show', $matricula))
            ->assertOk()->assertSee('Corrigir número de matrícula')
            ->assertSee('Matrícula de acesso ao portal');
    }

    public function test_acesso_exclusivo_a_equipe_ativa_incluindo_acoes(): void
    {
        $matricula = $this->matricula($this->curso());
        $url = route('secretaria.matriculas.index');
        $operacao = route('secretaria.matriculas.corrigir-numero', $matricula);
        $this->get($url)->assertRedirect('/secretaria/login');

        foreach (['aluno', 'professor'] as $nivel) {
            $this->actingAs($this->ator($nivel))->get($url)->assertForbidden();
            $this->post($operacao, $this->confirmacao())->assertForbidden();
        }

        $this->actingAs($this->ator('secretario', false))
            ->get($url)->assertForbidden();
    }

    public function test_correge_numero_academico_com_auditoria_sem_alterar_login(): void
    {
        $matricula = $this->matricula($this->curso());
        $loginAntes = $matricula->user->matricula_login;
        $url = route('secretaria.matriculas.corrigir-numero', $matricula);
        $this->actingAs($this->ator());

        $this->post($url, $this->confirmacao([
            'numero_atual' => 'MAT-2026-001',
            'numero_matricula' => 'MAT-2026-002',
        ]))->assertRedirect(route('secretaria.matriculas.show', $matricula));

        $this->assertSame('MAT-2026-002', $matricula->fresh()->numero_matricula);
        $this->assertSame($loginAntes, $matricula->user->fresh()->matricula_login);
        $this->assertDatabaseHas('matricula_movimentacoes', [
            'matricula_id' => $matricula->id, 'tipo' => 'correcao_numero',
            'valor_anterior' => 'MAT-2026-001', 'valor_novo' => 'MAT-2026-002',
        ]);
    }

    public function test_numero_duplicado_ou_tela_desatualizada_nao_altera_ou_audita(): void
    {
        $curso = $this->curso();
        $alvo = $this->matricula($curso);
        $outro = $this->matricula($curso, null, 'MAT-2026-002');
        $this->actingAs($this->ator());

        $this->post(route('secretaria.matriculas.corrigir-numero', $alvo), $this->confirmacao([
            'numero_atual' => $alvo->numero_matricula,
            'numero_matricula' => strtolower($outro->numero_matricula),
        ]))->assertSessionHasErrors('numero_matricula');

        $this->post(route('secretaria.matriculas.corrigir-numero', $alvo), $this->confirmacao([
            'numero_atual' => 'NUMERO-ANTIGO',
            'numero_matricula' => 'NOVO-001',
        ]))->assertSessionHasErrors('numero_matricula');

        $this->assertSame('MAT-2026-001', $alvo->fresh()->numero_matricula);
        $this->assertDatabaseCount('matricula_movimentacoes', 0);
    }

    public function test_senha_administrativa_incorreta_nao_grava_nem_fica_no_old_input(): void
    {
        $matricula = $this->matricula($this->curso());
        $this->actingAs($this->ator());

        $this->post(route('secretaria.matriculas.corrigir-numero', $matricula), $this->confirmacao([
            'numero_atual' => $matricula->numero_matricula,
            'numero_matricula' => 'NOVO-001',
            'senha_confirmacao' => 'Senha-invalida',
        ]))->assertSessionHasErrors('senha_confirmacao')
            ->assertSessionMissing('_old_input.senha_confirmacao');

        $this->assertDatabaseCount('matricula_movimentacoes', 0);
        $this->assertSame('MAT-2026-001', $matricula->fresh()->numero_matricula);
    }

    public function test_vinculo_turma_valido_reaproveita_servico_e_audita(): void
    {
        $curso = $this->curso();
        $matricula = $this->matricula($curso);
        $turma = $this->turma($curso);
        $this->actingAs($this->ator());

        $this->post(route('secretaria.matriculas.vincular-turma', $matricula), $this->confirmacao([
            'turma_id' => $turma->id,
        ]))->assertRedirect(route('secretaria.matriculas.show', $matricula));

        $this->assertDatabaseHas('matricula_turma', [
            'matricula_id' => $matricula->id,
            'turma_id' => $turma->id,
            'status' => 'ativo',
        ]);
        $this->assertDatabaseHas('matricula_movimentacoes', [
            'matricula_id' => $matricula->id, 'tipo' => 'vinculo_turma', 'turma_id' => $turma->id,
        ]);

        $this->post(route('secretaria.matriculas.vincular-turma', $matricula), $this->confirmacao([
            'turma_id' => $turma->id,
        ]))->assertSessionHasErrors('turma_id');

        $this->assertDatabaseCount('matricula_turma', 1);
        $this->assertDatabaseCount('matricula_movimentacoes', 1);
    }

    public function test_recusa_turma_de_outro_curso_turma_cheia_e_matricula_trancada(): void
    {
        $curso = $this->curso();
        $matricula = $this->matricula($curso);
        $outra = $this->turma($this->curso('Diaconato'));
        $lotada = $this->turma($curso, ['capacidade' => 1]);
        $this->matricula($curso, null, 'OUTRA-001')->update(['turma_id' => $lotada->id]);
        $this->actingAs($this->ator());

        $url = route('secretaria.matriculas.vincular-turma', $matricula);
        $this->post($url, $this->confirmacao(['turma_id' => $outra->id]))
            ->assertSessionHasErrors('turma_id');
        $this->post($url, $this->confirmacao(['turma_id' => $lotada->id]))
            ->assertSessionHasErrors('turma_id');

        $matricula->update(['status' => 'trancado']);
        $this->post($url, $this->confirmacao(['turma_id' => $lotada->id]))
            ->assertSessionHasErrors('turma_id');
        $this->assertDatabaseCount('matricula_movimentacoes', 0);
    }
}
