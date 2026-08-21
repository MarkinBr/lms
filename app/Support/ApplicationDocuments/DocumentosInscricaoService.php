<?php

declare(strict_types=1);

namespace App\Support\ApplicationDocuments;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use App\Policies\ApplicationDocumentPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Serviço central da Issue #53 — Central de Documentos da Inscrição.
 *
 * Concentra num único ponto três responsabilidades que precisavam existir
 * junto para não vazar regra pelas camadas de UI:
 *
 *  1. Descobrir a(s) inscrição(ões) do aluno autenticado. O vínculo real,
 *     conforme já usado por {@see ApplicationDocumentPolicy}
 *     e pelo endpoint `uploadDocument` da API, é o e-mail normalizado
 *     (nunca `user_id === application_id`).
 *  2. Traduzir os documentos exigidos em {@see Application::DOCUMENT_TYPES}
 *     para um resumo de status pronto para a UI (pendente, enviado,
 *     em_analise, aprovado, rejeitado) — sem expor CPF, RG, endereço ou
 *     caminho físico do arquivo.
 *  3. Persistir o upload no disco privado configurado, reaproveitando
 *     estritamente o mesmo caminho gravado pela API pública para que a
 *     Policy e o download continuem funcionando sem alteração.
 */
final class DocumentosInscricaoService
{
    /**
     * Tipos considerados obrigatórios para a Central do aluno.
     *
     * Mantido separado de {@see Application::DOCUMENT_TYPES} porque a
     * constante do model também lista "outro", que é anexo livre e não
     * conta como pendência.
     *
     * @var list<string>
     */
    public const TIPOS_OBRIGATORIOS = [
        'foto_3x4',
        'rg',
        'cpf',
        'comprovante_residencia',
        'historico_escolar',
    ];

    /**
     * Descrições institucionais dos tipos exigidos. Ficam no servidor
     * para evitar que um HTML injete texto arbitrário nos rótulos.
     *
     * @var array<string, array{label: string, descricao: string, obrigatorio: bool}>
     */
    public const CATALOGO = [
        'foto_3x4' => [
            'label' => 'Foto 3x4',
            'descricao' => 'Foto recente, colorida, com fundo claro.',
            'obrigatorio' => true,
        ],
        'rg' => [
            'label' => 'RG',
            'descricao' => 'Documento de identidade (frente e verso).',
            'obrigatorio' => true,
        ],
        'cpf' => [
            'label' => 'CPF',
            'descricao' => 'Documento de CPF ou comprovante equivalente.',
            'obrigatorio' => true,
        ],
        'comprovante_residencia' => [
            'label' => 'Comprovante de residência',
            'descricao' => 'Conta de consumo dos últimos 90 dias.',
            'obrigatorio' => true,
        ],
        'historico_escolar' => [
            'label' => 'Histórico escolar',
            'descricao' => 'Histórico do ensino médio ou equivalente.',
            'obrigatorio' => true,
        ],
        'certificado_conclusao' => [
            'label' => 'Certificado de conclusão',
            'descricao' => 'Certificado ou diploma do último curso concluído.',
            'obrigatorio' => false,
        ],
        'outro' => [
            'label' => 'Outro documento',
            'descricao' => 'Documento adicional solicitado pela secretaria.',
            'obrigatorio' => false,
        ],
    ];

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_ENVIADO = 'enviado';

    public const STATUS_EM_ANALISE = 'em_analise';

    public const STATUS_APROVADO = 'aprovado';

    public const STATUS_REJEITADO = 'rejeitado';

    public const TAMANHO_MAX_BYTES = 10 * 1024 * 1024; // 10 MB

    public const MIMES_ACEITOS = ['jpg', 'jpeg', 'png', 'pdf'];

    /**
     * Localiza a inscrição "corrente" do aluno autenticado.
     *
     * Regras (nesta ordem):
     *  1. Usuário inativo => null (a UI cai em 403).
     *  2. Sem e-mail => null (sem vínculo possível).
     *  3. Inscrição em análise mais recente por e-mail normalizado; se não
     *     houver, a mais recente em qualquer status (para exibir o
     *     resultado da análise, incluindo rejeição justificada).
     */
    public function inscricaoAtualDo(User $aluno): ?Application
    {
        if (! $aluno->ativo) {
            return null;
        }

        $email = $this->normalizarEmail($aluno->email);

        if ($email === null) {
            return null;
        }

        $base = Application::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->with('documents');

        return (clone $base)
            ->where('status', Application::STATUS_EM_ANALISE)
            ->orderByDesc('created_at')
            ->first()
            ?? (clone $base)->orderByDesc('created_at')->first();
    }

