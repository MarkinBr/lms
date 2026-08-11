<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\InscricaoConfirmacaoMail;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CourseModality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApplicationController extends Controller
{
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
        $query = Application::with(['curso', 'documentos'])
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

        $this->enviarConfirmacaoPorEmail($application->email, collect([$application]));

        // TODO: Enviar notificação WhatsApp para o candidato
        // TODO: Enviar notificação para Secretaria sobre nova inscrição

        return response()->json([
            'success' => true,
            'message' => 'Inscrição realizada com sucesso! Você receberá um e-mail de confirmação.',
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
        $detalhes = [];

        // Valida CPF por curso e modalidade pertencente ao curso, ANTES de gravar
        foreach ($validated['inscricoes'] as $indice => $inscricao) {
            $cursoId = (int) $inscricao['curso_id'];
            $modalidadeId = (int) $inscricao['course_modality_id'];

            $jaInscrito = Application::query()
                ->where('cpf', $cpf)
                ->where('curso_id', $cursoId)
                ->exists();

            if ($jaInscrito) {
                $detalhes["inscricoes.{$indice}.curso_id"] = [
                    'Você já possui uma inscrição para este curso.',
                ];
                continue;
            }

            $modalidade = CourseModality::query()->find($modalidadeId);

            if (! $modalidade || (int) $modalidade->curso_id !== $cursoId) {
                $detalhes["inscricoes.{$indice}.course_modality_id"] = [
                    'A modalidade selecionada não pertence ao curso informado.',
                ];
            }
        }

        if ($detalhes !== []) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Dados inválidos',
                    'details' => $detalhes,
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

        $this->enviarConfirmacaoPorEmail($validated['email'], collect($inscricoesCriadas));

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
     * Envia os protocolos após a inscrição já estar confirmada no banco.
     * Uma indisponibilidade temporária do provedor de e-mail não pode apagar
     * nem invalidar uma inscrição que foi gravada com sucesso.
     *
     * @param \Illuminate\Support\Collection<int, Application> $inscricoes
     */
    private function enviarConfirmacaoPorEmail(string $email, \Illuminate\Support\Collection $inscricoes): void
    {
        try {
            $inscricoes->each(
                fn (Application $inscricao) => $inscricao->load(['curso', 'courseModality']),
            );

            Mail::to($email)->send(new InscricaoConfirmacaoMail($inscricoes));
        } catch (Throwable $exception) {
            Log::warning('Não foi possível enviar a confirmação de inscrição por e-mail.', [
                'email' => $email,
                'protocolos' => $inscricoes->pluck('protocol')->all(),
                'erro' => $exception->getMessage(),
            ]);
        }
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
     * Atualiza status da inscrição (Secretaria/Admin)
     */
    public function updateStatus(Request $request, Application $application): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:em_analise,aprovado,rejeitado,matriculado,cancelado',
            'observacoes_secretaria' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $application->status;
        $application->update([
            'status' => $validated['status'],
            'observacoes_secretaria' => $validated['observacoes_secretaria'] ?? null,
            'analisado_em' => now(),
            'analisado_por_id' => auth()->id(),
        ]);

        // Log de auditoria
        activity()
            ->performedBy(auth()->user())
            ->on($application)
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'observacoes' => $validated['observacoes_secretaria'] ?? null,
            ])
            ->event('status_changed')
            ->log('Status da inscrição alterado');

        // TODO: Enviar e-mail para o candidato sobre aprovação/rejeição
        // TODO: Enviar notificação WhatsApp

        return response()->json([
            'success' => true,
            'message' => "Inscrição {$validated['status']} com sucesso.",
            'data' => $application->load(['curso', 'documentos']),
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
     * Armazena documento no storage
     */
    private function storeDocument($file, string $applicationId, string $type): string
    {
        $folder = "applications/{$applicationId}";
        $extension = $file->getClientOriginalExtension();
        $filename = "{$type}_".Str::uuid().".{$extension}";

        // Usa S3/R2 em produção, local em desenvolvimento
        $disk = config('filesystems.cloud_disk', 'public');

        $path = $file->storeAs($folder, $filename, $disk);

        return $path;
    }
}
