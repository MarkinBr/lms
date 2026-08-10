<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Curso;
use App\Models\CourseModality;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ApplicationController - API de Inscrições
 * 
 * Controlador para inscrições públicas e gestão pela secretaria
 */
class ApplicationController extends Controller
{
    /**
     * Listar cursos disponíveis para inscrição
     * 
     * @return JsonResponse
     */
    public function getCursos(): JsonResponse
    {
        $cursos = Curso::where('ativo', true)
            ->with(['modalities' => function ($query) {
                $query->where('ativo', true);
            }])
            ->orderBy('nome')
            ->get()
            ->map(function ($curso) {
                return [
                    'id' => $curso->id,
                    'nome' => $curso->nome,
                    'descricao' => $curso->descricao,
                    'duracao' => $curso->duracao,
                    'modalidades' => $curso->modalities->map(fn ($m) => [
                        'id' => $m->id,
                        'nome' => $m->nome,
                    ]),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $cursos,
        ]);
    }

    /**
     * Submeter nova inscrição (público)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Dados pessoais
            'nome_completo' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:applications,cpf',
            'rg' => 'nullable|string|max:20',
            'data_nascimento' => 'required|date|before:today',
            'sexo' => 'required|in:M,F,Outro',
            'estado_civil' => 'nullable|in:solteiro,casado,divorciado,viuvo,uniao_estavel',
            'nacionalidade' => 'string|max:100',
            'naturalidade_cidade' => 'nullable|string|max:100',
            'naturalidade_estado' => 'nullable|string|max:2',
            
            // Contato
            'email' => 'required|email|max:255|unique:applications,email',
            'telefone' => 'required|string|max:15',
            'whatsapp' => 'nullable|string|max:15',
            
            // Endereço
            'cep' => 'required|string|max:9',
            'logradouro' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|max:2',
            
            // Dados acadêmicos
            'escolaridade' => 'required|in:fundamental_incompleto,fundamental_completo,medio_incompleto,medio_completo,superior_incompleto,superior_completo,pos_graduacao,mestrado,doutorado',
            'curso_id' => 'required|uuid|exists:cursos,id',
            'course_modality_id' => 'required|uuid|exists:course_modalities,id',
            
            // Documentos (opcional no momento da inscrição, pode enviar depois)
            'documentos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Dados inválidos',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        try {
            // Criar inscrição
            $application = Application::create([
                'nome_completo' => $request->nome_completo,
                'cpf' => preg_replace('/\D/', '', $request->cpf), // Apenas números
                'rg' => $request->rg,
                'data_nascimento' => $request->data_nascimento,
                'sexo' => $request->sexo,
                'estado_civil' => $request->estado_civil,
                'nacionalidade' => $request->nacionalidade ?? 'Brasileira',
                'naturalidade_cidade' => $request->naturalidade_cidade,
                'naturalidade_estado' => $request->naturalidade_estado,
                'email' => strtolower($request->email),
                'telefone' => $request->telefone,
                'whatsapp' => $request->whatsapp,
                'cep' => $request->cep,
                'logradouro' => $request->logradouro,
                'numero' => $request->numero,
                'complemento' => $request->complemento,
                'bairro' => $request->bairro,
                'cidade' => $request->cidade,
                'estado' => $request->estado,
                'escolaridade' => $request->escolaridade,
                'curso_id' => $request->curso_id,
                'course_modality_id' => $request->course_modality_id,
                'status' => Application::STATUS_EM_ANALISE,
            ]);

            // Processar upload de documentos se existirem
            if ($request->hasFile('documentos')) {
                foreach ($request->file('documentos') as $tipo => $file) {
                    if (in_array($tipo, Application::DOCUMENT_TYPES)) {
                        $document = $this->uploadDocument($application, $file, $tipo);
                        if ($document) {
                            $application->documents()->save($document);
                        }
                    }
                }
            }

            // TODO: Enviar e-mail de confirmação para o candidato
            // TODO: Enviar notificação para a secretaria sobre nova inscrição

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $application->id,
                    'numero_protocolo' => Str::upper(Str::substr($application->id, 0, 8)),
                    'mensagem' => 'Inscrição realizada com sucesso! Você receberá um e-mail de confirmação.',
                    'status' => $application->status,
                    'proximos_passos' => 'A secretaria analisará sua inscrição e entrará em contato.',
                ],
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Erro ao criar inscrição', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Erro ao processar inscrição. Tente novamente.',
                ],
            ], 500);
        }
    }

    /**
     * Obter detalhes de uma inscrição (por ID ou e-mail)
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $application = Application::with(['curso', 'courseModality', 'documents'])
            ->findOrFail($id);

        // Verificar permissão (apenas o próprio candidato ou secretaria/admin)
        // Implementar middleware de autorização aqui

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $application->id,
                'nome_completo' => $application->nome_completo,
                'cpf_formatado' => $application->cpf_formatado,
                'email' => $application->email,
                'curso' => $application->curso->nome ?? null,
                'modalidade' => $application->courseModality->nome ?? null,
                'status' => $application->status,
                'status_label' => [
                    Application::STATUS_EM_ANALISE => 'Em Análise',
                    Application::STATUS_APROVADO => 'Aprovado',
                    Application::STATUS_REJEITADO => 'Rejeitado',
                    Application::STATUS_MATRICULADO => 'Matriculado',
                    Application::STATUS_CANCELADO => 'Cancelado',
                ][$application->status],
                'created_at' => $application->created_at->format('d/m/Y H:i'),
                'analisado_em' => $application->analisado_em?->format('d/m/Y H:i'),
                'observacoes_secretaria' => $application->observacoes_secretaria,
                'documentos' => $application->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'tipo' => $doc->tipo_label,
                    'validado' => $doc->validado,
                    'observacoes' => $doc->observacoes_validacao,
                ]),
            ],
        ]);
    }

    /**
     * Atualizar inscrição (apenas quando em análise)
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $application = Application::findOrFail($id);

        // Verificar se pode editar (somente em análise)
        if (!$application->canBeEditedByCandidate()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_EDITABLE',
                    'message' => 'Esta inscrição não pode mais ser editada.',
                ],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nome_completo' => 'sometimes|required|string|max:255',
            'cpf' => 'sometimes|required|string|size:11|unique:applications,cpf,' . $id . ',id',
            'rg' => 'nullable|string|max:20',
            'data_nascimento' => 'sometimes|required|date|before:today',
            'sexo' => 'sometimes|required|in:M,F,Outro',
            'estado_civil' => 'nullable|in:solteiro,casado,divorciado,viuvo,uniao_estavel',
            'email' => 'sometimes|required|email|max:255|unique:applications,email,' . $id . ',id',
            'telefone' => 'sometimes|required|string|max:15',
            'whatsapp' => 'nullable|string|max:15',
            'cep' => 'sometimes|required|string|max:9',
            'logradouro' => 'sometimes|required|string|max:255',
            'numero' => 'sometimes|required|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'sometimes|required|string|max:100',
            'cidade' => 'sometimes|required|string|max:100',
            'estado' => 'sometimes|required|string|max:2',
            'escolaridade' => 'sometimes|required|in:fundamental_incompleto,fundamental_completo,medio_incompleto,medio_completo,superior_incompleto,superior_completo,pos_graduacao,mestrado,doutorado',
            'curso_id' => 'sometimes|required|uuid|exists:cursos,id',
            'course_modality_id' => 'sometimes|required|uuid|exists:course_modalities,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Dados inválidos',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        try {
            $application->update($request->only([
                'nome_completo', 'cpf', 'rg', 'data_nascimento', 'sexo',
                'estado_civil', 'nacionalidade', 'naturalidade_cidade', 'naturalidade_estado',
                'email', 'telefone', 'whatsapp', 'cep', 'logradouro', 'numero',
                'complemento', 'bairro', 'cidade', 'estado', 'escolaridade',
                'curso_id', 'course_modality_id',
            ]));

            return response()->json([
                'success' => true,
                'data' => [
                    'mensagem' => 'Inscrição atualizada com sucesso!',
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar inscrição', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Erro ao atualizar inscrição.',
                ],
            ], 500);
        }
    }

    /**
     * Upload de documento adicional
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function uploadDocument(Request $request, string $id): JsonResponse
    {
        $application = Application::findOrFail($id);

        // Validar arquivo
        $validator = Validator::make($request->all(), [
            'documento' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
            'tipo' => 'required|in:' . implode(',', Application::DOCUMENT_TYPES),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Arquivo inválido',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        try {
            $file = $request->file('documento');
            $documento = $this->uploadDocument($application, $file, $request->tipo);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $documento->id,
                    'tipo' => $documento->tipo_label,
                    'nome' => $documento->nome_original,
                    'tamanho' => $documento->tamanho_formatado,
                    'mensagem' => 'Documento enviado com sucesso!',
                ],
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Erro ao upload documento', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPLOAD_ERROR',
                    'message' => 'Erro ao enviar documento.',
                ],
            ], 500);
        }
    }

    /**
     * Helper: Upload de documento
     * 
     * @param Application $application
     * @param $file
     * @param string $tipo
     * @return ApplicationDocument|null
     */
    private function uploadDocument(Application $application, $file, string $tipo): ?ApplicationDocument
    {
        // Gerar nome seguro
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::uuid() . '.' . $extension;
        
        // Caminho no storage
        $path = $file->storeAs(
            'applications/' . $application->id . '/documentos',
            $safeName,
            ['disk' => config('filesystems.default')] // Local ou S3/R2
        );

        // Criar registro do documento
        return new ApplicationDocument([
            'application_id' => $application->id,
            'tipo' => $tipo,
            'nome_original' => $file->getClientOriginalName(),
            'nome_armazenado' => $safeName,
            'mime_type' => $file->getMimeType(),
            'tamanho' => $file->getSize(),
            'caminho_arquivo' => $path,
            'validado' => false,
        ]);
    }
}
