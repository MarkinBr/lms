<?php

namespace Tests\Feature\Auth;

use App\Services\Acessos\CredenciaisAluno;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcessoAssistidoIdiomaTest extends TestCase
{
    public function test_acesso_assistido_expirado_exibe_erro_em_portugues(): void
    {
        config(['app.locale' => 'en']);

        $this->get(route('aluno.assistido.senha'))
            ->assertForbidden()
            ->assertSee('Acesso não permitido')
            ->assertSee('Sua credencial pode estar inválida ou expirada.')
            ->assertDontSee('Forbidden');
    }

    public function test_erros_comuns_tambem_estao_em_portugues(): void
    {
        config(['app.locale' => 'en']);

        $this->view('errors.419')->assertSee('Sessão expirada')->assertDontSee('Page Expired');
        $this->view('errors.429')->assertSee('Muitas tentativas')->assertDontSee('Too Many Requests');
    }

    public function test_validacao_de_nova_senha_e_em_portugues_mesmo_com_locale_ingles(): void
    {
        config(['app.locale' => 'en']);

        try {
            app(CredenciaisAluno::class)->definirSenha([], 'curta', 'curta');
            $this->fail('Uma senha curta não pode ser aceita.');
        } catch (ValidationException $exception) {
            $this->assertContains('A senha deve ter pelo menos 12 caracteres.', $exception->errors()['password']);
        }

        try {
            app(CredenciaisAluno::class)->definirSenha([], 'SenhaForte#2026', 'OutraSenha#2026');
            $this->fail('Uma confirmação diferente não pode ser aceita.');
        } catch (ValidationException $exception) {
            $this->assertContains('A confirmação da senha não corresponde.', $exception->errors()['password']);
        }
    }
}