    /**
     * Resumo pronto para a UI (dashboard + página exclusiva).
     *
     * @return array{
     *     tem_inscricao: bool,
     *     pode_enviar: bool,
     *     total: int,
     *     enviados: int,
     *     em_analise: int,
     *     aprovados: int,
     *     rejeitados: int,
     *     pendentes: int,
     *     documentos: Collection<int, array<string, mixed>>,
     * }
     */
    public function resumoDo(User $aluno): array
    {
        $inscricao = $this->inscricaoAtualDo($aluno);

        $documentos = $this->documentosDaInscricao($inscricao);
        $contabilizados = $documentos->where('obrigatorio', true);

        $enviados = $contabilizados->where('status', '!==', self::STATUS_PENDENTE)->count();
        $emAnalise = $contabilizados->where('status', self::STATUS_EM_ANALISE)->count();
        $aprovados = $contabilizados->where('status', self::STATUS_APROVADO)->count();
        $rejeitados = $contabilizados->where('status', self::STATUS_REJEITADO)->count();

        return [
            'tem_inscricao' => $inscricao !== null,
            'pode_enviar' => $inscricao !== null && $this->podeEnviarNaInscricao($inscricao),
            'total' => $contabilizados->count(),
            'enviados' => $enviados,
            'em_analise' => $emAnalise,
            'aprovados' => $aprovados,
            'rejeitados' => $rejeitados,
            'pendentes' => $contabilizados->where('status', self::STATUS_PENDENTE)->count(),
            'documentos' => $documentos,
        ];
    }

    /**
     * Cruza {@see self::CATALOGO} com os documentos existentes da inscrição.
     * A saída NUNCA contém `caminho_arquivo` nem qualquer PII.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function documentosDaInscricao(?Application $inscricao): Collection
    {
        $existentes = $inscricao?->documents ?? collect();

        return collect(self::CATALOGO)->map(function (array $meta, string $tipo) use ($existentes) {
            /** @var ApplicationDocument|null $ultimo */
            $ultimo = $existentes
                ->where('tipo', $tipo)
                ->sortByDesc('created_at')
                ->first();

