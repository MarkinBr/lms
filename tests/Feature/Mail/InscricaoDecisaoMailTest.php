<?php

namespace Tests\Feature\Mail;

use App\Mail\InscricaoDecisaoMail;
use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\User;
use App\Services\AprovarInscricaoService;
use App\Services\NotificarDecisaoInscricaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InscricaoDecisaoMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
    }

    private function criarCurso(string $titulo = 'Curso de Teologia'): Curso
    {
        return Curso::query()->create([
            'titulo' => $titulo,
            'ativo' => true,
        ]);
    }

    private function criarModalidade(Curso $curso): CourseModality
    {
        return CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'Híbrida',
            'ativo' => true,
        ]);
    }

    private function criarSecretaria(): User
    {
        return User::query()->create([
            'name' => 'Secretaria',
            'email' => 'secretaria@example.test',
            'password' => 'senha-teste',
            'nivel' => 'secretario',
            'ativo' => true,
        ]);
    }

    private function criarInscricao(Curso $curso, CourseModality $modalidade): Application
    {
        return Application::query()->create([
            'protocol' => 'PROT-'.uniqid(),
            'nome_completo' => 'Maria da Silva',
            'cpf' => '52998224725',
            'rg' => '99.888.777-6',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'nacionalidade' => 'Brasileira',
            'email' => 'maria@example.test',
            'telefone' => '11999999999',
            'cep' => '01001-000',
            'logradouro' => 'Rua Confidencial',
            'numero' => '123',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'escolaridade' => 'medio_completo',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);
    }

    public function test_aprovacao_envia_email_com_dados_publicos_e_matricula(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);

        $resultado = app(AprovarInscricaoService::class)->executar(
            $inscricao,
            $this->criarSecretaria(),
        );

        Mail::assertSent(InscricaoDecisaoMail::class, function (InscricaoDecisaoMail $mail) use ($resultado): bool {
            $render = $mail->render();

            return $mail->hasTo('maria@example.test')
                && $mail->tipo === InscricaoDecisaoMail::TIPO_MATRICULA
                && $mail->envelope()->subject === 'Matrícula confirmada — CETSADP'
                && str_contains($render, 'Maria da Silva')
                && str_contains($render, 'Curso de Teologia')
                && str_contains($render, 'Híbrida')
                && str_contains($render, $resultado->application->protocol)
                && str_contains($render, $resultado->matricula->numero_matricula)
                && str_contains($render, '/acompanhar-inscricao/')
                && str_contains($render, 'signature=')
                && ! str_contains($render, '52998224725')
                && ! str_contains($render, '99.888.777-6')
                && ! str_contains($render, 'Rua Confidencial')
                && ! str_contains($render, '11999999999');
        });
    }

    public function test_rejeicao_envia_email_com_motivo_escapado(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);
        $motivo = 'Motivo <script>alert("xss")</script> & finalizado';

        app(AprovarInscricaoService::class)->rejeitar(
            $inscricao,
            $this->criarSecretaria(),
            $motivo,
        );

        Mail::assertSent(InscricaoDecisaoMail::class, function (InscricaoDecisaoMail $mail) use ($motivo): bool {
            $render = $mail->render();

            return $mail->hasTo('maria@example.test')
                && $mail->tipo === InscricaoDecisaoMail::TIPO_REJEICAO
                && $mail->envelope()->subject === 'Resultado da sua inscrição — CETSADP'
                && str_contains($render, '&lt;script&gt;')
                && str_contains($render, '/acompanhar-inscricao/')
                && str_contains($render, 'signature=')
                && str_contains($render, 'Não aprovada')
                && ! str_contains($render, '<script>alert("xss")</script>')
                && ! str_contains($render, $motivo)
                && ! str_contains($render, '52998224725')
                && ! str_contains($render, 'Rua Confidencial');
        });
    }

    public function test_segunda_aprovacao_nao_reenvia_decisao(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);
        $secretaria = $this->criarSecretaria();
        $service = app(AprovarInscricaoService::class);

        $service->executar($inscricao, $secretaria);
        $service->executar($inscricao->fresh(), $secretaria);

        Mail::assertSent(InscricaoDecisaoMail::class, 1);
        $this->assertSame(1, Matricula::query()->count());
    }

    public function test_falha_no_envio_nao_desfaz_aprovacao(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);

        $this->mock(NotificarDecisaoInscricaoService::class)
            ->shouldReceive('notificarMatricula')
            ->once()
            ->andReturnFalse();

        $resultado = app(AprovarInscricaoService::class)->executar(
            $inscricao,
            $this->criarSecretaria(),
        );

        $this->assertSame(Application::STATUS_MATRICULADO, $resultado->application->status);
        $this->assertDatabaseHas('matriculas', ['application_id' => $inscricao->id]);
    }

    public function test_falha_no_envio_nao_desfaz_rejeicao(): void
    {
        $curso = $this->criarCurso();
        $modalidade = $this->criarModalidade($curso);
        $inscricao = $this->criarInscricao($curso, $modalidade);

        $this->mock(NotificarDecisaoInscricaoService::class)
            ->shouldReceive('notificarRejeicao')
            ->once()
            ->andReturnFalse();

        $resultado = app(AprovarInscricaoService::class)->rejeitar(
            $inscricao,
            $this->criarSecretaria(),
            'Documentação incompleta',
        );

        $this->assertSame(Application::STATUS_REJEITADO, $resultado->status);
        $this->assertSame('Documentação incompleta', $resultado->observacoes_secretaria);
        $this->assertSame(0, Matricula::query()->count());
    }
}
