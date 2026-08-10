<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
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
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome_completo' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:applications,email',
            'telefone' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'cpf' => 'required|string|max:14|unique:applications,cpf',
            'rg' => 'nullable|string|max:20',
            'data_nascimento' => 'required|date|before:today',
            'sexo' => 'required|in:M,F,Outro',
            'estado_civil' => 'nullable|string|max:50',
            'nacionalidade' => 'nullable|string|max:100|default:Brasileira',
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
            
            // Curso
            'curso_id' => 'required|exists:cursos,id',
            'course_modality_id' => 'nullable|uuid|exists:course_modalities,id',
            
            // Documentos (opcionais no momento da inscrição)
            'documentos' => 'nullable|array',
            'documentos.*.type' => 'required|in:photo,id_document,cpf,residence_certificate,school_history,other',
            'documentos.*.file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB
        ], [
            'cpf.unique' => 'Já existe uma inscrição com este CPF.',
            'email.unique' => 'Este e-mail já está em uso.',
            'data_nascimento.before' => 'Data de nascimento deve ser no passado.',
        ]);

        // Gera protocolo único
        $protocol = 'INSC-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');

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
        if (!empty($validated['documents'])) {
            foreach ($validated['documents'] as $docData) {
                $file = $docData['file'];
                $filename = $this->storeDocument($file, $application->id, $docData['type']);
                
                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'type' => $docData['type'],
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        // TODO: Enviar e-mail de confirmação para o candidato
        // TODO: Enviar notificação WhatsApp para o candidato
        // TODO: Enviar notificação para Secretaria sobre nova inscrição

        return response()->json([
            'success' => true,
            'message' => 'Inscrição realizada com sucesso! Você receberá um e-mail de confirmação.',
            'data' => [
                'application' => $application->load('course'),
                'protocol' => $protocol,
            ],
        ], 201);
    }

    /**
     * Exibe detalhes de uma inscrição (Secretaria/Admin)
     */
    public function show(Application $application): JsonResponse
    {
        $application->load(['course', 'documents']);

        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    /**
     * Consulta inscrição por protocolo (Candidato)
     */
    public function showByProtocol(string $protocol): JsonResponse
    {
        $application = Application::with(['course', 'documents'])
            ->where('protocol', $protocol)
            ->firstOrFail();

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
        if (!in_array($application->status, ['pending', 'under_review'])) {
            throw ValidationException::withMessages([
                'application' => 'Não é possível enviar documentos para inscrições finalizadas.',
            ]);
        }

        $validated = $request->validate([
            'type' => 'required|in:photo,id_document,cpf,residence_certificate,school_history,other',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $file = $validated['file'];
        $filename = $this->storeDocument($file, $application->id, $validated['type']);

        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'type' => $validated['type'],
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Documento enviado com sucesso.',
            'data' => $document,
        ]);
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
    private function storeDocument($file, int $applicationId, string $type): string
    {
        $folder = "applications/{$applicationId}";
        $extension = $file->getClientOriginalExtension();
        $filename = "{$type}_" . Str::uuid() . ".{$extension}";

        // Usa S3/R2 em produção, local em desenvolvimento
        $disk = config('filesystems.cloud_disk', 'public');
        
        $path = $file->storeAs($folder, $filename, $disk);

        return $path;
    }
}
