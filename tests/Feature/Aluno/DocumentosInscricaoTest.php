<?php

declare(strict_types=1);

namespace Tests\Feature\Aluno;

use App\Filament\Aluno\Widgets\DocumentosPendentesWidget;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CourseModality;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Issue #53 — Central de Documentos da Inscrição no Portal do Aluno.
 *
 * Cobre os 13 itens obrigatórios da issue: posse, inativo, visitante,
 * disco privado, validação de arquivo, justificativa de rejeição,
 * não-sobrescrita de documento aprovado, alerta no dashboard e
 * não-exposição de PII/caminho físico.
 */
class DocumentosInscricaoTest extends TestCase
{
    use RefreshDatabase;

    private function alunoAtivo(array $atributos = []): User
    {
        return User::factory()->create(array_merge([
            'nivel' => 'aluno',
            'ativo' => true,
        ], $atributos));
    }

    private function inscricaoDe(User $aluno, array $atributos = []): Application
    {
        $curso = Curso::query()->create([
            'titulo' => 'Curso de Teologia',
            'ativo' => true,
        ]);

        $modalidade = CourseModality::query()->create([
            'curso_id' => $curso->id,
            'nome' => 'Híbrido',
            'ativo' => true,
        ]);

        return Application::create(array_merge([
            'protocol' => 'TEST-'.Str::upper(Str::random(12)),
            'nome_completo' => 'Aluno Teste',
            'email' => $aluno->email,
            'cpf' => '12345678901',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'telefone' => '11999999999',
            'cep' => '01001000',
            'logradouro' => 'Rua Teste',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'escolaridade' => 'Superior',
            'curso_id' => $curso->id,
            'course_modality_id' => $modalidade->id,
            'status' => Application::STATUS_EM_ANALISE,
        ], $atributos));
    }

    // 1 + 4. Aluno autenticado vê a central; visitante é barrado.
    public function test_visitante_nao_acessa_a_central(): void
    {
        $this->get(route('aluno.documentos-inscricao'))->assertRedirect(); // middleware auth
    }

    public function test_aluno_visualiza_somente_seus_documentos(): void
    {
        $aluno = $this->alunoAtivo();
        $inscricao = $this->inscricaoDe($aluno);

        $resposta = $this->actingAs($aluno)->get(route('aluno.documentos-inscricao'));

        $resposta->assertOk();
        $resposta->assertSee('Documentos da inscrição');
        $resposta->assertSee('Foto 3x4');
    }

    // 2. Aluno não acessa nem envia documento de outro aluno.
    public function test_aluno_nao_envia_documento_para_inscricao_de_outro(): void
    {
        Storage::fake('applications');

        $alunoA = $this->alunoAtivo(['email' => 'aluno-a@example.com']);
        $alunoB = $this->alunoAtivo(['email' => 'aluno-b@example.com']);
        $inscricaoB = $this->inscricaoDe($alunoB);

        // Aluno A não tem inscrição própria => 403, nada gravado.
        $resposta = $this->actingAs($alunoA)->post(route('aluno.documentos-inscricao.store'), [
            'tipo' => 'rg',
            'arquivo' => UploadedFile::fake()->image('rg.jpg'),
        ]);

        $resposta->assertForbidden();
        $this->assertCount(0, ApplicationDocument::all());
    }

    // 3. Usuário inativo recebe 403 na visualização e no envio.
    public function test_usuario_inativo_recebe_403(): void
    {
        $inativo = $this->alunoAtivo(['ativo' => false]);
        $this->inscricaoDe($inativo);

        $this->actingAs($inativo)->get(route('aluno.documentos-inscricao'))->assertForbidden();

        Storage::fake('applications');
        $this->actingAs($inativo)->post(route('aluno.documentos-inscricao.store'), [
            'tipo' => 'rg',
            'arquivo' => UploadedFile::fake()->image('rg.jpg'),
        ])->assertForbidden();
    }

