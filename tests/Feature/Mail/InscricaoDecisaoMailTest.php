<?php

namespace Tests\Feature\Mail;

use App\Mail\InscricaoDecisaoMail;
use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\User;
use App\Services\AprovarInscricaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class InscricaoDecisaoMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_matricula_envia_email_com_dados_publicos_e_link_assinado(): void
    {
        Mail::fake();
        Notification::fake();
        [$inscricao, $secretaria] = $this->criarContexto();

        $resultado = app(AprovarInscricaoService::class)->executar($inscricao, $secretaria);

        Mail::assertSent(InscricaoDecisaoMail::class, function (InscricaoDecisaoMail $mail) use ($resultado): bool {
            $html = $mail->render();

            return $mail->hasTo($resultado->application->email)
                && $mail->tipo === InscricaoDecisaoMail::TIPO_MATRICULA
                && str_contains($html, $resultado->matricula->numero_matricula)
                && str_contains($html, $resultado->application->protocol)
                && str_contains($html, '/acompanhar-inscricao/')
                && str_contains($html, 'signature=')
                && ! str_contains($html, '12345678901')
                && ! str_contains($html, 'Rua Reservada');
        });
    }

    public function test_rejeicao_envia_motivo_escapado_e_segunda_aprovacao_nao_reenvia(): void
    {
        Mail::fake();
        Notification::fake();
        [$inscricao, $secretaria] = $this->criarContexto();
        $motivo = 'Documento <script>alert("xss")</script> pendente';

        app(AprovarInscricaoService::class)->rejeitar($inscricao, $secretaria, $motivo);

        Mail::assertSent(InscricaoDecisaoMail::class, function (InscricaoDecisaoMail $mail): bool {
            $html = $mail->render();

            return $mail->hasTo('candidata@example.test')
                && $mail->tipo === InscricaoDecisaoMail::TIPO_REJEICAO
                && str_contains($html, '&lt;script&gt;')
                && ! str_contains($html, '<script>alert("xss")</script>');
        });

        [$outraInscricao, $outraSecretaria] = $this->criarContexto('outra@example.test', '98765432100');
        $service = app(AprovarInscricaoService::class);
        $service->executar($outraInscricao, $outraSecretaria);
        $service->executar($outraInscricao->fresh(), $outraSecretaria);

        Mail::assertSent(InscricaoDecisaoMail::class, 2);
    }

    public function test_falha_do_provedor_nao_desfaz_matricula(): void
    {
        Notification::fake();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('provedor indisponível'));
        [$inscricao, $secretaria] = $this->criarContexto();

        $resultado = app(AprovarInscricaoService::class)->executar($inscricao, $secretaria);

        $this->assertSame(Application::STATUS_MATRICULADO, $resultado->application->status);
        $this->assertDatabaseHas('matriculas', ['application_id' => $inscricao->id]);
    }

    /** @return array{0: Application, 1: User} */
    private function criarContexto(string $email = 'candidata@example.test', string $cpf = '12345678901'): array
    {
        $curso = Curso::query()->create(['titulo' => 'Teologia', 'ativo' => true]);
        $modalidade = CourseModality::query()->create(['curso_id' => $curso->id, 'nome' => 'EAD', 'ativo' => true]);
        $secretaria = User::query()->create(['name' => 'Secretaria', 'email' => 'secretaria-'.uniqid().'@example.test', 'password' => 'x', 'nivel' => 'secretario', 'ativo' => true]);
        $inscricao = Application::query()->create([
            'protocol' => 'INSC-'.strtoupper(uniqid()),
            'nome_completo' => 'Maria da Silva',
            'cpf' => $cpf,
            'rg' => '99.888.777-6',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'nacionalidade' => 'Brasileira',
            'email' => $email,
            'telefone' => '11999999999',
            'cep' => '01001-000',
            'logradouro' => 'Rua Reservada',
            'numero' => '123',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'escolaridade' => 'medio_completo',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);

        return [$inscricao, $secretaria];
    }
}