            return [
                'tipo' => $tipo,
                'label' => $meta['label'],
                'descricao' => $meta['descricao'],
                'obrigatorio' => $meta['obrigatorio'],
                'status' => $this->statusDe($ultimo),
                'status_label' => $this->rotuloStatus($this->statusDe($ultimo)),
                // Somente o mínimo para a UI. Nada de caminho de arquivo.
                'nome_original' => $ultimo?->nome_original,
                'tamanho_formatado' => $ultimo?->tamanho_formatado,
                'enviado_em' => $ultimo?->created_at?->toIso8601String(),
                'justificativa_publica' => $this->justificativaPublica($ultimo),
            ];
        })
            ->values();
    }

    /**
     * Regras de aceite do upload. Retorna a inscrição carregada (com Policy
     * já verificada) ou lança {@see ValidationException}/aborta 403.
     *
     * @throws ValidationException
     */
    public function receberUpload(User $aluno, string $tipo, UploadedFile $arquivo): ApplicationDocument
    {
        if (! $aluno->ativo) {
            abort(403);
        }

        if (! array_key_exists($tipo, self::CATALOGO)) {
            throw ValidationException::withMessages([
                'tipo' => 'Tipo de documento inválido.',
            ]);
        }

        $inscricao = $this->inscricaoAtualDo($aluno);

        if ($inscricao === null) {
            abort(403);
        }

        if (! $this->podeEnviarNaInscricao($inscricao)) {
            throw ValidationException::withMessages([
                'arquivo' => 'Esta inscrição já foi finalizada e não aceita mais envios.',
            ]);
        }

        $ultimo = $inscricao->documents
            ->where('tipo', $tipo)
            ->sortByDesc('created_at')
            ->first();

        // Documento já aprovado não pode ser sobrescrito por auto-serviço.
        // Alteração exige regra explícita da secretaria (auditoria manual).
        if ($ultimo instanceof ApplicationDocument && $this->statusDe($ultimo) === self::STATUS_APROVADO) {
            throw ValidationException::withMessages([
                'arquivo' => 'Este documento já foi aprovado. Solicite a substituição diretamente à secretaria.',
            ]);
        }

        $this->validarArquivo($arquivo);

        return $this->persistir($aluno, $inscricao, $tipo, $arquivo);
    }

    /**
     * Garante que o disco configurado não é o público e faz o storeAs no
     * mesmo padrão da API (Issue #50): `applications/<uuid>/<tipo>_<uuid>.<ext>`.
     *
     * Se qualquer passo falhar, nada fica gravado (rollback de DB + delete
     * do binário) — critério #15 do prompt.
     */
    private function persistir(
        User $aluno,
        Application $inscricao,
        string $tipo,
        UploadedFile $arquivo,
    ): ApplicationDocument {
        $disco = (string) config('filesystems.applications_disk');

        if ($disco === '' || $disco === 'public') {
            report(new RuntimeException('Disco de documentos de inscrição não configurado corretamente.'));
            abort(500);
        }

        $extensao = strtolower((string) $arquivo->getClientOriginalExtension());
        if (! in_array($extensao, self::MIMES_ACEITOS, true)) {
            // Defesa em profundidade: já validado antes, mas nunca aceite
            // uma extensão fora da lista no momento do storeAs.
            throw ValidationException::withMessages([
                'arquivo' => 'Extensão de arquivo não permitida.',
            ]);
        }

        $pasta = 'applications/'.$inscricao->id;
        $nomeSeguro = $tipo.'_'.Str::uuid()->toString().'.'.$extensao;

        $caminho = null;

        try {
            $caminho = $arquivo->storeAs($pasta, $nomeSeguro, $disco);

            if ($caminho === false || $caminho === '' || $caminho === null) {
                throw new RuntimeException('Falha ao persistir arquivo no disco privado.');
            }

            /** @var ApplicationDocument $documento */
            $documento = DB::transaction(fn () => ApplicationDocument::create([
                'application_id' => $inscricao->id,
                'tipo' => $tipo,
                'nome_armazenado' => $nomeSeguro,
                'nome_original' => $arquivo->getClientOriginalName(),
                'mime_type' => $arquivo->getClientMimeType(),
                'tamanho' => $arquivo->getSize(),
                'caminho_arquivo' => $caminho,
                'validado' => false,
                'validado_por_id' => null,
                'observacoes_validacao' => null,
                'validado_em' => null,
            ]));

            return $documento;
        } catch (Throwable $exception) {
            if ($caminho !== null && $caminho !== '' && Storage::disk($disco)->exists($caminho)) {
                Storage::disk($disco)->delete($caminho);
            }

            report($exception);

            throw ValidationException::withMessages([
                'arquivo' => 'Não foi possível concluir o envio agora. Tente novamente em instantes.',
            ]);
        }
    }

    /**
     * Regra de aceite:
     * - MIME real de {@see UploadedFile::getMimeType()} precisa ser um dos
     *   permitidos (não confia em getClientMimeType, que vem do navegador).
     * - Extensão precisa bater com o MIME.
     * - Tamanho <= 10MB.
     * - Nome original não pode conter path traversal.
     *
     * @throws ValidationException
     */
    private function validarArquivo(UploadedFile $arquivo): void
    {
        $tamanho = (int) $arquivo->getSize();

        if ($tamanho <= 0 || $tamanho > self::TAMANHO_MAX_BYTES) {
            throw ValidationException::withMessages([
                'arquivo' => 'O arquivo deve ter até 10 MB.',
            ]);
        }

        $mimeReal = (string) $arquivo->getMimeType();

        $mimesPermitidos = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'application/pdf' => ['pdf'],
        ];

        if (! array_key_exists($mimeReal, $mimesPermitidos)) {
            throw ValidationException::withMessages([
                'arquivo' => 'Formato de arquivo não permitido. Envie JPG, PNG ou PDF.',
            ]);
        }

        $extensao = strtolower((string) $arquivo->getClientOriginalExtension());
        if (! in_array($extensao, $mimesPermitidos[$mimeReal], true)) {
            throw ValidationException::withMessages([
                'arquivo' => 'A extensão do arquivo não confere com o conteúdo enviado.',
            ]);
        }

        $nome = (string) $arquivo->getClientOriginalName();
        if ($nome === ''
            || Str::contains($nome, ['..', '/', '\\', "\0"])
        ) {
            throw ValidationException::withMessages([
                'arquivo' => 'Nome de arquivo inválido.',
            ]);
        }
    }

    private function podeEnviarNaInscricao(Application $inscricao): bool
    {
        return $inscricao->status === Application::STATUS_EM_ANALISE;
    }

    private function statusDe(?ApplicationDocument $documento): string
    {
        if ($documento === null) {
            return self::STATUS_PENDENTE;
        }

        // "validado = true" com data => aprovado.
        if ($documento->validado === true && $documento->validado_em !== null) {
            return self::STATUS_APROVADO;
        }

        // "validado = false" + validado_por => secretaria já analisou e rejeitou.
        if ($documento->validado === false && $documento->validado_por_id !== null && $documento->validado_em !== null) {
            return self::STATUS_REJEITADO;
        }

        // "validado = false" + validado_em preenchido sem validador
        // significa "em análise" (o backoffice tocou mas ainda não decidiu).
        if ($documento->validado_em !== null) {
            return self::STATUS_EM_ANALISE;
        }

        return self::STATUS_ENVIADO;
    }

    private function rotuloStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_ENVIADO => 'Enviado',
            self::STATUS_EM_ANALISE => 'Em análise',
            self::STATUS_APROVADO => 'Aprovado',
            self::STATUS_REJEITADO => 'Rejeitado',
            default => 'Pendente',
        };
    }

    /**
     * Somente a justificativa pública da secretaria é devolvida. Nunca
     * observações internas nem PII. O texto é retornado bruto (a view
     * escapa via Blade `{{ }}`).
     */
    private function justificativaPublica(?ApplicationDocument $documento): ?string
    {
        if ($documento === null) {
            return null;
        }

        if ($this->statusDe($documento) !== self::STATUS_REJEITADO) {
            return null;
        }

        $texto = trim((string) ($documento->observacoes_validacao ?? ''));

        return $texto === '' ? null : $texto;
    }

    private function normalizarEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = mb_strtolower(trim($email));

        return $email === '' ? null : $email;
    }
}
