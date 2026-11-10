<?php

namespace Tests\Feature\Mail;

use App\Mail\InscricaoConfirmacaoMail;
use App\Models\Application;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Services\NotificarInscricaoRecebidaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class InscricaoConfirmacaoMailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Application, 1: Curso, 2: CourseModality}
     */
    private function criarInscricao(string $protocolo, string $tituloCurso, string $nomeModalidade): array
    {
        $curso = Curso::query()->create([
            'titulo' => $tituloCurso,
            'ativo' => true,
        ]);
        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => $nomeModalidade,
            'ativo' => true,
        ]);
        $application = Application::query()->create([
            'protocol' => $protocolo,
            'nome_completo' => 'Maria da Silva',
            'email' => 'maria@example.test',
            'telefone' => '11999999999',
            'whatsapp' => '11999999999',
            'cpf' => '12345678901',
            'rg' => '123456789',
            'data_nascimento' => '1990-01-15',
            'sexo' => 'F',
            'escolaridade' => 'medio_completo',
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'numero' => '1',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ]);

        return [$application, $curso, $modalidade];
    }

    public function test_mailable_monta_envelope_e_conteudo_para_inscricao_unica(): void
    {
        $mail = new InscricaoConfirmacaoMail(
            nomeCandidato: 'Maria da Silva',
            inscricoes: [[
                'protocol' => 'INSC-AAA11111-20260813',
                'curso' => 'Curso Médio em Teologia',
                'modalidade' => 'Híbrido',
            ]],
        );

        $this->assertSame('Confirmação de inscrição — CETSADP', $mail->envelope()->subject);
        $this->assertSame('emails.inscricao-confirmacao', $mail->content()->view);
        $this->assertSame('Maria da Silva', $mail->nomeCandidato);
        $this->assertCount(1, $mail->inscricoes);
    }

    public function test_mailable_rejeita_quantidade_invalida(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InscricaoConfirmacaoMail(nomeCandidato: 'Maria', inscricoes: []);
    }

    public function test_mailable_rejeita_lote_com_mais_de_tres_inscricoes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InscricaoConfirmacaoMail(
            nomeCandidato: 'Maria',
            inscricoes: [
                ['protocol' => 'P1', 'curso' => 'A', 'modalidade' => 'Híbrido'],
                ['protocol' => 'P2', 'curso' => 'B', 'modalidade' => 'Híbrido'],
                ['protocol' => 'P3', 'curso' => 'C', 'modalidade' => 'Híbrido'],
                ['protocol' => 'P4', 'curso' => 'D', 'modalidade' => 'Híbrido'],
            ],
        );
    }

    public function test_envelope_usa_remetente_e_reply_to_configuraveis(): void
    {
        config()->set('mail.inscricoes.from.address', 'inscricoes@cetsadp.com.br');
        config()->set('mail.inscricoes.from.name', 'CETSADP');
        config()->set('mail.inscricoes.reply_to.address', 'contato@cetsadp.com.br');
        config()->set('mail.inscricoes.reply_to.name', 'Secretaria CETSADP');

        $envelope = (new InscricaoConfirmacaoMail(
            nomeCandidato: 'Maria',
            inscricoes: [['protocol' => 'P1', 'curso' => 'A', 'modalidade' => 'Híbrido']],
        ))->envelope();

        $this->assertSame('inscricoes@cetsadp.com.br', $envelope->from->address);
        $this->assertSame('CETSADP', $envelope->from->name);
        $this->assertSame('contato@cetsadp.com.br', $envelope->replyTo[0]->address);
        $this->assertSame('Secretaria CETSADP', $envelope->replyTo[0]->name);
    }

    public function test_envelope_tem_fallback_completo_para_remetente_global(): void
    {
        config()->set('mail.inscricoes', null);
        config()->set('mail.from.address', 'geral@cetsadp.com.br');
        config()->set('mail.from.name', 'CETSADP Geral');

        $envelope = (new InscricaoConfirmacaoMail(
            nomeCandidato: 'Maria',
            inscricoes: [['protocol' => 'P1', 'curso' => 'A', 'modalidade' => 'Híbrido']],
        ))->envelope();

        $this->assertSame('geral@cetsadp.com.br', $envelope->from->address);
        $this->assertSame('CETSADP Geral', $envelope->from->name);
        $this->assertSame('geral@cetsadp.com.br', $envelope->replyTo[0]->address);
        $this->assertSame('CETSADP Geral', $envelope->replyTo[0]->name);
    }

    public function test_servico_envia_uma_mensagem_ao_destinatario_correto(): void
    {
        Mail::fake();
        [$application] = $this->criarInscricao('INSC-UNICO001-20260813', 'Curso Médio em Teologia', 'Híbrido');

        $enviado = app(NotificarInscricaoRecebidaService::class)->enviar(collect([$application]));

        $this->assertTrue($enviado);
        Mail::assertSent(InscricaoConfirmacaoMail::class, function (InscricaoConfirmacaoMail $mail) use ($application): bool {
            return $mail->hasTo('maria@example.test')
                && $mail->inscricoes === [[
                    'protocol' => $application->protocol,
                    'curso' => 'Curso Médio em Teologia',
                    'modalidade' => 'Híbrido',
                ]];
        });
        Mail::assertSentCount(1);
    }

    public function test_servico_consolida_lote_em_um_email_na_ordem_recebida(): void
    {
        Mail::fake();
        [$a] = $this->criarInscricao('INSC-LOTE00A1-20260813', 'Curso Médio em Teologia', 'Híbrido');
        [$b] = $this->criarInscricao('INSC-LOTE00B2-20260813', 'Habilitação ao Diaconato', 'Híbrido');
        [$c] = $this->criarInscricao('INSC-LOTE00C3-20260813', 'Liderança Cristã Ministerial', 'Híbrido');

        $enviado = app(NotificarInscricaoRecebidaService::class)->enviar(collect([$a, $b, $c]));

        $this->assertTrue($enviado);
        Mail::assertSent(InscricaoConfirmacaoMail::class, function (InscricaoConfirmacaoMail $mail): bool {
            return $mail->hasTo('maria@example.test')
                && array_column($mail->inscricoes, 'protocol') === [
                    'INSC-LOTE00A1-20260813',
                    'INSC-LOTE00B2-20260813',
                    'INSC-LOTE00C3-20260813',
                ]
                && array_column($mail->inscricoes, 'curso') === [
                    'Curso Médio em Teologia',
                    'Habilitação ao Diaconato',
                    'Liderança Cristã Ministerial',
                ];
        });
        Mail::assertSentCount(1);
    }

    public function test_servico_nao_envia_colecao_vazia_ou_emails_divergentes(): void
    {
        Mail::fake();

        $service = app(NotificarInscricaoRecebidaService::class);
        $this->assertFalse($service->enviar(collect()));

        [$a] = $this->criarInscricao('INSC-MIX000A1-20260813', 'Curso Médio em Teologia', 'Híbrido');
        [$b] = $this->criarInscricao('INSC-MIX000B2-20260813', 'Habilitação ao Diaconato', 'Híbrido');
        $b->email = 'outro@example.test';

        $this->assertFalse($service->enviar(collect([$a, $b])));
        Mail::assertNothingSent();
    }

    public function test_falha_no_transporte_retorna_false_e_log_inteiro_nao_contem_dados_sensiveis(): void
    {
        [$application] = $this->criarInscricao('INSC-FALHA001-20260813', 'Curso Médio em Teologia', 'Híbrido');

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->once()->andReturnSelf();
        $mailer->shouldReceive('send')->once()->andThrow(new RuntimeException(
            'SMTP indisponível para maria@example.test protocolo INSC-FALHA001-20260813',
        ));
        Mail::swap($mailer);

        $registroCapturado = null;
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $mensagem, array $contexto) use (&$registroCapturado): bool {
                $registroCapturado = ['mensagem' => $mensagem, 'contexto' => $contexto];

                return true;
            });

        $enviado = app(NotificarInscricaoRecebidaService::class)->enviar(collect([$application]));

        $this->assertFalse($enviado);
        $this->assertSame(RuntimeException::class, $registroCapturado['contexto']['exception']);
        $this->assertSame(1, $registroCapturado['contexto']['quantidade_inscricoes']);
        $this->assertSame([$application->id], $registroCapturado['contexto']['application_ids']);

        $logCompleto = json_encode($registroCapturado, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('maria@example.test', $logCompleto);
        $this->assertStringNotContainsString('INSC-FALHA001', $logCompleto);
        $this->assertStringNotContainsString('12345678901', $logCompleto);
        $this->assertStringNotContainsString('11999999999', $logCompleto);
        $this->assertStringNotContainsString('Praça da Sé', $logCompleto);
        $this->assertStringNotContainsString('SMTP indisponível', $logCompleto);
    }

    public function test_renderizacao_escapa_todos_os_campos_dinamicos_e_nao_expoe_dados_sensiveis(): void
    {
        $nome = '<script>alert("nome")</script> Maria';
        $protocolo = 'INSC-<script>alert("protocolo")</script>';
        $curso = 'Curso <img src=x onerror=alert("curso")>';
        $modalidade = '<svg onload=alert("modalidade")>Híbrido</svg>';

        $mail = new InscricaoConfirmacaoMail(
            nomeCandidato: $nome,
            inscricoes: [[
                'protocol' => $protocolo,
                'curso' => $curso,
                'modalidade' => $modalidade,
            ]],
        );
        $html = $mail->render();

        $this->assertStringContainsString(e($nome), $html);
        $this->assertStringContainsString(e($protocolo), $html);
        $this->assertStringContainsString(e($curso), $html);
        $this->assertStringContainsString(e($modalidade), $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<svg onload=', $html);

        $this->assertStringContainsString('Em análise', $html);
        $this->assertStringContainsString('Acompanhar inscrição', $html);
        $this->assertStringContainsString('protocolo', mb_strtolower($html));
        $this->assertStringContainsString('e-mail', mb_strtolower($html));
        $this->assertStringContainsString(url('/'), $html);
        $this->assertStringContainsString(
            'Centro Educacional, Teológico e Social da Igreja Evangélica Assembleia de Deus em Paracambi',
            $html,
        );

        foreach (['12345678901', '123456789', '11999999999', '01001-000', 'Praça da Sé', '1990-01-15'] as $sensivel) {
            $this->assertStringNotContainsString($sensivel, $html);
        }
        $this->assertStringNotContainsString('signature', mb_strtolower($html));
        $this->assertStringNotContainsString('senha', mb_strtolower($html));
    }
}
