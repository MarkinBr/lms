<?php

namespace Tests\Feature\Secretaria;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\InstrumentoAvaliacao;
use App\Models\Modulo;
use App\Models\PeriodoLetivo;
use App\Models\ProfessorAtribuicao;
use App\Models\Turma;
use App\Models\TurmaDisciplina;
use App\Models\User;
use App\Services\GestaoAcademica\PermissoesDocentes;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EstruturaAcademicaFlexivelTest extends TestCase
{
    use RefreshDatabase;

    private function curso(string $titulo = 'Seminário'): Curso
    {
        return Curso::query()->create(['titulo' => $titulo, 'ativo' => true]);
    }

    private function turma(Curso $curso, array $campos = []): Turma
    {
        return Turma::query()->create(array_merge([
            'curso_id' => $curso->id,
            'nome' => 'Turma longa',
            'ano_letivo' => 2026,
            'valor_total_curso' => 3000,
            'qtd_parcelas' => 36,
            'data_inicio' => '2026-02-01',
            'data_fim' => '2029-12-01',
            'status' => 'planejada',
        ], $campos));
    }

    private function periodo(string $nome, bool $ativo = true): PeriodoLetivo
    {
        return PeriodoLetivo::query()->create([
            'nome' => $nome, 'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31', 'ativo' => $ativo,
        ]);
    }

    private function disciplina(Curso $curso, string $codigo): Disciplina
    {
        $modulo = Modulo::query()->create([
            'curso_id' => $curso->id, 'titulo' => 'Base '.$codigo, 'ordem' => 1,
        ]);

        return Disciplina::query()->create([
            'curso_id' => $curso->id, 'modulo_id' => $modulo->id,
            'codigo' => $codigo, 'nome' => $codigo, 'carga_horaria' => 40,
            'ordem' => 1, 'ativa' => true,
        ]);
    }

    private function professor(string $nome): User
    {
        return User::query()->create([
            'name' => $nome, 'email' => uniqid().'@cetsadp.test',
            'password' => 'segredo', 'nivel' => 'professor', 'ativo' => true,
        ]);
    }

    public function test_multiplos_periodos_ativos_e_turma_de_longa_duracao(): void
    {
        $primeiro = $this->periodo('2026');
        $segundo = $this->periodo('2027');
        $terceiro = $this->periodo('2028');
        $turma = $this->turma($this->curso(), ['periodo_letivo_id' => $primeiro->id]);
        $turma->periodos()->syncWithoutDetaching([$segundo->id, $terceiro->id]);

        $this->assertEqualsCanonicalizing(
            [$primeiro->id, $segundo->id, $terceiro->id],
            $turma->periodos()->pluck('periodo_letivos.id')->all()
        );
        $this->assertSame(3, PeriodoLetivo::ativos()->count());
    }

    public function test_turma_pode_oferecer_varias_disciplinas_e_modulos_por_disciplina(): void
    {
        $curso = $this->curso();
        $turma = $this->turma($curso);
        $bib = $this->disciplina($curso, 'BIB-001');
        $teo = $this->disciplina($curso, 'TEO-001');
        $segundoModulo = Modulo::query()->create([
            'curso_id' => $curso->id, 'titulo' => 'Aprofundamento', 'ordem' => 2,
        ]);

        $teo->modulos()->syncWithoutDetaching([$segundoModulo->id]);
        $turma->turmaDisciplinas()->create(['disciplina_id' => $bib->id, 'ordem' => 1, 'ativa' => true]);
        $turma->turmaDisciplinas()->create(['disciplina_id' => $teo->id, 'ordem' => 2, 'ativa' => true]);

        $this->assertCount(2, $turma->disciplinas);
        $this->assertCount(2, $teo->modulos);
        $aula = Aula::query()->create([
            'disciplina_id' => $teo->id, 'modulo_id' => $segundoModulo->id,
            'titulo' => 'Aula de aprofundamento', 'ordem' => 1,
        ]);
        $this->assertSame($segundoModulo->id, $aula->modulo_id);
    }

    public function test_nega_disciplina_de_outro_curso(): void
    {
        $turma = $this->turma($this->curso('Curso 1'));
        $disciplina = $this->disciplina($this->curso('Curso 2'), 'OUTRO');

        $this->expectException(ValidationException::class);
        TurmaDisciplina::query()->create([
            'turma_id' => $turma->id, 'disciplina_id' => $disciplina->id,
        ]);
    }

    public function test_varios_professores_e_permissoes_especificas_na_mesma_turma(): void
    {
        $curso = $this->curso();
        $disciplina = $this->disciplina($curso, 'BIB-001');
        $turma = $this->turma($curso, ['disciplina_id' => $disciplina->id]);
        $aula = Aula::query()->create([
            'modulo_id' => $disciplina->modulo_id, 'disciplina_id' => $disciplina->id,
            'titulo' => 'Aula 1', 'ordem' => 1,
        ]);
        $instrumento = InstrumentoAvaliacao::query()->create([
            'disciplina_id' => $disciplina->id, 'tipo' => 'prova', 'titulo' => 'Prova final',
            'status' => 'rascunho',
        ]);
        $total = $this->professor('Titular');
        $aulaProfessor = $this->professor('Aula');
        $avaliador = $this->professor('Avaliador');

        $base = ['turma_id' => $turma->id, 'ativa' => true];
        ProfessorAtribuicao::query()->create($base + [
            'professor_id' => $total->id, 'escopo' => 'turma',
            'permissoes' => ['visualizar', 'editar_conteudo', 'corrigir'],
        ]);
        ProfessorAtribuicao::query()->create($base + [
            'professor_id' => $aulaProfessor->id, 'escopo' => 'aula',
            'disciplina_id' => $disciplina->id, 'modulo_id' => $disciplina->modulo_id,
            'aula_id' => $aula->id, 'permissoes' => ['visualizar', 'editar_conteudo'],
        ]);
        ProfessorAtribuicao::query()->create($base + [
            'professor_id' => $avaliador->id, 'escopo' => 'instrumento',
            'disciplina_id' => $disciplina->id, 'instrumento_id' => $instrumento->id,
            'permissoes' => ['visualizar', 'corrigir'],
        ]);

        $acl = app(PermissoesDocentes::class);
        $this->assertTrue($acl->permite($total->id, $turma->id, 'editar_conteudo', $disciplina->id, $disciplina->modulo_id, $aula->id));
        $this->assertTrue($acl->permite($aulaProfessor->id, $turma->id, 'editar_conteudo', $disciplina->id, $disciplina->modulo_id, $aula->id));
        $this->assertFalse($acl->permite($aulaProfessor->id, $turma->id, 'corrigir', $disciplina->id, $disciplina->modulo_id, $aula->id));
        $this->assertFalse($acl->permite($avaliador->id, $turma->id, 'editar_conteudo', $disciplina->id, $disciplina->modulo_id, $aula->id));
        $this->assertTrue($acl->permite($avaliador->id, $turma->id, 'corrigir', $disciplina->id, null, null, $instrumento->id));
    }

    public function test_status_academico_inscricoes_e_publicacao_sao_independentes(): void
    {
        $turma = $this->turma($this->curso(), [
            'situacao_academica' => 'em_andamento',
            'situacao_inscricoes' => 'encerradas',
            'publicada' => true,
        ]);

        $this->assertSame('em_andamento', $turma->situacao_academica);
        $this->assertSame('encerradas', $turma->situacao_inscricoes);
        $this->assertTrue($turma->publicada);
        $this->assertSame(Turma::STATUS_ABERTA, $turma->status); // compatibilidade legado
    }

    public function test_codigos_sao_gerados_sem_alterar_codigos_preexistentes(): void
    {
        $curso = $this->curso();
        $periodo = $this->periodo('2026');
        $turma = $this->turma($curso, ['periodo_letivo_id' => $periodo->id]);
        $this->assertStringStartsWith('T-', $turma->codigo);

        $modulo = Modulo::query()->create([
            'curso_id' => $curso->id, 'titulo' => 'Módulo sem código', 'ordem' => 1,
        ]);
        $disciplina = Disciplina::query()->create([
            'curso_id' => $curso->id, 'modulo_id' => $modulo->id,
            'nome' => 'Disciplina sem código', 'carga_horaria' => 40, 'ordem' => 1,
        ]);
        $this->assertStringStartsWith('DISC-', $disciplina->codigo);
        $codigo = $disciplina->codigo;
        $disciplina->update(['nome' => 'Nome atualizado']);
        $this->assertSame($codigo, $disciplina->fresh()->codigo);
    }

    public function test_inscricoes_respeitam_publicacao_e_datas_sem_mudar_status_academico(): void
    {
        $turma = $this->turma($this->curso(), [
            'situacao_academica' => 'em_andamento',
            'situacao_inscricoes' => 'abertas',
            'publicada' => true,
            'inscricoes_inicio' => '2026-06-01',
            'inscricoes_fim' => '2026-07-01',
        ]);

        $this->assertFalse($turma->inscricoesAbertasEm(CarbonImmutable::parse('2026-05-31')));
        $this->assertTrue($turma->inscricoesAbertasEm(CarbonImmutable::parse('2026-06-15')));
        $this->assertFalse($turma->inscricoesAbertasEm(CarbonImmutable::parse('2026-07-02')));
        $this->assertSame('em_andamento', $turma->situacao_academica);

        $turma->update(['publicada' => false]);
        $this->assertFalse($turma->inscricoesAbertasEm(CarbonImmutable::parse('2026-06-15')));
    }
}