    public function test_perfil_de_secretaria_nao_acessa_a_central_do_aluno(): void
    {
        $secretario = User::factory()->create([
            'nivel' => 'secretario',
            'ativo' => true,
        ]);

        $this->actingAs($secretario)
            ->get(route('aluno.documentos-inscricao'))
            ->assertForbidden();
    }

    // 5 + 8. Upload válido vai para o disco privado e nada toca o público.
    public function test_upload_valido_salva_no_disco_privado_e_nada_no_publico(): void
    {
        Storage::fake('applications');
        Storage::fake('public');

        $aluno = $this->alunoAtivo();
        $this->inscricaoDe($aluno);

        $resposta = $this->actingAs($aluno)->post(route('aluno.documentos-inscricao.store'), [
            'tipo' => 'rg',
            'arquivo' => UploadedFile::fake()->image('rg.jpg'),
        ]);

        $resposta->assertRedirect(route('aluno.documentos-inscricao'));

        $documento = ApplicationDocument::firstOrFail();

        Storage::disk('applications')->assertExists($documento->caminho_arquivo);
        $this->assertStringNotContainsString('public', $documento->caminho_arquivo);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    // 6. Arquivo inválido (tipo errado) é rejeitado.
    public function test_arquivo_com_tipo_invalido_e_rejeitado(): void
    {
        Storage::fake('applications');

        $aluno = $this->alunoAtivo();
        $this->inscricaoDe($aluno);

        $resposta = $this->actingAs($aluno)->post(route('aluno.documentos-inscricao.store'), [
            'tipo' => 'rg',
            'arquivo' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
        ]);

        $resposta->assertSessionHasErrors('arquivo');
        $this->assertCount(0, ApplicationDocument::all());
    }

    // 7. Arquivo acima de 10 MB é rejeitado.
    public function test_arquivo_acima_do_limite_e_rejeitado(): void
    {
        Storage::fake('applications');

        $aluno = $this->alunoAtivo();
        $this->inscricaoDe($aluno);

        $resposta = $this->actingAs($aluno)->post(route('aluno.documentos-inscricao.store'), [
            'tipo' => 'rg',
            'arquivo' => UploadedFile::fake()->image('rg.jpg')->size(11 * 1024),
        ]);

        $resposta->assertSessionHasErrors('arquivo');
        $this->assertCount(0, ApplicationDocument::all());
    }

    // 9. Path traversal no nome do arquivo não escapa do diretório privado.
    public function test_nome_malicioso_nao_grava_fora_do_disco_privado(): void
    {
        Storage::fake('applications');
        Storage::fake('public');

        $aluno = $this->alunoAtivo();
        $this->inscricaoDe($aluno);

        $arquivo = UploadedFile::fake()->image('..%2F..%2Fevil.jpg');

        $this->actingAs($aluno)->post(route('aluno.documentos-inscricao.store'), [
            'tipo' => 'rg',
            'arquivo' => $arquivo,
        ]);

        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    // 9 + 10. Rejeição exibe somente a justificativa pública, escapada.
    public function test_documento_rejeitado_exibe_justificativa_publica_com_escape(): void
    {
        $aluno = $this->alunoAtivo();
        $inscricao = $this->inscricaoDe($aluno);
        $secretario = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);

        $documento = ApplicationDocument::create([
            'application_id' => $inscricao->id,
            'tipo' => 'rg',
            'nome_original' => 'rg.jpg',
            'nome_armazenado' => 'rg_abc.jpg',
            'mime_type' => 'image/jpeg',
            'tamanho' => 100,
            'caminho_arquivo' => 'applications/'.$inscricao->id.'/rg_abc.jpg',
        ]);
        $documento->invalidateDocument(
            (string) $secretario->id,
            'Documento ilegível. <script>alert(1)</script> Reenvie.',
        );

        $resposta = $this->actingAs($aluno)->get(route('aluno.documentos-inscricao'));

        $resposta->assertOk();
        $resposta->assertSee('Rejeitado');
        $resposta->assertSee('Documento ilegível.');
        // Escapado: a tag <script> nunca aparece crua no HTML.
        $resposta->assertDontSee('<script>alert(1)</script>', false);
    }

    // 11. Documento aprovado não pode ser sobrescrito por auto-serviço.
    public function test_documento_aprovado_nao_pode_ser_sobrescrito(): void
    {
        Storage::fake('applications');

        $aluno = $this->alunoAtivo();
        $inscricao = $this->inscricaoDe($aluno);
        $secretario = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);

        $documento = ApplicationDocument::create([
            'application_id' => $inscricao->id,
            'tipo' => 'rg',
            'nome_original' => 'rg.jpg',
            'nome_armazenado' => 'rg_abc.jpg',
            'mime_type' => 'image/jpeg',
            'tamanho' => 100,
            'caminho_arquivo' => 'applications/'.$inscricao->id.'/rg_abc.jpg',
        ]);
        $documento->validateDocument((string) $secretario->id);

        $resposta = $this->actingAs($aluno)->post(route('aluno.documentos-inscricao.store'), [
            'tipo' => 'rg',
            'arquivo' => UploadedFile::fake()->image('novo-rg.jpg'),
        ]);

        $resposta->assertSessionHasErrors('arquivo');
        // Continua existindo apenas o documento original aprovado.
        $this->assertCount(1, ApplicationDocument::all());
        $this->assertTrue(ApplicationDocument::firstOrFail()->validado);
    }

