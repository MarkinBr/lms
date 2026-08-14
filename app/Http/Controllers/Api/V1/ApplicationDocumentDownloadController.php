<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Download/visualização protegida de documentos de inscrição (Issue #50).
 * Erros são 403/404 genéricos, sem metadados, caminho físico ou PII.
 */
class ApplicationDocumentDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        Application $application,
        ApplicationDocument $document,
    ): StreamedResponse {
        // Vínculo estrito: o documento precisa pertencer à inscrição da URL;
        // caso contrário, 404 — sem revelar a existência do outro documento.
        if ((string) $document->application_id !== (string) $application->id) {
            abort(404);
        }

        $user = $request->user();

        if ($user === null || ! $user->ativo) {
            abort(403);
        }

        if (! $user->can('download', $document)) {
            abort(403);
        }

        $disk = Storage::disk(config('filesystems.applications_disk'));
        $caminho = (string) $document->caminho_arquivo;

        // Anti path traversal: aceita somente o padrão gravado pelo upload
        // ("applications/<uuid-application>/<arquivo>") e rejeita "..",
        // separador Windows e byte nulo.
        $prefixoEsperado = 'applications/'.$application->id.'/';

        if ($caminho === ''
            || Str::contains($caminho, ['..', '\\', "\0"])
            || ! Str::startsWith($caminho, $prefixoEsperado)
            || ! $disk->exists($caminho)
        ) {
            abort(404);
        }

        return $disk->download(
            $caminho,
            $document->nome_original,
            [
                'Content-Type' => $document->mime_type,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
