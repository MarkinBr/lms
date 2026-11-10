<?php

namespace Tests\Feature\Autorizacao;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Issue #49 — Smoke test de rotas do módulo acadêmico:
 * `route:list` executa sem erro e todos os middlewares usados estão registrados.
 */
class RotasAcademicasTest extends TestCase
{
    /**
     * Garante que `php artisan route:list` não quebra (middlewares registrados,
     * controllers existentes, métodos válidos).
     */
    public function test_route_list_executa_sem_erro(): void
    {
        $exitCode = Artisan::call('route:list');

        $this->assertSame(0, $exitCode);
    }

    /**
     * As rotas críticas de relatório de matrícula apontam para métodos
     * existentes em MatriculaReportController (Issue #49: sem
     * BadMethodCallException por método ausente).
     */
    public function test_rotas_de_relatorio_de_matricula_tem_metodos_validos(): void
    {
        foreach (['matricula.declaracao.pdf', 'matricula.historico.pdf', 'matricula.comprovante.pdf'] as $nome) {
            $rota = Route::getRoutes()->getByName($nome);

            $this->assertNotNull($rota, "Rota {$nome} não registrada.");

            [$controller, $metodo] = explode('@', $rota->getActionName());
            $this->assertTrue(
                method_exists($controller, $metodo),
                "Método {$controller}@{$metodo} ausente (rota {$nome})."
            );
        }
    }

    /**
     * Rotas de interação de aula do portal exigem middleware 'aluno' ou
     * pertencem ao sub-grupo já protegido (Issue #49: nenhuma rota de
     * aula fica apenas com 'auth' aberto a qualquer perfil).
     */
    public function test_rotas_de_interacao_de_aula_exigem_perfil_aluno(): void
    {
        $nomesCriticos = [
            'aluno.aula.concluir',
            'aluno.aula.progresso',
            'aluno.aula.comentar',
            'aluno.aula.concluir.nova',
            'aluno.aula.progresso.nova',
            'aluno.aula.tempo',
            'aluno.aula.quiz',
            'aluno.aula.notas',
        ];

        foreach ($nomesCriticos as $nome) {
            $rota = Route::getRoutes()->getByName($nome);

            $this->assertNotNull($rota, "Rota {$nome} não registrada.");
            $this->assertContains(
                'aluno',
                $rota->middleware(),
                "Rota {$nome} deveria exigir middleware 'aluno'."
            );
        }
    }
}
