<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\GestaoAcademica;
use App\Filament\Resources\CursoResource;
use App\Filament\Resources\DisciplinaResource;
use App\Filament\Resources\PeriodoLetivoResource;
use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\PeriodoLetivo;
use App\Models\User;
use App\Services\GestaoAcademica\PainelAcademicoQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestaoAcademicaPageTest extends TestCase
{
    use RefreshDatabase;

    private int $sequenciaDisciplina = 1;

    private function admin(): User
    {
        return User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
    }

    private function disciplina(
        Curso $curso,
        Modulo $modulo,
        string $nome,
        ?string $codigo = null,
        ?int $ordem = null,
    ): Disciplina {
        $sequencia = $this->sequenciaDisciplina++;

        return Disciplina::create([
            'curso_id' => $curso->id,
            'modulo_id' => $modulo->id,
            'codigo' => $codigo ?? sprintf('DISC-%03d', $sequencia),
            'nome' => $nome,
            'carga_horaria' => 40,
            'ordem' => $ordem ?? $sequencia,
            'ativa' => true,
        ]);
    }

    public function test_admin_ativo_acessa_a_central(): void
    {
        $this->actingAs($this->admin())
            ->get(GestaoAcademica::getUrl(panel: 'admin'))
            ->assertOk();
    }

    public function test_perfis_nao_autorizados_nao_acessam(): void
    {
        $url = GestaoAcademica::getUrl(panel: 'admin');

        // Visitante
        $this->get($url)->assertRedirect();

        foreach (['aluno', 'professor', 'secretario'] as $nivel) {
            $usuario = User::factory()->create(['nivel' => $nivel, 'ativo' => true]);

            $this->actingAs($usuario)->get($url)->assertForbidden();
        }

        // Admin inativo é recusado sem acessar nenhum dado da central.
        $inativo = User::factory()->create(['nivel' => 'admin', 'ativo' => false]);
        $this->actingAs($inativo)->get($url)->assertForbidden();
    }

    public function test_indicadores_usam_dados_reais(): void
    {
        $curso = Curso::create(['titulo' => 'Curso Real', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo 1', 'ordem' => 1]);
        $this->disciplina($curso, $modulo, 'Disciplina Real');
        PeriodoLetivo::create(['nome' => '2026.1', 'data_inicio' => '2026-02-01', 'data_fim' => '2026-06-30', 'ativo' => true]);

        $this->actingAs($this->admin())
            ->get(GestaoAcademica::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('Curso Real');
    }

    public function test_nenhum_conteudo_ficticio_do_html_aparece(): void
    {
        $ficticios = [
            'João Silva',
            'maria.santos@',
            'CETSADP 2024',
            '(11) 99999',
        ];

        $resposta = $this->actingAs($this->admin())
            ->get(GestaoAcademica::getUrl(panel: 'admin'));

        foreach ($ficticios as $texto) {
            $resposta->assertDontSee($texto);
        }
    }

    public function test_central_tem_carregamento_acessivel_e_acao_unica_por_disciplina(): void
    {
        $curso = Curso::create(['titulo' => 'Curso acessível', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo acessível', 'ordem' => 1]);
        $this->disciplina($curso, $modulo, 'Disciplina acessível');

        $this->actingAs($this->admin())
            ->get(GestaoAcademica::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('role="status"', false)
            ->assertSee('Atualizando a árvore curricular')
            ->assertDontSee('role="alert"', false)
            ->assertSee('Gerenciar disciplina e aulas')
            ->assertDontSee('#aulas', false);
    }

    public function test_estado_vazio_renderizado(): void
    {
        $this->actingAs($this->admin())
            ->get(GestaoAcademica::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('Nenhum curso ativo encontrado');
    }

    public function test_arvore_exibe_somente_o_curso_selecionado(): void
    {
        $cursoA = Curso::create(['titulo' => 'Teologia Sistemática', 'ativo' => true]);
        $cursoB = Curso::create(['titulo' => 'Aconselhamento Bíblico', 'ativo' => true]);

        $moduloA = Modulo::create(['curso_id' => $cursoA->id, 'titulo' => 'Fundamentos', 'ordem' => 1]);
        $moduloB = Modulo::create(['curso_id' => $cursoB->id, 'titulo' => 'Prática', 'ordem' => 1]);

        $disciplinaA = $this->disciplina($cursoA, $moduloA, 'Bibliologia');
        $disciplinaB = $this->disciplina($cursoB, $moduloB, 'Escuta Ativa');

        Aula::create(['modulo_id' => $moduloA->id, 'disciplina_id' => $disciplinaA->id, 'titulo' => 'Inspiração das Escrituras', 'ordem' => 1]);
        Aula::create(['modulo_id' => $moduloB->id, 'disciplina_id' => $disciplinaB->id, 'titulo' => 'Primeiro Atendimento', 'ordem' => 1]);

        $arvore = app(PainelAcademicoQuery::class)->arvoreDoCurso($cursoA->id);

        $this->assertNotNull($arvore);
        $this->assertSame('Teologia Sistemática', $arvore['curso']['titulo']);
        $this->assertCount(1, $arvore['modulos']);
        $this->assertSame('Fundamentos', $arvore['modulos'][0]['titulo']);
        $this->assertSame('Bibliologia', $arvore['modulos'][0]['disciplinas'][0]['nome']);
        $this->assertSame('Inspiração das Escrituras', $arvore['modulos'][0]['disciplinas'][0]['aulas'][0]['titulo']);
    }

    public function test_acoes_geram_urls_dos_resources_corretos(): void
    {
        $this->assertStringContainsString('/admin/cursos', CursoResource::getUrl('index', panel: 'admin'));
        $this->assertStringContainsString('/admin/disciplinas', DisciplinaResource::getUrl('index', panel: 'admin'));
        $this->assertStringContainsString('/admin/periodo-letivos', PeriodoLetivoResource::getUrl('index', panel: 'admin'));
        $this->assertStringNotContainsString('/secretaria', PeriodoLetivoResource::getUrl('index', panel: 'admin'));
    }

    public function test_disciplina_criada_no_modulo_herda_curso_e_modulo(): void
    {
        $curso = Curso::create(['titulo' => 'Curso X', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Mod', 'ordem' => 1]);

        $disciplina = $this->disciplina($curso, $modulo, 'Hermenêutica');

        $this->assertSame($curso->id, $disciplina->curso_id);
        $this->assertSame($modulo->id, $disciplina->modulo_id);
    }

    public function test_aula_criada_na_disciplina_herda_modulo(): void
    {
        $curso = Curso::create(['titulo' => 'Curso Y', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Mod', 'ordem' => 1]);
        $disciplina = $this->disciplina($curso, $modulo, 'Exegese');

        $aula = Aula::create(['disciplina_id' => $disciplina->id, 'titulo' => 'Gêneros literários', 'ordem' => 1]);

        $this->assertSame($disciplina->id, $aula->disciplina_id);
        $this->assertSame($modulo->id, $aula->modulo_id);
    }

    public function test_cruzamento_entre_cursos_e_rejeitado(): void
    {
        $cursoA = Curso::create(['titulo' => 'Curso A', 'ativo' => true]);
        $cursoB = Curso::create(['titulo' => 'Curso B', 'ativo' => true]);
        $moduloA = Modulo::create(['curso_id' => $cursoA->id, 'titulo' => 'Mod A', 'ordem' => 1]);
        $moduloB = Modulo::create(['curso_id' => $cursoB->id, 'titulo' => 'Mod B', 'ordem' => 1]);
        $this->expectException(ValidationException::class);

        Disciplina::create([
            'curso_id' => $cursoA->id,
            'modulo_id' => $moduloB->id,
            'codigo' => 'INVALIDA-001',
            'nome' => 'Inválida',
            'carga_horaria' => 40,
            'ordem' => 1,
            'ativa' => true,
        ]);
    }

    public function test_aula_com_modulo_incompativel_e_rejeitada(): void
    {
        $cursoA = Curso::create(['titulo' => 'Curso A2', 'ativo' => true]);
        $moduloA = Modulo::create(['curso_id' => $cursoA->id, 'titulo' => 'Mod A2', 'ordem' => 1]);
        $cursoB = Curso::create(['titulo' => 'Curso B2', 'ativo' => true]);
        $moduloB = Modulo::create(['curso_id' => $cursoB->id, 'titulo' => 'Mod B2', 'ordem' => 1]);
        $disciplinaB = $this->disciplina($cursoB, $moduloB, 'Disc B2');

        $this->expectException(ValidationException::class);

        Aula::create(['modulo_id' => $moduloA->id, 'disciplina_id' => $disciplinaB->id, 'titulo' => 'Cruzada', 'ordem' => 1]);
    }

    public function test_aula_legada_sem_disciplina_aparece_como_pendencia(): void
    {
        $curso = Curso::create(['titulo' => 'Curso Legado', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Mod Leg', 'ordem' => 1]);
        DB::table('aulas')->insert([
            'modulo_id' => $modulo->id,
            'disciplina_id' => null,
            'titulo' => 'Aula antiga',
            'ordem' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $consulta = app(PainelAcademicoQuery::class);
        $pendencias = $consulta->pendencias();

        $this->assertSame(1, $pendencias['aulas_sem_disciplina']['count']);

        // E não é remapeada automaticamente
        $this->assertNull(Aula::where('titulo', 'Aula antiga')->value('disciplina_id'));
    }

    public function test_pagina_nao_dispara_n_mais_1(): void
    {
        $curso = Curso::create(['titulo' => 'Curso N1', 'ativo' => true]);
        for ($i = 1; $i <= 4; $i++) {
            $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => "Mod $i", 'ordem' => $i]);
            $disciplina = $this->disciplina($curso, $modulo, "Disc $i", ordem: $i);
            Aula::create(['modulo_id' => $modulo->id, 'disciplina_id' => $disciplina->id, 'titulo' => "Aula $i", 'ordem' => 1]);
        }

        $this->actingAs($this->admin());

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(GestaoAcademica::getUrl(panel: 'admin'))->assertOk();
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Limite conservador: contagens + árvore de 1 curso + autenticação.
        // Crescer linearmente por linha da árvore indicaria N+1.
        $this->assertLessThanOrEqual(40, $total, "Queries demais na central: {$total}");
    }

    public function test_indicador_de_matriculas_ativas_aceita_status_ativo_e_ativa(): void
    {
        $curso = Curso::create(['titulo' => 'Curso Matrículas', 'ativo' => true]);

        foreach (['ativo', 'ativa', 'cancelada'] as $status) {
            Matricula::create([
                'user_id' => User::factory()->create()->id,
                'curso_id' => $curso->id,
                'data_matricula' => now()->toDateString(),
                'status' => $status,
            ]);
        }

        $resumo = app(PainelAcademicoQuery::class)->resumo();

        $this->assertSame(2, $resumo['matriculas_ativas']);
    }
}
