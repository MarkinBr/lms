<?php

namespace Tests\Feature\Auth;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;

class IdentificadorAlunoTest extends PortalAuthTestCase
{
    use RefreshDatabase;

    private const SENHA = 'SenhaLegado#2026';

    #[DataProvider('identificadores')]
    public function test_aluno_entra_por_identificador_permanente_sem_depender_de_email(array $atributos, string $login): void
    {
        $aluno = $this->aluno($atributos);
        $this->post(route('aluno.login.submit'), ['email' => $login, 'password' => self::SENHA])
            ->assertRedirect(route('aluno.dashboard'));
        $this->assertAuthenticatedAs($aluno);
    }

    public static function identificadores(): array
    {
        return [
            'matricula sem email' => [['email' => null], '  LEG-001  '],
            'cpf com mascara' => [['email' => null, 'cpf' => '12345678901'], '123.456.789-01'],
            'cpf armazenado com mascara' => [['email' => null, 'cpf' => '123.456.789-01'], '12345678901'],
            'email confirmado' => [[], '  ALUNO@EXAMPLE.TEST  '],
        ];
    }

    public function test_email_nao_confirmado_nao_autentica_mas_matricula_continua_valida(): void
    {
        $aluno = $this->aluno(['email_verified_at' => null]);
        $this->negar($aluno->email);
        $this->post(route('aluno.login.submit'), ['email' => 'leg-001', 'password' => self::SENHA])
            ->assertRedirect(route('aluno.dashboard'));
        $this->assertAuthenticatedAs($aluno);
    }

    #[DataProvider('colisoes')]
    public function test_identificador_ambiguo_nao_escolhe_a_primeira_conta(array $primeiro, array $segundo, string $login): void
    {
        $this->aluno($primeiro);
        $this->aluno(array_merge(['email' => 'outro@example.test', 'matricula_login' => 'leg-002'], $segundo));
        $this->negar($login);
    }

    public static function colisoes(): array
    {
        return [
            'matricula cpf' => [['matricula_login' => '12345678901'], ['cpf' => '123.456.789-01'], '12345678901'],
            'cpf duplicado normalizado' => [['cpf' => '12345678901'], ['cpf' => '123.456.789-01'], '123.456.789-01'],
            'matricula email' => [['matricula_login' => 'outro@example.test'], [], 'outro@example.test'],
            'inativo tambem colide' => [['matricula_login' => '12345678901'], ['cpf' => '12345678901', 'ativo' => false], '12345678901'],
            'admin tambem colide' => [['matricula_login' => '12345678901'], ['cpf' => '12345678901', 'nivel' => 'admin'], '12345678901'],
            'emails diferem so em caixa' => [[], ['email' => 'ALUNO@example.test'], 'aluno@example.test'],
        ];
    }

    #[DataProvider('inelegiveis')]
    public function test_matricula_de_perfil_inelegivel_recebe_erro_generico(array $atributos): void
    {
        $this->aluno($atributos);
        $this->negar('leg-001');
    }

    public static function inelegiveis(): array
    {
        return [[['ativo' => false]], [['nivel' => 'admin']], [['nivel' => 'professor']]];
    }

    public function test_hash_bcrypt_2b_sintetico_do_legado_autentica_e_e_atualizado_sem_mudar_id(): void
    {
        $aluno = $this->aluno(['email' => null]);
        // Fixture sintética bcrypt no formato $2b$ do Node, senha SenhaLegado#2026.
        DB::table('users')->where('id', $aluno->id)->update([
            'password' => '$2b$04$abcdefghijklmnopqrstuuGxpf2Nndb2pk8/eTdDAK5sIlvVxRLr2',
        ]);
        $this->post(route('aluno.login.submit'), ['email' => 'leg-001', 'password' => self::SENHA])
            ->assertRedirect(route('aluno.dashboard'));
        $this->assertAuthenticatedAs($aluno);
        $this->assertTrue(Hash::check(self::SENHA, $aluno->fresh()->password));
    }

    #[DataProvider('hashesInvalidos')]
    public function test_hash_incompativel_ou_invalido_nao_produz_erro_500(string $hash): void
    {
        $aluno = $this->aluno();
        DB::table('users')->where('id', $aluno->id)->update(['password' => $hash]);
        $this->negar('aluno@example.test');
    }

    public static function hashesInvalidos(): array
    {
        return [['hash-legado-invalido'], [''], ['$2b$04$incompleto'], ['$argon2id$invalido']];
    }

    public function test_limite_por_cpf_nao_pode_ser_contornado_por_mascara(): void
    {
        $this->aluno(['cpf' => '12345678901']);
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('aluno.login.submit'), ['email' => '123.456.789-01', 'password' => 'errada'])
                ->assertSessionHasErrors('email');
        }
        $resposta = $this->post(route('aluno.login.submit'), ['email' => '12345678901', 'password' => self::SENHA]);
        $resposta->assertSessionHasErrors('email');
        $this->assertStringContainsString('Muitas tentativas', $resposta->getSession()->get('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_aluno_sem_email_mantem_matricula_e_pode_concluir_aula(): void
    {
        $aluno = $this->aluno(['email' => null, 'email_verified_at' => null]);
        $curso = Curso::create(['titulo' => 'Curso sintético', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo', 'ordem' => 1]);
        $disciplina = Disciplina::create(['curso_id' => $curso->id, 'modulo_id' => $modulo->id, 'codigo' => 'SINT-001', 'nome' => 'Disciplina', 'carga_horaria' => 40, 'ordem' => 1, 'ativa' => true]);
        $aula = Aula::create(['disciplina_id' => $disciplina->id, 'modulo_id' => $modulo->id, 'titulo' => 'Aula', 'ordem' => 1, 'liberada' => true]);
        $matricula = Matricula::create(['user_id' => $aluno->id, 'curso_id' => $curso->id, 'status' => 'ativo',
            'numero_matricula' => 'VINC-001', 'data_matricula' => now(), 'dia_vencimento' => 10]);
        $this->actingAs($aluno)->get(route('aluno.aula', $aula->id))->assertOk();
        $this->postJson(route('aluno.aula.concluir.nova', $aula->id))
            ->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('matriculas', ['id' => $matricula->id, 'user_id' => $aluno->id]);
    }

    private function aluno(array $atributos = []): User
    {
        return User::factory()->create(array_merge(['email' => 'aluno@example.test', 'matricula_login' => 'leg-001',
            'password' => self::SENHA, 'nivel' => 'aluno', 'ativo' => true], $atributos));
    }

    private function negar(string $identificador): void
    {
        $resposta = $this->from(route('aluno.login'))->post(route('aluno.login.submit'), [
            'email' => $identificador, 'password' => self::SENHA,
        ]);
        $resposta->assertRedirect(route('aluno.login'))->assertSessionHasErrors('email');
        $this->assertSame('Não foi possível entrar. Verifique seus dados e tente novamente.', $resposta->getSession()->get('errors')->first('email'));
        $this->assertGuest();
    }
}
