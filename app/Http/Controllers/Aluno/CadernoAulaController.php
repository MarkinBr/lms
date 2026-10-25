<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\Matricula;
use App\Models\Progresso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CadernoAulaController extends Controller
{
    public function salvar(Request $request, int $id): JsonResponse
    {
        $aula = Aula::with('modulo')->findOrFail($id);
        abort_unless($aula->modulo, 404);
        abort_unless(Matricula::where('user_id', $request->user()->id)
            ->where('curso_id', $aula->modulo->curso_id)
            ->visiveisNoPortal()->exists(), 403);
        abort_unless($aula->liberada, 403, 'Esta aula ainda não foi liberada.');
        abort_unless(Schema::hasTable('cadernos_aula'), 503, 'O caderno está em atualização. Exporte seu rascunho antes de sair.');

        $dados = $request->validate([
            'revision' => ['required', 'integer', 'min:0'],
            'baseNotes' => ['present', 'nullable', 'string', 'max:10000'],
            'notebook' => ['required', 'array:version,text,highlights,anchors'],
            'notebook.version' => ['required', 'integer', 'in:1'],
            'notebook.text' => ['present', 'nullable', 'string', 'max:10000'],
            'notebook.highlights' => ['present', 'array', 'max:150'],
            'notebook.highlights.*' => ['array:id,source,text,color,url,page,rects,start,end'],
            'notebook.highlights.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'notebook.highlights.*.source' => ['required', 'in:pdf,text'],
            'notebook.highlights.*.text' => ['required', 'string', 'max:1400'],
            'notebook.highlights.*.color' => ['required', 'in:yellow,green,pink'],
            'notebook.highlights.*.url' => ['nullable', 'string', 'max:2048', 'regex:~^https?://~i'],
            'notebook.highlights.*.page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notebook.highlights.*.start' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'notebook.highlights.*.end' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'notebook.highlights.*.rects' => ['nullable', 'array', 'max:100'],
            'notebook.highlights.*.rects.*' => ['array', 'size:4'],
            'notebook.highlights.*.rects.*.*' => ['numeric', 'between:0,1'],
            'notebook.anchors' => ['present', 'array', 'max:150'],
            'notebook.anchors.*' => ['array:id,kind,label,url,page,time'],
            'notebook.anchors.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'notebook.anchors.*.kind' => ['required', 'in:pdf,video'],
            'notebook.anchors.*.label' => ['required', 'string', 'max:200'],
            'notebook.anchors.*.url' => ['nullable', 'string', 'max:2048', 'regex:~^https?://~i'],
            'notebook.anchors.*.page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notebook.anchors.*.time' => ['nullable', 'numeric', 'between:0,604800'],
        ]);
        $caderno = $dados['notebook'];
        $caderno['text'] = $caderno['text'] ?? '';
        abort_if(strlen(json_encode($caderno)) > 500000, 422, 'Seu caderno ultrapassou o limite de armazenamento. Exporte uma cópia.');

        return DB::transaction(function () use ($request, $id, $dados, $caderno) {
            $chave = ['aluno_id' => $request->user()->id, 'aula_id' => $id];
            // Inserção e atualização condicionais evitam perder edições entre abas/dispositivos.
            DB::table('cadernos_aula')->insertOrIgnore($chave + [
                'revisao' => 0,
                'conteudo' => json_encode(['version' => 1, 'text' => '', 'highlights' => [], 'anchors' => []]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            Progresso::firstOrCreate(['user_id' => $request->user()->id, 'aula_id' => $id]);
            $progresso = Progresso::where('user_id', $request->user()->id)->where('aula_id', $id)->lockForUpdate()->firstOrFail();
            $notasAtuais = $progresso->notas ?? '';
            if ($notasAtuais !== ($dados['baseNotes'] ?? '')) {
                return response()->json(['success' => false, 'message' => 'O caderno mudou em outra janela. Exporte seu rascunho e recarregue para comparar.'], 409);
            }
            $alterado = DB::table('cadernos_aula')->where($chave)->where('revisao', $dados['revision'])->update([
                'conteudo' => json_encode($caderno), 'revisao' => $dados['revision'] + 1, 'updated_at' => now(),
            ]);
            if (! $alterado) {
                return response()->json(['success' => false, 'message' => 'Há uma versão mais recente na sua conta. Exporte seu rascunho e recarregue para comparar.'], 409);
            }
            // Notas antigas continuam texto simples; grifos não contaminam o campo legado.
            $progresso->update(['notas' => $caderno['text']]);

            return response()->json(['success' => true, 'revision' => $dados['revision'] + 1]);
        });
    }
}
