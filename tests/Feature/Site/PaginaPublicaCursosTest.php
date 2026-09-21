<?php

namespace Tests\Feature\Site;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\EventoCalendario;
use App\Models\Matricula;
use App\Models\Mensalidade;
use App\Models\Modulo;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use Database\Seeders\MatrizCurricularTeologiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura pública dos dados críticos exigidos pela Issue #23:
 *   - Curso Médio em Teologia: 3 anos, 6 módulos, 40 disciplinas, 960 horas.
 *   - Habilitação ao Diaconato: ciclo 2026 híbrido, cronograma completo
 *     e calendário de vencimentos do 2º semestre.
 *   - O simples acesso à página pública NÃO cria registros acadêmicos ou
 *     financeiros (Turma, PeriodoLetivo, Matricula, Mensalidade,
 *     EventoCalendario).
 */
class PaginaPublicaCursosTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // TEOLOGIA
    // -----------------------------------------------------------------

    public function test_pagina_teologia_exibe_duracao_e_estrutura_oficiais(): void
    {
        $this->seed(MatrizCurricularTeologiaSeeder::class);

        $response = $this->get('/');
        $response->assertOk();

        // Duração corrigida: 3 anos (antes o site dizia 2 anos).
        $response->assertSeeText('3 ANOS');

        // Estrutura oficial exibida no resumo público.
        $response->assertSeeText('6 módulos');
        $response->assertSeeText('40 disciplinas');
        $response->assertSeeText('960 horas');
    }

    public function test_pagina_teologia_renderiza_todas_as_40_disciplinas_do_seeder(): void
    {
        $this->seed(MatrizCurricularTeologiaSeeder::class);

        $curso = Curso::query()
            ->where('titulo', MatrizCurricularTeologiaSeeder::CURSO_TITULO)
            ->firstOrFail();

        $disciplinas = Disciplina::query()
            ->where('curso_id', $curso->id)
            ->orderBy('ordem')
            ->get();

        $this->assertCount(40, $disciplinas);

        $response = $this->get('/');
        $response->assertOk();

        foreach ($disciplinas as $disciplina) {
            $response->assertSeeText($disciplina->nome);
        }
    }

    public function test_pagina_teologia_exibe_todos_os_seis_modulos_ordenados(): void
    {
        $this->seed(MatrizCurricularTeologiaSeeder::class);

        $response = $this->get('/');
        $response->assertOk();

        foreach (['1º Período', '2º Período', '3º Período', '4º Período', '5º Período', '6º Período'] as $titulo) {
            $response->assertSeeText($titulo);
        }
    }

    public function test_pagina_teologia_nao_menciona_calendario_nao_confirmado(): void
    {
        // O material da Issue #23 não confirma calendário de Teologia.
        // Garantimos que as datas antigas inventadas foram removidas.
        $this->seed(MatrizCurricularTeologiaSeeder::class);

        $curso = Curso::query()
            ->where('titulo', MatrizCurricularTeologiaSeeder::CURSO_TITULO)
            ->with('modulos.disciplinas')
            ->firstOrFail();

        $this->view('site.pages.teologia', [
            'matrizTeologia' => $curso->modulos,
        ])
            ->assertDontSeeText('28 de Março de 2026')
            ->assertDontSeeText('Outubro de 2027')
            ->assertDontSeeText('Abril de 2026 a Setembro de 2027');
    }

    // -----------------------------------------------------------------
    // DIACONATO — CICLO 2026
    // -----------------------------------------------------------------

    public function test_pagina_diaconato_declara_modalidade_hibrida_para_o_ciclo_2026(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        $response->assertSeeText('Híbrida (online e presencial)');

        // O ciclo deve estar rotulado como 2026 (não como regra permanente).
        $response->assertSeeText('Ciclo 2026');

    }

    public function test_pagina_diaconato_exibe_o_cronograma_completo_do_ciclo_2026(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        $datasCronograma = [
            '28/03/2026', // Aula inaugural
            '25/04/2026', // 1ª aula
            '23/05/2026', // 2ª aula
            '27/06/2026', // 3ª aula
            '25/07/2026', // 4ª aula
            '22/08/2026', // 5ª aula
            '26/09/2026', // 6ª aula
            '24/10/2026', // Formatura (previsão)
        ];

        foreach ($datasCronograma as $data) {
            $response->assertSeeText($data);
        }

        $response->assertSeeText('quarto sábado');
    }

    public function test_pagina_diaconato_exibe_calendario_de_vencimentos_do_segundo_semestre(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        $vencimentos = [
            '25/07/2026',
            '22/08/2026',
            '26/09/2026',
            '24/10/2026',
            '28/11/2026',
            '26/12/2026',
        ];

        foreach ($vencimentos as $data) {
            $response->assertSeeText($data);
        }

        $response->assertSeeText('vencimento no quarto sábado de cada mês');
    }

    public function test_pagina_diaconato_inclui_detalhes_das_aulas_confirmadas(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        // 27/06/2026 — Introdução à Bibliologia — Pr. Otávio Sobrinho
        $response->assertSeeText('Introdução à Bibliologia');
        $response->assertSeeText('Pr. Otávio Sobrinho');
        $response->assertSeeText('Templo Matriz');
        $response->assertSeeText('09h00 às 13h00');

        // 25/07/2026 — Pr. Alexandre Santos
        $response->assertSeeText('Pr. Alexandre Santos');
        $response->assertSeeText('Igreja Matriz – Templo');
        $response->assertSeeText('09h00 às 16h00');
        $response->assertSeeText('13h10 às 14h10');
        $response->assertSeeText('10h40 às 11h00');
    }

    public function test_pagina_diaconato_nao_publica_carga_horaria_de_360_horas(): void
    {
        // A Issue #23 não confirma as 360 horas — o número deve ter sido
        // removido do site enquanto não houver fonte oficial no repositório.
        $response = $this->get('/');
        $response->assertOk();

        $response->assertDontSeeText('360 horas');
    }

    // -----------------------------------------------------------------
    // RESTRIÇÃO DE SEGURANÇA: nada é gravado por acesso público
    // -----------------------------------------------------------------

    public function test_acesso_a_pagina_publica_nao_cria_registros_academicos_ou_financeiros(): void
    {
        $this->seed(MatrizCurricularTeologiaSeeder::class);

        $turmasAntes = Turma::query()->count();
        $periodosAntes = PeriodoLetivo::query()->count();
        $matriculasAntes = Matricula::query()->count();
        $mensalidadesAntes = Mensalidade::query()->count();
        $eventosAntes = EventoCalendario::query()->count();

        $modulosAntes = Modulo::query()->count();
        $disciplinasAntes = Disciplina::query()->count();

        $this->get('/')->assertOk();
        $this->get('/')->assertOk();

        // Nada de oferta/gestão é criado.
        $this->assertSame($turmasAntes, Turma::query()->count(), 'A página pública não pode criar turmas.');
        $this->assertSame($periodosAntes, PeriodoLetivo::query()->count(), 'A página pública não pode criar períodos letivos.');
        $this->assertSame($matriculasAntes, Matricula::query()->count(), 'A página pública não pode criar matrículas.');
        $this->assertSame($mensalidadesAntes, Mensalidade::query()->count(), 'A página pública não pode criar mensalidades.');
        $this->assertSame($eventosAntes, EventoCalendario::query()->count(), 'A página pública não pode criar eventos de calendário.');

        // A matriz já semeada continua igual — nada foi duplicado pela view.
        $this->assertSame($modulosAntes, Modulo::query()->count());
        $this->assertSame($disciplinasAntes, Disciplina::query()->count());
    }

    public function test_pagina_publica_funciona_sem_a_matriz_semeada(): void
    {
        // Se o seeder ainda não rodou, a view degrada de forma segura.
        $this->assertSame(0, Curso::query()->count());

        $response = $this->get('/');
        $response->assertOk();

        // Resumo textual permanece com os totais oficiais mesmo sem dados no banco.
        $response->assertSeeText('3 ANOS');
        $response->assertSeeText('960');
    }

    public function test_pagina_inicial_exibe_a_duracao_oficial_de_teologia(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeText('Duração: 3 anos')
            ->assertDontSeeText('Duração: 2 anos');
    }

    public function test_todos_os_cursos_sao_apresentados_como_hibridos(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeText('Híbrida (online e presencial)');

        $this->assertGreaterThanOrEqual(
            3,
            substr_count($response->getContent(), 'Híbrida (online e presencial)'),
        );
    }

    public function test_pagina_lideranca_nao_oferece_modalidades_separadas(): void
    {
        $this->view('site.pages.lideranca')
            ->assertSeeText('MODALIDADE HÍBRIDA')
            ->assertSeeText('Aulas online e encontros presenciais')
            ->assertDontSeeText('Ao vivo + EAD');
    }
}
