<?php

declare(strict_types=1);

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Policies\ApplicationDocumentPolicy;
use App\Support\ApplicationDocuments\DocumentosInscricaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Central de Documentos da Inscrição (Issue #53).
 *
 * Fica no grupo `auth` do portal do aluno (`/portal`) — a mesma sessão
 * que autentica o painel Filament em `/aluno`. Reutiliza a Policy
 * {@see ApplicationDocumentPolicy} para downloads e
 * o disco privado `applications` configurado em `config/filesystems.php`.
 *
 * NÃO substitui `DocumentosController` (declarações/históricos emitidos
 * pela instituição) — vive lado a lado.
 */
class DocumentosInscricaoController extends Controller
{
    public function __construct(
        private readonly DocumentosInscricaoService $service,
    ) {}

    /**
     * Página exclusiva "Documentos da inscrição".
     */
    public function index(Request $request): View
    {
        $aluno = $this->alunoAutenticado($request);

        $resumo = $this->service->resumoDo($aluno);

        return view('aluno.documentos-inscricao.index', [
            'resumo' => $resumo,
            'aluno' => $aluno,
        ]);
    }

    /**
     * Recebe o upload por POST tradicional (sem novas libs JS).
     * A validação de MIME real, extensão, tamanho e nome fica no service.
     */
    public function store(Request $request): RedirectResponse
    {
        $aluno = $this->alunoAutenticado($request);

        // Validação leve na entrada (mensagens em pt-BR). A validação
        // real de MIME/extensão/tamanho/vínculo fica no service.
        $request->validate([
            'tipo' => 'required|string',
            'arquivo' => 'required|file',
        ], [
            'tipo.required' => 'Informe o tipo de documento.',
            'arquivo.required' => 'Selecione um arquivo antes de enviar.',
            'arquivo.file' => 'Envie um arquivo válido.',
        ]);

        $this->service->receberUpload(
            $aluno,
            (string) $request->input('tipo'),
            $request->file('arquivo'),
        );

        return redirect()
            ->route('aluno.documentos-inscricao')
            ->with('sucesso_documento', 'Documento enviado. A secretaria será notificada.');
    }

    /**
     * Traduz `auth()->user()` para {@see User}, aplicando as duas guardas
     * exigidas pela Issue #53:
     *   - visitante => 403 (o middleware `auth` já cuida do redirecionamento,
     *     mas mantemos o abort explícito para casos de sessão inválida);
     *   - inativo => 403.
     */
    private function alunoAutenticado(Request $request): User
    {
        $aluno = Auth::user();

        if (! $aluno instanceof User) {
            abort(403);
        }

        if (! $aluno->ativo) {
            abort(403);
        }

        return $aluno;
    }
}
