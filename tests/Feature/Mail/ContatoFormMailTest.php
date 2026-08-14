<?php

namespace Tests\Feature\Mail;

use App\Mail\ContatoRecebidoMail;
use App\Services\NotificarContatoRecebidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ContatoFormMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_formulario_envia_email_institucional_com_reply_to_do_visitante(): void
    {
        Mail::fake();
        config()->set('mail.contact.to.address', 'contato@cetsadp.com.br');
        config()->set('mail.contact.from.address', 'contato@cetsadp.com.br');

        $response = $this->postJson(route('site.contato'), [
            'nome' => 'Maria da Silva',
            'email' => 'maria@example.test',
            'telefone' => '21999999999',
            'assunto' => 'Dúvidas sobre cursos',
            'mensagem' => 'Gostaria de saber mais sobre o curso.',
            'consentimento' => '1',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(ContatoRecebidoMail::class, function (ContatoRecebidoMail $mail): bool {
            $render = $mail->render();
            $envelope = $mail->envelope();

            return $mail->hasTo('contato@cetsadp.com.br')
                && $envelope->subject === 'Novo contato pelo site — CETSADP'
                && $envelope->replyTo[0]->address === 'maria@example.test'
                && str_contains($render, 'Maria da Silva')
                && str_contains($render, 'Dúvidas sobre cursos')
                && str_contains($render, 'Gostaria de saber mais sobre o curso.');
        });
    }

    public function test_conteudo_do_contato_e_escapado_no_html(): void
    {
        Mail::fake();

        $this->postJson(route('site.contato'), [
            'nome' => '<img src=x onerror=alert(1)>',
            'email' => 'visitante@example.test',
            'assunto' => 'Parcerias e outros',
            'mensagem' => '<script>alert("xss")</script> & texto',
            'consentimento' => '1',
        ])->assertOk();

        Mail::assertSent(ContatoRecebidoMail::class, function (ContatoRecebidoMail $mail): bool {
            $render = $mail->render();

            return ! str_contains($render, '<img src=x onerror=alert(1)>')
                && ! str_contains($render, '<script>alert("xss")</script>')
                && str_contains($render, '&lt;script&gt;');
        });
    }

    public function test_validacao_invalida_nao_envia_contato(): void
    {
        Mail::fake();

        $this->postJson(route('site.contato'), [
            'nome' => 'Sem assunto',
            'email' => 'email-invalido',
            'mensagem' => '',
        ])->assertUnprocessable();

        Mail::assertNothingSent();
    }

    public function test_falha_do_provedor_nao_produz_erro_500(): void
    {
        $this->mock(NotificarContatoRecebidoService::class)
            ->shouldReceive('enviar')
            ->once()
            ->andReturnFalse();

        $response = $this->postJson(route('site.contato'), [
            'nome' => 'Visitante',
            'email' => 'visitante@example.test',
            'assunto' => 'Financeiro',
            'mensagem' => 'Preciso de orientação.',
            'consentimento' => '1',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', false);
    }

    public function test_servico_captura_falha_real_do_transporte_sem_logar_dados_do_visitante(): void
    {
        Log::spy();
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('falha SMTP com conteúdo sensível'));

        $resultado = app(NotificarContatoRecebidoService::class)->enviar([
            'nome' => 'Visitante',
            'email' => 'visitante@example.test',
            'telefone' => '21999999999',
            'assunto' => 'Financeiro',
            'mensagem' => 'Mensagem privada',
        ]);

        $this->assertFalse($resultado);
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $mensagem, array $contexto): bool {
                return $mensagem === 'Falha ao enviar contato recebido pelo site.'
                    && $contexto === ['exception' => RuntimeException::class];
            });
    }
}
