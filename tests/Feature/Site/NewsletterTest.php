<?php

namespace Tests\Feature\Site;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_inscricao_normaliza_email_e_persiste_assinante_ativo(): void
    {
        $response = $this->postJson(route('site.newsletter'), [
            'email' => '  Pessoa@Example.Test ',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'pessoa@example.test',
            'ativo' => true,
        ]);
    }

    public function test_email_repetido_e_idempotente_e_nao_cria_linha_duplicada(): void
    {
        $this->postJson(route('site.newsletter'), ['email' => 'pessoa@example.test'])
            ->assertOk();

        $this->postJson(route('site.newsletter'), ['email' => 'PESSOA@EXAMPLE.TEST'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, NewsletterSubscriber::query()->count());
    }

    public function test_assinante_inativo_e_reativado_sem_duplicar(): void
    {
        NewsletterSubscriber::query()->create([
            'email' => 'antigo@example.test',
            'ativo' => false,
        ]);

        $this->postJson(route('site.newsletter'), ['email' => 'ANTIGO@EXAMPLE.TEST'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'antigo@example.test',
            'ativo' => true,
        ]);
        $this->assertSame(1, NewsletterSubscriber::query()->count());
    }

    public function test_email_invalido_nao_cria_assinante(): void
    {
        $this->postJson(route('site.newsletter'), ['email' => 'nao-e-mail'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }
}
