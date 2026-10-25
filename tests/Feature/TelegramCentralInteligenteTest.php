<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\User;
use App\Services\Telegram\ConsultasAcademicasTelegram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramCentralInteligenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_painel_agenda_relatorios_e_aulas_pendentes_sao_respostas_limitadas(): void
    {
        $servico = app(ConsultasAcademicasTelegram::class);
        $this->assertStringContainsString('Painel do dia', $servico->hoje()['text']);
        $this->assertStringContainsString('Agenda de sete dias', $servico->agendaSemanal()['text']);
        $this->assertStringContainsString('Relatório por curso', $servico->relatorios()['text']);
        $this->assertStringContainsString('Aulas não liberadas', $servico->aulasPendentes()['text']);
    }

    public function test_busca_e_case_insensitive_e_percentual_e_literal(): void
    {
        User::factory()->create(['nivel' => 'aluno', 'name' => 'João% Ensino']);
        User::factory()->create(['nivel' => 'aluno', 'name' => 'JoãoOutro Ensino']);
        $consulta = app(ConsultasAcademicasTelegram::class);
        $this->assertStringContainsString('João% Ensino', $consulta->buscarAluno('joão%')['text']);
        $this->assertStringNotContainsString('JoãoOutro', $consulta->buscarAluno('joão%')['text']);
    }

    public function test_busca_guiada_oferece_ficha_por_id_e_rotas_de_consulta(): void
    {
        $aluno = User::factory()->create(['nivel' => 'aluno', 'name' => 'Pesquisa Guiada']);
        $consulta = app(ConsultasAcademicasTelegram::class);
        $busca = $consulta->buscarAluno('Pesquisa');
        $this->assertSame('ficha:'.$aluno->id, $busca['reply_markup']['inline_keyboard'][0][0]['callback_data']);
        $ficha = $consulta->aluno($aluno->id);
        $botoes = json_encode($ficha['reply_markup']['inline_keyboard']);
        $this->assertStringContainsString('notas:'.$aluno->id, $botoes);
        $this->assertStringContainsString('frequencia:'.$aluno->id, $botoes);
    }

    public function test_filtros_guiados_oferecem_atalhos_de_status_e_curso(): void
    {
        $curso = Curso::create(['titulo' => 'Curso de Liderança', 'ativo' => true]);
        $resposta = app(ConsultasAcademicasTelegram::class)->filtros();
        $botoes = json_encode($resposta['reply_markup']['inline_keyboard']);
        $this->assertStringContainsString('filtro:status:ativo:1', $botoes);
        $this->assertStringContainsString('filtro:curso:'.$curso->id.':1', $botoes);
    }

    public function test_filtro_alunos_por_curso_e_status_sem_expor_contato(): void
    {
        $curso = Curso::create(['titulo' => 'Teologia', 'ativo' => true]);
        User::factory()->create(['nivel' => 'aluno', 'name' => 'Filtrado Aluno', 'ativo' => true]);
        $consulta = app(ConsultasAcademicasTelegram::class);
        $this->assertStringContainsString('Filtrado Aluno', $consulta->filtrarAlunos('status', 'ativo', 1)['text']);
        $this->assertStringContainsString('Nenhum', $consulta->filtrarAlunos('curso', (string) $curso->id, 1)['text']);
        $this->assertStringContainsString('Opção inválida', $consulta->filtrarAlunos('email', '1', 1)['text']);
    }
}
