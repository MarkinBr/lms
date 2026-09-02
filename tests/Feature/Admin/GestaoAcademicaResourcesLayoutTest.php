<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\GestaoAcademica;
use App\Filament\Resources\AulaAoVivoResource;
use App\Filament\Resources\AulaAoVivoResource\Pages\ListAulasAoVivo;
use App\Filament\Resources\CursoResource;
use App\Filament\Resources\CursoResource\Pages\CreateCurso;
use App\Filament\Resources\CursoResource\Pages\EditCurso;
use App\Filament\Resources\CursoResource\Pages\ListCursos;
use App\Filament\Resources\CursoResource\Pages\ViewCurso;
use App\Filament\Resources\CursoResource\RelationManagers\ModulosRelationManager;
use App\Filament\Resources\DisciplinaResource;
use App\Filament\Resources\DisciplinaResource\Pages\CreateDisciplina;
use App\Filament\Resources\DisciplinaResource\Pages\EditDisciplina;
use App\Filament\Resources\DisciplinaResource\Pages\ListDisciplinas;
use App\Filament\Resources\MatriculaResource;
use App\Filament\Resources\MatriculaResource\Pages\ListMatriculas;
use App\Filament\Resources\ModuloResource;
use App\Filament\Resources\ModuloResource\Pages\ListModulos;
use App\Filament\Resources\PeriodoLetivoResource;
use App\Filament\Resources\PeriodoLetivoResource\Pages\ManagePeriodoLetivos;
use App\Filament\Resources\PessoaResource;
use App\Filament\Resources\PessoaResource\Pages\ListPessoas;
use App\Filament\Resources\TurmaResource;
use App\Filament\Resources\TurmaResource\Pages\ListTurmas;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Modulo;
use App\Models\PeriodoLetivo;
use App\Models\User;
use App\Support\GestaoAcademica\NavegacaoAcademica;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Issue #61 — Regressão do shell visual acadêmico dos Resources.
 *
 * Garante que as páginas acadêmicas usam o layout compartilhado
 * (sem fi-main-sidebar/fi-topbar), mantêm navegação real, autorização
 * intacta e nenhuma funcionalidade do Filament foi perdida.
 */
class GestaoAcademicaResourcesLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function admin(): User
    {
        return User::factory()->create(['nivel' => 'admin', 'ativo' => true]);
    }

    /**
     * Nove listagens acadêmicas cobertas pela Issue.
     *
     * @return array<string, array{class-string, string}>
     */
    public static function listagensAcademicas(): array
    {
        return [
            'cursos' => [CursoResource::class, '/admin/cursos'],
            'modulos' => [ModuloResource::class, '/admin/modulos'],
            'disciplinas' => [DisciplinaResource::class, '/admin/disciplinas'],
            'periodos' => [PeriodoLetivoResource::class, '/admin/periodo-letivos'],
            'turmas' => [TurmaResource::class, '/admin/turmas'],
            'matriculas' => [MatriculaResource::class, '/admin/matriculas'],
            'alunos' => [PessoaResource::class, '/admin/pessoas'],
            'professores' => [UserResource::class, '/admin/users'],
            'aulas-ao-vivo' => [AulaAoVivoResource::class, '/admin/aula-ao-vivos'],
        ];
    }

    /** @param  class-string  $resource */
    #[DataProvider('listagensAcademicas')]
    public function test_admin_ativo_abre_todas_as_nove_listagens(string $resource, string $caminho): void
    {
        $url = $resource::getUrl('index', panel: 'admin');

        $this->assertStringContainsString($caminho, $url);
        $this->assertStringNotContainsString('/secretaria', $url);
        $this->assertStringNotContainsString('/professor', $url);

        $this->actingAs($this->admin())->get($url)->assertOk();
    }

    /** @param  class-string  $resource */
    #[DataProvider('listagensAcademicas')]
    public function test_listagem_usa_shell_academico_sem_chrome_global(string $resource): void
    {
        $this->actingAs($this->admin())
            ->get($resource::getUrl('index', panel: 'admin'))
            ->assertOk()
            ->assertSee('ga-academic-resource-layout', false)
            ->assertSee('Voltar à Central')
            ->assertSee('href="'.filament()->getPanel('admin')->getUrl().'"', false)
            ->assertSee('/admin/gestao-academica')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Pular para o conteúdo')
            ->assertSee('aria-controls="ga-navegacao"', false)
            ->assertDontSee('fi-main-sidebar', false)
            ->assertDontSee('fi-topbar', false);
    }

    public function test_criacao_edicao_e_visualizacao_usam_o_shell_academico(): void
    {
        $admin = $this->admin();
        $curso = Curso::create(['titulo' => 'Curso Shell', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo Shell', 'ordem' => 1]);
        $disciplina = Disciplina::create([
            'curso_id' => $curso->id,
            'modulo_id' => $modulo->id,
            'codigo' => 'SHELL-001',
            'nome' => 'Disciplina Shell',
            'carga_horaria' => 40,
            'ordem' => 1,
            'ativa' => true,
        ]);

        $paginas = [
            CreateCurso::getUrl(panel: 'admin'),
            EditCurso::getUrl(['record' => $curso], panel: 'admin'),
            ViewCurso::getUrl(['record' => $curso], panel: 'admin'),
            CreateDisciplina::getUrl(panel: 'admin'),
            EditDisciplina::getUrl(['record' => $disciplina], panel: 'admin'),
            ManagePeriodoLetivos::getUrl(panel: 'admin'),
        ];

        foreach ($paginas as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('ga-academic-resource-layout', false)
                ->assertDontSee('fi-main-sidebar', false)
                ->assertDontSee('fi-topbar', false);
        }
    }

    public function test_retorno_contextual_leva_a_secao_correta_da_central(): void
    {
        $this->actingAs($this->admin());

        $this->get(ListCursos::getUrl(panel: 'admin'))
            ->assertSee('/admin/gestao-academica?secao=cursos');

        $this->get(ListModulos::getUrl(panel: 'admin'))
            ->assertSee('/admin/gestao-academica?secao=estrutura');

        $this->get(ListDisciplinas::getUrl(panel: 'admin'))
            ->assertSee('/admin/gestao-academica?secao=estrutura');

        $this->get(ManagePeriodoLetivos::getUrl(panel: 'admin'))
            ->assertSee('/admin/gestao-academica?secao=operacao');

        $this->get(ListTurmas::getUrl(panel: 'admin'))
            ->assertSee('/admin/gestao-academica?secao=operacao');

        $this->get(ListMatriculas::getUrl(panel: 'admin'))
            ->assertSee('/admin/gestao-academica?secao=operacao');

        $this->get(ListPessoas::getUrl(panel: 'admin'))
            ->assertSee('/admin/gestao-academica?secao=pessoas');

        $this->get(ListUsers::getUrl(panel: 'admin'))
            ->assertSee('/admin/gestao-academica?secao=pessoas');

        $this->get(ListAulasAoVivo::getUrl(panel: 'admin'))
            ->assertSee('/admin/gestao-academica?secao=acompanhamento');
    }

    public function test_item_da_sidebar_ativo_muda_conforme_a_rota(): void
    {
        $this->actingAs($this->admin());

        $cursos = $this->get(ListCursos::getUrl(panel: 'admin'))->assertOk()->getContent();

        $padraoCursos = preg_quote(CursoResource::getUrl('index', panel: 'admin'), '/');
        $this->assertMatchesRegularExpression(
            '/href="'.$padraoCursos.'"[^>]*aria-current="page"/',
            $cursos,
            'Item Cursos não recebeu aria-current na página de Cursos'
        );

        $disciplinas = $this->get(ListDisciplinas::getUrl(panel: 'admin'))->assertOk()->getContent();

        $padraoDisciplinas = preg_quote(DisciplinaResource::getUrl('index', panel: 'admin'), '/');
        $this->assertMatchesRegularExpression(
            '/href="'.$padraoDisciplinas.'"[^>]*aria-current="page"/',
            $disciplinas,
            'Item Disciplinas não recebeu aria-current na página de Disciplinas'
        );
    }

    public function test_sidebar_aponta_para_urls_reais_dos_resources(): void
    {
        $this->actingAs($this->admin());

        $html = $this->get(ListCursos::getUrl(panel: 'admin'))->assertOk()->getContent();

        foreach (self::listagensAcademicas() as [$resource, $caminho]) {
            $this->assertStringContainsString(
                $resource::getUrl('index', panel: 'admin'),
                $html,
                "Sidebar não aponta para {$caminho}"
            );
        }

        $this->assertStringContainsString(
            GestaoAcademica::getUrl(panel: 'admin'),
            $html,
            'Sidebar não aponta para a Central (Visão geral)'
        );
    }

    public function test_administrador_inativo_e_outros_perfis_nao_ganham_acesso(): void
    {
        $url = CursoResource::getUrl('index', panel: 'admin');

        foreach (['aluno', 'professor', 'secretario'] as $nivel) {
            $usuario = User::factory()->create(['nivel' => $nivel, 'ativo' => true]);

            $resposta = $this->actingAs($usuario)->get($url);

            $this->assertContains(
                $resposta->getStatusCode(),
                [302, 403],
                "Perfil {$nivel} obteve acesso indevido"
            );
            $this->assertStringNotContainsString('ga-academic-resource-layout', $resposta->getContent());
        }

        $inativo = User::factory()->create(['nivel' => 'admin', 'ativo' => false]);
        $resposta = $this->actingAs($inativo)->get($url);
        $this->assertContains($resposta->getStatusCode(), [302, 403]);
    }

    public function test_visitante_continua_redirecionado_ao_login_admin(): void
    {
        $resposta = $this->get(CursoResource::getUrl('index', panel: 'admin'));

        $resposta->assertRedirect();

        $this->assertStringContainsString(
            '/admin/login',
            $resposta->headers->get('Location') ?? ''
        );
    }

    public function test_formularios_mantem_campos_e_validacao_atuais(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateCurso::class)
            ->assertFormFieldExists('titulo')
            ->fillForm(['titulo' => null])
            ->call('create')
            ->assertHasFormErrors(['titulo' => 'required']);
    }

    public function test_acoes_e_relation_managers_continuam_presentes(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $curso = Curso::create(['titulo' => 'Curso Ações', 'ativo' => true]);
        $modulo = Modulo::create(['curso_id' => $curso->id, 'titulo' => 'Módulo Ações', 'ordem' => 1]);

        Livewire::test(ListCursos::class)->assertTableActionExists('edit');

        Livewire::test(EditCurso::class, ['record' => $curso->getRouteKey()])
            ->assertActionExists('view')
            ->assertActionExists('delete')
            ->assertActionExists('restore')
            ->assertActionExists('forceDelete');

        Livewire::test(ModulosRelationManager::class, [
            'ownerRecord' => $curso,
            'pageClass' => EditCurso::class,
        ])->assertCanSeeTableRecords([$modulo]);
    }

    public function test_modal_de_periodo_letivo_continua_abrindo_e_validando(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ManagePeriodoLetivos::class)
            ->assertActionExists('create')
            ->mountAction('create')
            ->assertActionMounted('create')
            ->setActionData([
                'nome' => null,
                'data_inicio' => '2026-02-01',
                'data_fim' => '2026-06-30',
            ])
            ->callMountedAction()
            ->assertHasActionErrors(['nome' => 'required']);

        $this->assertDatabaseMissing('periodo_letivos', ['data_inicio' => '2026-02-01']);
    }

    public function test_periodo_letivo_criado_pelo_modal_funciona(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ManagePeriodoLetivos::class)
            ->callAction('create', [
                'nome' => '2026.2',
                'data_inicio' => '2026-08-01',
                'data_fim' => '2026-12-15',
                'ativo' => false,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('periodo_letivos', ['nome' => '2026.2']);
    }

    public function test_arquivos_novos_nao_usam_cdn_onclick_dados_ficticios_ou_outros_paineis(): void
    {
        $arquivos = [
            resource_path('views/layouts/gestao-academica-resource.blade.php'),
            resource_path('views/gestao-academica/sidebar.blade.php'),
            resource_path('views/gestao-academica/topbar.blade.php'),
            resource_path('css/filament/admin/gestao-academica-recursos.css'),
        ];

        $proibidos = [
            'onclick=', 'href="#"', 'localStorage', 'fonts.googleapis', 'fonts.gstatic',
            'http://', 'https://', 'cdn.', 'João Silva', 'maria.santos', 'CETSADP 2024',
            '(11) 99999', 'Marcos André', 'carlos.augusto',
            "'/secretaria", '"/secretaria', "'/professor", '"/professor', "'/aluno", '"/aluno',
        ];

        foreach ($arquivos as $arquivo) {
            $conteudo = file_get_contents($arquivo);
            $this->assertNotFalse($conteudo, "Arquivo ausente: {$arquivo}");

            foreach ($proibidos as $padrao) {
                $this->assertStringNotContainsStringIgnoringCase(
                    $padrao,
                    $conteudo,
                    "{$arquivo} contém padrão proibido: {$padrao}"
                );
            }
        }
    }

    public function test_central_continua_funcionando_sem_regressao(): void
    {
        $this->actingAs($this->admin())
            ->get(GestaoAcademica::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('ga-standalone-layout', false)
            ->assertDontSee('fi-main-sidebar', false);
    }

    public function test_shell_nao_introduz_n_mais_1(): void
    {
        $curso = Curso::create(['titulo' => 'Curso N+1', 'ativo' => true]);
        Modulo::create(['curso_id' => $curso->id, 'titulo' => 'M1', 'ordem' => 1]);
        PeriodoLetivo::create([
            'nome' => '2026.1',
            'data_inicio' => '2026-02-01',
            'data_fim' => '2026-06-30',
            'ativo' => true,
        ]);

        $admin = $this->admin();
        $this->actingAs($admin);

        // Aquecimento: compila views, boota painel e resolve navegação.
        $this->get(ListCursos::getUrl(panel: 'admin'))->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(ListCursos::getUrl(panel: 'admin'))->assertOk();
        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            40,
            $consultas,
            "O shell acadêmico introduziu consultas demais ({$consultas})"
        );
    }

    public function test_navegacao_academica_usa_somente_urls_do_painel_admin(): void
    {
        foreach (NavegacaoAcademica::itens() as $item) {
            $this->assertStringStartsWith(
                url('/admin'),
                $item['url'],
                "Item {$item['titulo']} aponta para fora do painel admin"
            );
        }
    }
}