    // 12 + 13. Alerta do dashboard segue as pendências (via regra do widget).
    public function test_widget_mostra_alerta_quando_ha_pendencias(): void
    {
        $aluno = $this->alunoAtivo();
        $this->inscricaoDe($aluno);

        $this->actingAs($aluno);

        $this->assertTrue(DocumentosPendentesWidget::canView());
    }

    public function test_widget_nao_mostra_alerta_quando_nao_ha_pendencias(): void
    {
        $aluno = $this->alunoAtivo();
        $inscricao = $this->inscricaoDe($aluno);
        $secretario = User::factory()->create(['nivel' => 'secretario', 'ativo' => true]);

        // Marca todos os obrigatórios como enviados.
        foreach (['foto_3x4', 'rg', 'cpf', 'comprovante_residencia', 'historico_escolar'] as $tipo) {
            ApplicationDocument::create([
                'application_id' => $inscricao->id,
                'tipo' => $tipo,
                'nome_original' => $tipo.'.jpg',
                'nome_armazenado' => $tipo.'_x.jpg',
                'mime_type' => 'image/jpeg',
                'tamanho' => 100,
                'caminho_arquivo' => 'applications/'.$inscricao->id.'/'.$tipo.'_x.jpg',
            ]);
        }

        $this->actingAs($aluno);

        $this->assertFalse(DocumentosPendentesWidget::canView());
    }

    public function test_dashboard_web_exibe_aviso_e_link_para_documentos_pendentes(): void
    {
        $aluno = $this->alunoAtivo();
        $this->inscricaoDe($aluno);

        $resposta = $this->actingAs($aluno)->get(route('aluno.dashboard'));

        $resposta->assertOk()
            ->assertSee('Documentos da inscrição')
            ->assertSee('Enviar documentos')
            ->assertSee(route('aluno.documentos-inscricao'));
    }

    public function test_dashboard_considera_matricula_ativa_como_curso_ativo(): void
    {
        $aluno = $this->alunoAtivo();
        $inscricao = $this->inscricaoDe($aluno);

        Matricula::create([
            'user_id' => $aluno->id,
            'curso_id' => $inscricao->curso_id,
            'application_id' => $inscricao->id,
            'status' => Matricula::STATUS_ATIVA,
        ]);

        $this->actingAs($aluno)
            ->get(route('aluno.dashboard'))
            ->assertOk()
            ->assertSee('Cursos Ativos')
            ->assertSee('Curso de Teologia');
    }

