<?php

namespace Tests\Feature;

use App\Http\Controllers\SiteController;
use App\Mail\ContatoRecebidoMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ContatoFormMailTest extends TestCase
{
    public function test_formulario_envia_mailable_para_destinatario_com_remetente_e_reply_to_corretos(): void
    {
        Route::post('/__test/fale-conosco', [SiteController::class, 'contato']);

        config()->set('mail.contact.destination', 'contato@cetsadp.com.br');
        config()->set('mail.contact.from_address', 'contato@cetsadp.com.br');
        config()->set('mail.contact.from_name', 'CETSADP');

        Mail::fake();

        $payload = [
            'nome' => 'João Visitante',
            'email' => 'visitante@example.com',
            'telefone' => '(21) 99999-0000',
            'assunto' => 'Informações sobre cursos',
            'mensagem' => 'Gostaria de saber mais sobre a grade curricular.',
            'consentimento' => '1',
        ];

        $this->postJson('/__test/fale-conosco', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso!',
            ]);

        Mail::assertSent(ContatoRecebidoMail::class, function (ContatoRecebidoMail $mail) use ($payload) {
            $envelope = $mail->envelope();
            $replyTo = $envelope->replyTo[0] ?? null;

            return $mail->hasTo('contato@cetsadp.com.br')
                && $envelope->from?->address === 'contato@cetsadp.com.br'
                && $envelope->from?->name === 'CETSADP'
                && $replyTo?->address === $payload['email']
                && $replyTo?->name === $payload['nome'];
        });
    }

    public function test_html_do_email_exibe_assunto_e_mensagem_com_escape_seguro(): void
    {
        config()->set('mail.contact.from_address', 'contato@cetsadp.com.br');
        config()->set('mail.contact.from_name', 'CETSADP');

        $dados = [
            'nome' => 'Maria <Admin>',
            'email' => 'maria@example.com',
            'telefone' => '',
            'assunto' => '<script>alert("x")</script> Pedido de informações',
            'mensagem' => "Olá <b>equipe</b>\n<script>alert('xss')</script>",
        ];

        $html = (new ContatoRecebidoMail($dados))->render();

        $this->assertStringContainsString('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; Pedido de informações', $html);
        $this->assertStringContainsString('Olá &lt;b&gt;equipe&lt;/b&gt;<br />', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert("x")</script> Pedido de informações', $html);
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $html);
    }
}
