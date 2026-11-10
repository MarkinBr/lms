<?php

namespace Tests\Feature\Secretaria;

use App\Models\User;
use App\Services\Acessos\AtendimentoAluno;
use App\Services\Acessos\CadastroAluno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GestaoCadastroAcessosTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretaria_cria_acesso_sem_email_sem_gerar_vinculos_academicos(): void
    {
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $aluno = app(CadastroAluno::class)->criar($ator, ['name' => 'Aluno Teste', 'whatsapp' => '21999999999'], 'Atendimento presencial');
        $this->assertSame('aluno', $aluno->nivel);
        $this->assertNull($aluno->email);
        $this->assertSame('5521999999999', $aluno->whatsapp);
        $this->assertNotEmpty($aluno->matricula_login);
        $this->assertTrue((bool) $aluno->primeiro_acesso_pendente);
        $this->assertDatabaseCount('matriculas', 0);
        $this->assertDatabaseCount('mensalidades', 0);
        $this->assertDatabaseHas('acesso_auditorias', ['aluno_id' => $aluno->id, 'acao' => 'cadastro_criado']);
    }

    public function test_edita_todos_os_grupos_sem_alterar_email_confirmado_ate_confirmacao(): void
    {
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true, 'email' => 'antigo@example.test', 'email_verified_at' => now()]);
        $estado = AtendimentoAluno::estado($aluno);
        $atual = app(CadastroAluno::class)->atualizar($ator, $aluno, [
            'name' => 'Nome Corrigido', 'rg' => 'RG EXEMPLO', 'email' => 'novo@example.test',
            'whatsapp' => '21988888888', 'endereco' => 'Rua de Exemplo', 'escolaridade' => 'medio_completo',
            'ano_conclusao' => 2020, 'formacao_teologica' => false, 'instituicao_anterior' => 'Instituição de Exemplo',
            'como_conheceu' => 'indicacao', 'indicacao_nome' => 'Contato de Exemplo',
        ], $estado, 'Correção de cadastro');
        $this->assertSame('Nome Corrigido', $atual->name);
        $this->assertSame('Rua de Exemplo', $atual->endereco);
        $this->assertSame('antigo@example.test', $atual->email);
        $this->assertSame('novo@example.test', $atual->email_confirmacao_pendente);
        $this->assertSame(2020, (int) $atual->ano_conclusao);
        $this->assertSame('Instituição de Exemplo', $atual->instituicao_anterior);
    }

    public function test_snapshot_antigo_impede_sobrescrever_outro_atendimento(): void
    {
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $estado = AtendimentoAluno::estado($aluno);
        $aluno->update(['name' => 'Outra alteração']);
        $this->expectException(ValidationException::class);
        app(CadastroAluno::class)->atualizar($ator, $aluno, ['name' => 'Proposta antiga'], $estado, 'Teste');
    }

    public function test_nao_atende_perfis_privilegiados_nem_ator_desativado(): void
    {
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $admin = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        try {
            AtendimentoAluno::autorizar($ator, $admin);
            $this->fail('Perfil privilegiado não pode ser atendido.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $ator->update(['ativo' => false]);
        $this->expectException(HttpException::class);
        AtendimentoAluno::autorizar($ator);
    }

    public function test_editor_rejeita_mass_assignment_e_nao_registra_dados_sensiveis(): void
    {
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        try {
            app(CadastroAluno::class)->atualizar($ator, $aluno, ['nivel' => 'admin'], AtendimentoAluno::estado($aluno), 'Tentativa');
            $this->fail('Campo privilegiado não pode ser aceito.');
        } catch (ValidationException) {
            $this->assertSame('aluno', $aluno->fresh()->nivel);
        }
        app(CadastroAluno::class)->atualizar($ator, $aluno, ['telefone' => '21977777777'], AtendimentoAluno::estado($aluno), 'Atualização do contato');
        $this->assertStringNotContainsString('21977777777', DB::table('acesso_auditorias')->value('detalhes'));
    }

    public static function dadosInvalidos(): iterable
    {
        foreach ([false, true] as $editar) {
            foreach ([
                ['estado_civil', 'inventado'], ['escolaridade', 'inventada'], ['como_conheceu', 'outro'],
                ['genero', 'inventado'], ['estado', 'ZZ'], ['naturalidade_estado', 'XY'],
                ['email', ['teste@example.test']], ['cpf', ['52998224725']],
                ['whatsapp', ['21999999999']], ['telefone', ['21999999999']], ['cep', ['01001000']],
            ] as [$campo, $valor]) {
                yield ($editar ? 'edicao ' : 'criacao ').$campo => [$editar, $campo, $valor];
            }
        }
    }

    #[DataProvider('dadosInvalidos')]
    public function test_rejeita_enum_ou_tipo_invalido_sem_gravar_cadastro(bool $editar, string $campo, mixed $valor): void
    {
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $aluno = $editar ? User::factory()->create(['nivel' => 'aluno', 'ativo' => true]) : null;
        $quantidade = User::count();
        $antes = $aluno?->fresh()->getAttributes();

        try {
            if ($editar) {
                app(CadastroAluno::class)->atualizar($ator, $aluno, [$campo => $valor], AtendimentoAluno::estado($aluno), 'Conferência');
            } else {
                app(CadastroAluno::class)->criar($ator, ['name' => 'Aluno de teste', $campo => $valor], 'Conferência');
            }
            $this->fail('O dado inválido deveria ser rejeitado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($campo, $exception->errors());
        }
        $this->assertSame($quantidade, User::count());
        $this->assertDatabaseCount('acesso_auditorias', 0);
        if ($aluno) {
            $this->assertSame($antes, $aluno->fresh()->getAttributes());
        }
    }

    public function test_normaliza_enums_validos_e_preserva_false(): void
    {
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $aluno = app(CadastroAluno::class)->criar($ator, [
            'name' => ' Aluno de teste ', 'genero' => 'feminino', 'estado' => 'rj', 'naturalidade_estado' => 'sp',
            'estado_civil' => 'União estável', 'escolaridade' => 'mestrado', 'como_conheceu' => 'igreja',
            'formacao_teologica' => false,
        ], 'Cadastro conferido');

        $this->assertSame('F', $aluno->genero);
        $this->assertSame('RJ', $aluno->estado);
        $this->assertSame('SP', $aluno->naturalidade_estado);
        $this->assertSame('mestrado', $aluno->escolaridade);
        $this->assertSame('União estável', $aluno->estado_civil);
        $this->assertFalse($aluno->formacao_teologica);
        $atualizado = app(CadastroAluno::class)->atualizar($ator, $aluno, [
            'genero' => 'outro', 'estado_civil' => null, 'escolaridade' => null, 'como_conheceu' => null,
        ], AtendimentoAluno::estado($aluno), 'Correção conferida');
        $this->assertSame('Outro', $atualizado->genero);
        $this->assertNull($atualizado->estado_civil);
        $this->assertNull($atualizado->escolaridade);
        $this->assertNull($atualizado->como_conheceu);
    }

    public static function duplicidades(): iterable
    {
        foreach ([false, true] as $editar) {
            yield ($editar ? 'edicao ' : 'criacao ').'email' => [$editar, 'email', 'email', '  DONO@example.test ', 'dono@example.test'];
            yield ($editar ? 'edicao ' : 'criacao ').'reserva de email' => [$editar, 'email_confirmacao_pendente', 'email', '  RESERVA@example.test ', 'reserva@example.test'];
            yield ($editar ? 'edicao ' : 'criacao ').'cpf' => [$editar, 'cpf', 'cpf', '529.982.247-25', '52998224725'];
        }
    }

    #[DataProvider('duplicidades')]
    public function test_duplicidade_normalizada_retorna_erro_no_campo_sem_efeitos(bool $editar, string $campoExistente, string $campo, string $existente, string $valor): void
    {
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $dono = User::factory()->create(['nivel' => 'aluno']);
        $dono->forceFill([$campoExistente => $existente])->save();
        $aluno = $editar ? User::factory()->create(['nivel' => 'aluno']) : null;
        $quantidade = User::count();
        $antes = $aluno?->fresh()->getAttributes();
        try {
            if ($editar) {
                app(CadastroAluno::class)->atualizar($ator, $aluno, [$campo => $valor], AtendimentoAluno::estado($aluno), 'Conferência');
            } else {
                app(CadastroAluno::class)->criar($ator, ['name' => 'Aluno de teste', $campo => $valor], 'Conferência');
            }
            $this->fail('Duplicidade deveria produzir erro amigável.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($campo, $exception->errors());
        }
        $this->assertSame($quantidade, User::count());
        $this->assertDatabaseCount('acesso_auditorias', 0);
        if ($aluno) {
            $this->assertSame($antes, $aluno->fresh()->getAttributes());
        }
    }

    public static function corridas(): array
    {
        return [[false, 'cpf'], [false, 'email'], [true, 'cpf']];
    }

    #[DataProvider('corridas')]
    public function test_colisao_no_banco_apos_consulta_retorna_validacao_e_rollback(bool $editar, string $campo): void
    {
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $aluno = $editar ? User::factory()->create(['nivel' => 'aluno']) : null;
        $quantidade = User::count();
        $antes = $aluno?->fresh()->getAttributes();
        $operacao = $editar ? 'UPDATE' : 'INSERT';
        // Um concorrente aparece na fronteira da gravação, depois da consulta de disponibilidade.
        DB::unprepared("CREATE TEMP TRIGGER corrida_cadastro BEFORE {$operacao} ON users
            WHEN NEW.name = 'Aluno Corrida'
            BEGIN
                INSERT INTO users (name, email, cpf, password, nivel, ativo)
                VALUES ('Concorrente', ".($campo === 'email' ? 'NEW.email' : "'concorrente@example.test'").', '.($campo === 'cpf' ? 'NEW.cpf' : 'NULL').", 'hash', 'aluno', 1);
            END");
        try {
            $dados = ['name' => 'Aluno Corrida', $campo => $campo === 'email' ? 'corrida@example.test' : '52998224725'];
            if ($editar) {
                app(CadastroAluno::class)->atualizar($ator, $aluno, $dados, AtendimentoAluno::estado($aluno), 'Conferência');
            } else {
                app(CadastroAluno::class)->criar($ator, $dados, 'Conferência');
            }
            $this->fail('A colisão deveria produzir validação amigável.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($campo, $exception->errors());
            $this->assertStringNotContainsString('SQL', implode(' ', $exception->errors()[$campo]));
        } finally {
            DB::unprepared('DROP TRIGGER corrida_cadastro');
        }
        $this->assertSame($quantidade, User::count());
        $this->assertDatabaseCount('acesso_auditorias', 0);
        if ($aluno) {
            $this->assertSame($antes, $aluno->fresh()->getAttributes());
        }
    }

    public function test_snapshot_de_outra_secretaria_impede_perda_de_atualizacao_e_segunda_auditoria(): void
    {
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $outro = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $estado = AtendimentoAluno::estado($aluno);
        app(CadastroAluno::class)->atualizar($outro, $aluno, ['cidade' => 'Niterói'], $estado, 'Correção atual');
        try {
            app(CadastroAluno::class)->atualizar($ator, $aluno, ['cidade' => 'Rio de Janeiro'], $estado, 'Proposta antiga');
            $this->fail('A revisão antiga deveria ser invalidada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('estado', $exception->errors());
        }
        $this->assertSame('Niterói', $aluno->fresh()->cidade);
        $this->assertDatabaseCount('acesso_auditorias', 1);
    }

    public function test_retirar_whatsapp_tambem_revoga_atendimento_anterior(): void
    {
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $aluno->forceFill(['whatsapp' => '5521999999999', 'acesso_versao' => 4])->save();
        $atualizado = app(CadastroAluno::class)->atualizar($ator, $aluno, ['whatsapp' => null], AtendimentoAluno::estado($aluno), 'Contato removido');

        $this->assertNull($atualizado->whatsapp);
        $this->assertSame(5, $atualizado->acesso_versao);
    }

    public static function contatosSemDigitos(): iterable
    {
        foreach ([false, true] as $editar) {
            foreach (['cpf', 'cep', 'telefone', 'whatsapp'] as $campo) {
                yield ($editar ? 'edicao ' : 'criacao ').$campo => [$editar, $campo];
            }
        }
    }

    #[DataProvider('contatosSemDigitos')]
    public function test_texto_nao_vazio_sem_digitos_nao_apaga_identificador_ou_contato(bool $editar, string $campo): void
    {
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $aluno = $editar ? User::factory()->create(['nivel' => 'aluno', 'ativo' => true]) : null;
        if ($aluno) {
            $aluno->forceFill(['cpf' => '52998224725', 'cep' => '01001000', 'telefone' => '21999999999', 'whatsapp' => '5521999999999'])->save();
        }
        $antes = $aluno?->fresh()->getAttributes();
        $quantidade = User::count();
        try {
            $dados = [$campo => ' --()./+ '];
            if ($editar) {
                app(CadastroAluno::class)->atualizar($ator, $aluno, $dados, AtendimentoAluno::estado($aluno), 'Contato conferido');
            } else {
                app(CadastroAluno::class)->criar($ator, ['name' => 'Aluno de teste', ...$dados], 'Contato conferido');
            }
            $this->fail('Texto preenchido que perde todos os dígitos deve ser rejeitado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($campo, $exception->errors());
        }
        $this->assertSame($quantidade, User::count());
        $this->assertDatabaseCount('acesso_auditorias', 0);
        if ($aluno) {
            $this->assertSame($antes, $aluno->fresh()->getAttributes());
        }
    }

    public function test_null_ou_branco_legitimo_continua_opcional_apos_normalizacao(): void
    {
        $ator = User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
        $aluno = app(CadastroAluno::class)->criar($ator, [
            'name' => 'Aluno sem contatos', 'cpf' => null, 'cep' => '', 'telefone' => '   ', 'whatsapp' => null,
        ], 'Cadastro conferido');
        foreach (['cpf', 'cep', 'telefone', 'whatsapp'] as $campo) {
            $this->assertNull($aluno->{$campo});
        }
        $aluno->forceFill(['cpf' => '52998224725', 'cep' => '01001000', 'telefone' => '21999999999', 'whatsapp' => '5521999999999'])->save();
        $atual = app(CadastroAluno::class)->atualizar($ator, $aluno, [
            'cpf' => '   ', 'cep' => null, 'telefone' => '', 'whatsapp' => '   ',
        ], AtendimentoAluno::estado($aluno), 'Remoção explícita conferida');
        foreach (['cpf', 'cep', 'telefone', 'whatsapp'] as $campo) {
            $this->assertNull($atual->{$campo});
        }
    }

    public static function reservasEmail(): array
    {
        return ['atual versus pendente' => [true, false], 'pendente versus atual' => [false, true], 'duas reservas pendentes' => [false, false]];
    }

    #[DataProvider('reservasEmail')]
    public function test_email_canonico_adquire_mesma_trava_transacional_antes_de_consultar_atual_ou_pendente(bool $primeiroNovo, bool $segundoNovo): void
    {
        $ator = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $outro = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);
        $aluno = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $segundo = User::factory()->create(['nivel' => 'aluno', 'ativo' => true]);
        $antes = $segundo->fresh()->getAttributes();
        $banco = DB::getFacadeRoot();
        $nivelInicial = $banco->transactionLevel();
        $travas = [];
        $consultas = [];
        $fase = 'primeiro';
        // Apenas a primitiva externa do PostgreSQL é simulada; cadastro e consultas usam SQLite real.
        $proxy = \Mockery::mock($banco)->makePartial();
        $proxy->shouldReceive('getDriverName')->andReturn('pgsql');
        $proxy->shouldReceive('select')->andReturnUsing(function (string $sql, array $bindings) use (&$travas, &$fase, $banco) {
            $travas[$fase][] = ['sql' => $sql, 'bindings' => $bindings, 'nivel' => $banco->transactionLevel()];

            return [];
        });
        $banco->listen(function ($consulta) use (&$consultas, &$travas, &$fase) {
            if (str_contains($consulta->sql, 'LOWER(TRIM(email))')) {
                $consultas[$fase][] = count($travas[$fase] ?? []);
            }
        });
        DB::swap($proxy);
        try {
            $cadastro = app(CadastroAluno::class);
            $dados = ['name' => 'Primeiro atendimento', 'email' => '  RESERVA@example.test  '];
            if ($primeiroNovo) {
                $primeiro = $cadastro->criar($ator, $dados, 'Cadastro conferido');
                $this->assertSame('reserva@example.test', $primeiro->email);
            } else {
                $primeiro = $cadastro->atualizar($ator, $aluno, $dados, AtendimentoAluno::estado($aluno), 'Correção conferida');
                $this->assertSame('reserva@example.test', $primeiro->email_confirmacao_pendente);
            }
            $quantidade = User::count();
            $fase = 'segundo';
            try {
                $dados = ['name' => 'Segundo atendimento', 'email' => 'reserva@example.test'];
                if ($segundoNovo) {
                    $cadastro->criar($outro, $dados, 'Cadastro conferido');
                } else {
                    $cadastro->atualizar($outro, $segundo, $dados, AtendimentoAluno::estado($segundo), 'Correção conferida');
                }
                $this->fail('O segundo atendimento não pode reutilizar o endereço reservado.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('email', $exception->errors());
            }
            foreach (['primeiro', 'segundo'] as $etapa) {
                $this->assertCount(1, $travas[$etapa] ?? [], $etapa);
                $trava = $travas[$etapa][0];
                $this->assertStringContainsString('pg_advisory_xact_lock', $trava['sql']);
                $this->assertGreaterThan($nivelInicial, $trava['nivel']);
                $this->assertNotEmpty($consultas[$etapa] ?? []);
                foreach ($consultas[$etapa] as $adquiridas) {
                    $this->assertSame(1, $adquiridas, 'A trava deve preceder a consulta de disponibilidade.');
                }
            }
            $this->assertSame($travas['primeiro'][0]['bindings'], $travas['segundo'][0]['bindings']);
            $this->assertSame($quantidade, User::count());
            $this->assertSame($antes, $segundo->fresh()->getAttributes());
            $this->assertDatabaseCount('acesso_auditorias', 1);
        } finally {
            DB::swap($banco);
        }
    }
}