    public function test_atalho_legado_de_documentos_redireciona_para_a_central(): void
    {
        $aluno = $this->alunoAtivo();
        $this->inscricaoDe($aluno);

        $this->actingAs($aluno)
            ->get(route('aluno.documentos'))
            ->assertRedirect(route('aluno.documentos-inscricao'));
    }

    public function test_portal_renderiza_frequencia_e_notas_com_matricula_sem_turma(): void
    {
        $aluno = $this->alunoAtivo();
        $inscricao = $this->inscricaoDe($aluno);

        Matricula::create([
            'user_id' => $aluno->id,
            'curso_id' => $inscricao->curso_id,
            'application_id' => $inscricao->id,
            'status' => Matricula::STATUS_ATIVA,
        ]);

        $this->actingAs($aluno)
            ->get(route('aluno.frequencia'))
            ->assertOk()
            ->assertSee('Curso de Teologia');

        $this->actingAs($aluno)
            ->get(route('aluno.notas'))
            ->assertOk()
            ->assertSee('Curso de Teologia');
    }

    // 13. HTML da central não expõe CPF, endereço, caminho físico nem URL pública.
    public function test_html_nao_expoe_pii_nem_caminho_fisico(): void
    {
        Storage::fake('applications');

        $aluno = $this->alunoAtivo();
        $inscricao = $this->inscricaoDe($aluno);

        ApplicationDocument::create([
            'application_id' => $inscricao->id,
            'tipo' => 'rg',
            'nome_original' => 'rg.jpg',
            'nome_armazenado' => 'rg_abc.jpg',
            'mime_type' => 'image/jpeg',
            'tamanho' => 100,
            'caminho_arquivo' => 'applications/'.$inscricao->id.'/rg_abc.jpg',
        ]);

        $resposta = $this->actingAs($aluno)->get(route('aluno.documentos-inscricao'));
        $html = $resposta->getContent();

        $resposta->assertOk();
        $this->assertStringNotContainsString('12345678901', $html); // CPF
        $this->assertStringNotContainsString('Rua Teste', $html);   // endereço
        $this->assertStringNotContainsString('caminho_arquivo', $html);
        $this->assertStringNotContainsString('storage/app', $html); // caminho físico
        $this->assertStringNotContainsString('/storage/', $html);   // URL pública
    }

    // 15. Falha no storage não deixa registro parcial no banco.
    public function test_falha_no_storage_nao_deixa_registro_parcial(): void
    {
        // Disco configurado como público é recusado com 500, sem gravar nada.
        config()->set('filesystems.applications_disk', 'public');

        $aluno = $this->alunoAtivo();
        $this->inscricaoDe($aluno);

        $resposta = $this->actingAs($aluno)->post(route('aluno.documentos-inscricao.store'), [
            'tipo' => 'rg',
            'arquivo' => UploadedFile::fake()->image('rg.jpg'),
        ]);

        $resposta->assertStatus(500);
        $this->assertCount(0, ApplicationDocument::all());
    }

    // Vínculo por e-mail: aluno sem inscrição vinculada não vê pendências de terceiros.
    public function test_aluno_sem_inscricao_nao_ve_pendencias_de_terceiros(): void
    {
        $alunoSemInscricao = $this->alunoAtivo(['email' => 'sem-inscricao@example.com']);
        $outroAluno = $this->alunoAtivo(['email' => 'outro@example.com']);
        $this->inscricaoDe($outroAluno);

        $resposta = $this->actingAs($alunoSemInscricao)->get(route('aluno.documentos-inscricao'));

        $resposta->assertOk();
        $resposta->assertSee('Nenhuma inscrição encontrada');
    }
}
