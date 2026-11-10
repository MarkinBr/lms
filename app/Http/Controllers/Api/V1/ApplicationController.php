<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CourseModality;
use App\Models\User;
use App\Services\AprovarInscricaoException;
use App\Services\AprovarInscricaoService;
use App\Services\NotificarInscricaoRecebidaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly NotificarInscricaoRecebidaService $notificarInscricao,
    ) {}

    /**
     * Regras comuns dos dados pessoais/endereço do candidato.
     * O e-mail deixou de ser globalmente único e o CPF passou a ser
     * validado por curso (ver regrasCpfEmailPorCurso()).
     */
    private function regrasDadosPessoais(): array
    {
        return [
            'nome_completo' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefone' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'cpf' => 'required|string|max:14',
            'rg' => 'nullable|string|max:20',
            'data_nascimento' => 'required|date|before:today',
            'sexo' => 'required|in:M,F,Outro',
            'estado_civil' => 'nullable|string|max:50',
            'nacionalidade' => 'nullable|string|max:100',
            'naturalidade_cidade' => 'nullable|string|max:100',
            'naturalidade_estado' => 'nullable|string|max:2',
            'escolaridade' => 'required|string|max:100',

            // Endereço
            'cep' => 'required|string|max:10',
            'logradouro' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|max:2',
        ];
    }

    /**
     * CPF (sem máscara) deve ser único dentro do mesmo curso.
     * O mesmo CPF pode se inscrever em cursos diferentes.
     */
    private function regrasCpfEmailPorCurso(int $cursoId): array
    {
        $cpfRule = Rule::unique('applications', 'cpf')
            ->where('curso_id', $cursoId);

        return ['cpf' => ['required', 'string', 'max:14', $cpfRule]];
    }

    /**
     * Lista todas as inscrições (Secretaria/Admin)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Application::with(['curso', 'documents'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($cursoId = $request->get('curso_id')) {
            $query->where('curso_id', $cursoId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nome_completo', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%");
            });
        }

        $applications = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $applications->items(),
            'pagination' => [
                'page' => $applications->currentPage(),
                'limit' => $applications->perPage(),
                'total' => $applications->total(),
                'total_pages' => $applications->lastPage(),
            ],
        ]);
    }

    /**
     * Cria uma nova inscrição (Candidato - Público)
     * Endpoint único preservado: POST /api/v1/applications
     */
    public function store(Request $request): JsonResponse
    {
        $cursoId = (int) $request->input('curso_id');

        $validated = $request->validate(
            array_merge($this->regrasDadosPessoais(), $this->regrasCpfEmailPorCurso($cursoId), [
                // Curso
                'curso_id' => 'required|integer|exists:cursos,id',
                'course_modality_id' => 'required|integer|exists:course_modalities,id',

                // Documentos (opcionais no momento da inscrição)
                'documentos' => 'nullable|array',
                'documentos.*.tipo' => 'required|in:'.implode(',', Application::DOCUMENT_TYPES),
                'documentos.*.arquivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            ]),
            [
                'cpf.unique' => 'Já existe uma inscrição com este CPF para este curso.',
                'data_nascimento.before' => 'Data de nascimento deve ser no passado.',
            ],
        );

        $modalidade = CourseModality::query()->find($validated['course_modality_id']);

        if (! $modalidade || $modalidade->curso_id !== (int) $validated['curso_id']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Dados inválidos',
                    'details' => [
                        'course_modality_id' => [
                            'A modalidade selecionada não pertence ao curso informado.',
                        ],
                    ],
                ],
            ], 422);
        }

        // Gera protocolo único
        $protocol = $this->gerarProtocolo();

        // Cria a inscrição
        $application = Application::create([
            'protocol' => $protocol,
            'nome_completo' => $validated['nome_completo'],
            'email' => $validated['email'],
            'telefone' => $validated['telefone'],
            'whatsapp' => $validated['whatsapp'] ?? $validated['telefone'],
            'cpf' => $validated['cpf'],
            'rg' => $validated['rg'] ?? null,
            'data_nascimento' => $validated['data_nascimento'],
            'sexo' => $validated['sexo'],
            'estado_civil' => $validated['estado_civil'] ?? null,
            'nacionalidade' => $validated['nacionalidade'] ?? 'Brasileira',
            'naturalidade_cidade' => $validated['naturalidade_cidade'] ?? null,
            'naturalidade_estado' => $validated['naturalidade_estado'] ?? null,
            'escolaridade' => $validated['escolaridade'],
            'cep' => $validated['cep'],
            'logradouro' => $validated['logradouro'],
            'numero' => $validated['numero'],
            'complemento' => $validated['complemento'] ?? null,
            'bairro' => $validated['bairro'],
            'cidade' => $validated['cidade'],
            'estado' => $validated['estado'],
            'curso_id' => $validated['curso_id'],
            'course_modality_id' => $validated['course_modality_id'],
            'status' => 'em_analise', // em_analise, aprovado, rejeitado, matriculado, cancelado
        ]);

        // Processa documentos se enviados
        if (! empty($validated['documentos'])) {
            foreach ($validated['documentos'] as $docData) {
                $file = $docData['arquivo'];
                $filename = $this->storeDocument($file, $application->id, $docData['tipo']);

                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'tipo' => $docData['tipo'],
                    'nome_armazenado' => basename($filename),
                    'nome_original' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'tamanho' => $file->getSize(),
                    'caminho_arquivo' => $filename,
                ]);
            }
        }

        // Confirmação institucional por e-mail, somente após a inscrição e os
        // documentos estarem persistidos. Falhas do provedor não desfazem o
        // registro nem alteram a resposta HTTP 201.
        $this->notificarInscricao->enviar(collect([$application]));

        // TODO: Enviar notificação WhatsApp para o candidato
        // TODO: Enviar notificação para Secretaria sobre nova inscrição

        return response()->json([
            'success' => true,
            'message' => 'Inscrição realizada com sucesso! Guarde o protocolo abaixo.',
            'data' => [
                'application' => $application->load(['curso', 'courseModality']),
                'protocol' => $protocol,
            ],
        ], 201);
    }

    /**
     * Cria inscrições em lote (1 a 3 cursos por envio).
     * Endpoint: POST /api/v1/applications/batch
     *
     * Regras:
     * - cada curso gera uma Application independente, com protocolo próprio;
     * - não permite cursos repetidos no mesmo envio;
     * - cada modalidade deve pertencer ao curso correspondente;
     * - tudo em transação: se qualquer item falhar, nada é gravado.
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $validated = $request->validate(
            array_merge($this->regrasDadosPessoais(), [
                'inscricoes' => 'required|array|min:1|max:3',
                'inscricoes.*.curso_id' => 'required|integer|exists:cursos,id|distinct',
                'inscricoes.*.course_modality_id' => 'required|integer|exists:course_modalities,id',
            ]),
            [
                'inscricoes.required' => 'Informe ao menos uma inscrição.',
                'inscricoes.min' => 'Informe ao menos uma inscrição.',
                'inscricoes.max' => 'É possível se inscrever em no máximo 3 cursos por envio.',
                'inscricoes.*.curso_id.distinct' => 'Não é permitido repetir o mesmo curso no mesmo envio.',
                'inscricoes.*.curso_id.exists' => 'Um dos cursos selecionados não foi encontrado.',
                'inscricoes.*.course_modality_id.exists' => 'Uma das modalidades selecionadas não foi encontrada.',
                'data_nascimento.before' => 'Data de nascimento deve ser no passado.',
            ],
        );

        $cpf = preg_replace('/\D/', '', (string) $validated['cpf']);
        /**
         * @var array<string, array<string, array<int, string>>> $errosPorIndice
         */
        $errosPorIndice = [];

        // Valida CPF por curso e modalidade pertencente ao curso, ANTES de gravar
        foreach ($validated['inscricoes'] as $indice => $inscricao) {
            $cursoId = (int) $inscricao['curso_id'];
            $modalidadeId = (int) $inscricao['course_modality_id'];
            $chaveIndice = (string) $indice;

            $jaInscrito = Application::query()
                ->where('cpf', $cpf)
                ->where('curso_id', $cursoId)
                ->exists();

            if ($jaInscrito) {
                $errosPorIndice[$chaveIndice]['curso_id'] = [
                    'Você já possui uma inscrição para este curso.',
                ];

                continue;
            }

            $modalidade = CourseModality::query()->find($modalidadeId);

            if (! $modalidade || (int) $modalidade->curso_id !== $cursoId) {
                $errosPorIndice[$chaveIndice]['course_modality_id'] = [
                    'A modalidade selecionada não pertence ao curso informado.',
                ];
            }
        }

        if ($errosPorIndice !== []) {
            ksort($errosPorIndice, SORT_NUMERIC);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Dados inválidos',
                    'details' => [
                        'inscricoes' => (object) $errosPorIndice,
                    ],
                ],
            ], 422);
        }

        // Criação em transação: se qualquer item falhar, nenhum registro é gravado
        $inscricoesCriadas = DB::transaction(function () use ($validated) {
            $criadas = [];

            foreach ($validated['inscricoes'] as $inscricao) {
                $criadas[] = Application::create([
                    'protocol' => $this->gerarProtocolo(),
                    'nome_completo' => $validated['nome_completo'],
                    'email' => $validated['email'],
                    'telefone' => $validated['telefone'],
                    'whatsapp' => $validated['whatsapp'] ?? $validated['telefone'],
                    'cpf' => $validated['cpf'],
                    'rg' => $validated['rg'] ?? null,
                    'data_nascimento' => $validated['data_nascimento'],
                    'sexo' => $validated['sexo'],
                    'estado_civil' => $validated['estado_civil'] ?? null,
                    'nacionalidade' => $validated['nacionalidade'] ?? 'Brasileira',
                    'naturalidade_cidade' => $validated['naturalidade_cidade'] ?? null,
                    'naturalidade_estado' => $validated['naturalidade_estado'] ?? null,
                    'escolaridade' => $validated['escolaridade'],
                    'cep' => $validated['cep'],
                    'logradouro' => $validated['logradouro'],
                    'numero' => $validated['numero'],
                    'complemento' => $validated['complemento'] ?? null,
                    'bairro' => $validated['bairro'],
                    'cidade' => $validated['cidade'],
                    'estado' => $validated['estado'],
                    'curso_id' => (int) $inscricao['curso_id'],
                    'course_modality_id' => (int) $inscricao['course_modality_id'],
                    'status' => Application::STATUS_EM_ANALISE,
                ]);
            }

            return $criadas;
        });

        // Uma confirmação consolidada com todos os protocolos, somente depois
        // de a transação concluir e nunca dentro do laço.
        $this->notificarInscricao->enviar(collect($inscricoesCriadas));

        // TODO: Enviar notificação para Secretaria sobre novas inscrições

        return response()->json([
            'success' => true,
            'message' => 'Inscrições realizadas com sucesso! Guarde os protocolos abaixo.',
            'data' => [
                'inscricoes' => collect($inscricoesCriadas)
                    ->map(fn (Application $application) => [
                        'protocol' => $application->protocol,
                        'curso' => [
                            'id' => $application->curso->id,
                            'titulo' => $application->curso->titulo,
                        ],
                        'modalidade' => [
                            'id' => $application->courseModality->id,
                            'nome' => $application->courseModality->nome,
                        ],
                        'status' => $application->status,
                    ])
                    ->values(),
            ],
        ], 201);
    }

    /**
     * Acompanhamento público e seguro da inscrição.
     * Endpoint: POST /api/v1/applications/acompanhamento
     *
     * Exige protocolo + e-mail. Não aceita CPF e não revela
     * se o protocolo existe quando os dados não coincidem.
     */
    public function acompanhar(Request $request): JsonResponse
    {
        $request->validate([
            'protocol' => 'required|string|max:40',
            'email' => 'required|email|max:255',
        ], [
            'protocol.required' => 'Informe o protocolo da inscrição.',
            'email.required' => 'Informe o e-mail utilizado na inscrição.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $application = Application::with(['curso', 'courseModality'])
            ->where('protocol', trim((string) $request->input('protocol')))
            ->where('email', mb_strtolower(trim((string) $request->input('email'))))
            ->first();

        if (! $application) {
            // Resposta genérica: não revela se o protocolo existe
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INSCRICAO_NAO_ENCONTRADA',
                    'message' => 'Não foi possível localizar a inscrição. Verifique o protocolo e o e-mail informados.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'protocol' => $application->protocol,
                'curso' => [
                    'id' => $application->curso->id,
                    'titulo' => $application->curso->titulo,
                ],
                'modalidade' => [
                    'id' => $application->courseModality->id,
                    'nome' => $application->courseModality->nome,
                ],
                'status' => $application->status,
                'status_label' => $this->rotuloStatus($application->status),
                'data_inscricao' => $application->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Rótulo amigável do status da inscrição
     */
    private function rotuloStatus(string $status): string
    {
        return [
            Application::STATUS_EM_ANALISE => 'Em análise pela secretaria',
            Application::STATUS_APROVADO => 'Aprovado',
            Application::STATUS_REJEITADO => 'Não aprovado',
            Application::STATUS_MATRICULADO => 'Matriculado',
            Application::STATUS_CANCELADO => 'Cancelado',
        ][$status] ?? $status;
    }

    /**
     * Gera protocolo único de inscrição
     */
    private function gerarProtocolo(): string
    {
        return 'INSC-'.strtoupper(Str::random(8)).'-'.now()->format('Ymd');
    }

    /**
     * Exibe detalhes de uma inscrição (Secretaria/Admin)
     */
    public function show(Application $application): JsonResponse
    {
        $application->load(['curso', 'courseModality', 'documents']);

        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    /**
     * Decide uma inscrição pelo mesmo fluxo usado no painel da Secretaria.
     */
    public function updateStatus(
        Request $request,
        Application $application,
        AprovarInscricaoService $aprovarInscricao,
    ): JsonResponse {
        if (is_string($request->input('observacoes_secretaria'))) {
            $request->merge([
                'observacoes_secretaria' => trim($request->input('observacoes_secretaria')),
            ]);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Application::STATUS_APROVADO,
                Application::STATUS_REJEITADO,
            ])],
            'observacoes_secretaria' => [
                Rule::requiredIf($request->input('status') === Application::STATUS_REJEITADO),
                'nullable',
                'string',
                'min:3',
                'max:1000',
            ],
        ], [
            'status.in' => 'A decisão deve ser aprovado ou rejeitado.',
            'observacoes_secretaria.required' => 'A justificativa da rejeição é obrigatória.',
            'observacoes_secretaria.min' => 'A justificativa da rejeição deve ter ao menos 3 caracteres.',
        ]);

        /** @var User $operador */
        $operador = $request->user();
        $observacoes = isset($validated['observacoes_secretaria'])
            ? trim((string) $validated['observacoes_secretaria'])
            : null;

        try {
            if ($validated['status'] === Application::STATUS_APROVADO) {
                $resultado = $aprovarInscricao->executar($application, $operador, $observacoes);
                $application = $resultado->application;
                $mensagem = 'Inscrição aprovada e matrícula gerada com sucesso.';
            } else {
                $application = $aprovarInscricao->rejeitar($application, $operador, (string) $observacoes);
                $mensagem = 'Inscrição rejeitada com sucesso.';
            }
        } catch (AprovarInscricaoException $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $exception->codigoDominio,
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $mensagem,
            'data' => $application->load(['curso', 'courseModality', 'documents', 'matricula']),
        ]);
    }

    /**
     * Upload de documento complementar (Candidato com auth)
     */
    public function uploadDocument(Request $request, Application $application): JsonResponse
    {
        // Verifica se o candidato é dono da inscrição
        if ($application->email !== auth()->user()->email) {
            throw ValidationException::withMessages([
                'application' => 'Você não tem permissão para esta inscrição.',
            ]);
        }

        // Só permite upload se estiver pendente ou em análise
        if ($application->status !== Application::STATUS_EM_ANALISE) {
            throw ValidationException::withMessages([
                'application' => 'Não é possível enviar documentos para inscrições finalizadas.',
            ]);
        }

        $validated = $request->validate([
            'tipo' => 'required|in:'.implode(',', Application::DOCUMENT_TYPES),
            'arquivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $file = $validated['arquivo'];
        $filename = $this->storeDocument($file, $application->id, $validated['tipo']);

        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'tipo' => $validated['tipo'],
            'nome_armazenado' => basename($filename),
            'nome_original' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'tamanho' => $file->getSize(),
            'caminho_arquivo' => $filename,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Documento enviado com sucesso.',
            'data' => $document,
        ], 201);
    }

    /**
     * Remove uma inscrição (Soft delete - Secretaria/Admin)
     */
    public function destroy(Application $application): JsonResponse
    {
        $application->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inscrição removida com sucesso.',
        ]);
    }

    /**
     * Armazena documento no disco PRIVADO configurado (Issue #50).
     *
     * - Não há fallback silencioso para "public".
     * - O disco é resolvido por config('filesystems.applications_disk'),
     *   declarado em config/filesystems.php.
     * - Se a configuração estiver ausente ou apontar para "public", o upload
     *   falha com 500 registrado, em vez de gravar PII em disco público.
     */
    private function storeDocument($file, string $applicationId, string $type): string
    {
        $disk = (string) config('filesystems.applications_disk');

        // Segurança em profundidade: nunca degrada para o disco público.
        if ($disk === '' || $disk === 'public') {
            report(new \RuntimeException('Disco de documentos de inscrição não configurado corretamente.'));
            abort(500);
        }

        $folder = "applications/{$applicationId}";
        $extension = $file->getClientOriginalExtension();
        $filename = "{$type}_".Str::uuid().".{$extension}";

        return $file->storeAs($folder, $filename, $disk);
    }
}
