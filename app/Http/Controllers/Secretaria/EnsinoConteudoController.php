<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Modulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EnsinoConteudoController extends Controller
{
    public function salvarModulo(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'curso_id' => ['required', 'integer', 'exists:cursos,id'],
            'titulo' => ['required', 'string', 'max:200'],
            'descricao' => ['nullable', 'string', 'max:3000'],
            'ordem' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);
        DB::transaction(function () use ($dados) {
            Curso::query()->findOrFail($dados['curso_id']);
            Modulo::query()->create($dados);
        });

        return redirect()->route('secretaria.ensino.modulos')->with('status', 'Módulo registrado na organização do curso.');
    }

    public function salvarAula(Request $request, ?Aula $aula = null): RedirectResponse
    {
        $dados = $request->validate([
            'disciplina_id' => ['required', 'integer', 'exists:disciplinas,id'],
            'modulo_id' => ['nullable', 'integer', 'exists:modulos,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'conteudo_texto' => ['nullable', 'string', 'max:60000'],
            'video_url' => ['nullable', 'url:http,https', 'max:1200'],
            'transcricao_video' => ['nullable', 'string', 'max:60000'],
            'ordem' => ['required', 'integer', 'min:1', 'max:9999'],
            'duracao' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'liberada' => ['nullable', 'boolean'],
            'permite_download' => ['nullable', 'boolean'],
            'permite_comentarios' => ['nullable', 'boolean'],
            'materiais' => ['nullable', 'array', 'max:8'],
            'materiais.*' => ['file', 'max:15360', 'mimes:pdf,txt,doc,docx,ppt,pptx,odt,odp,mp3,mp4,jpg,jpeg,png'],
        ]);
        $disciplina = Disciplina::query()->with('modulo')->findOrFail($dados['disciplina_id']);
        $moduloId = $dados['modulo_id'] ?? $aula?->modulo_id ?? $disciplina->modulo_id;
        $modulo = Modulo::query()->find($moduloId);
        if (! $modulo || (int) $modulo->curso_id !== (int) $disciplina->curso_id ||
            ((int) $modulo->id !== (int) $disciplina->modulo_id && ! $disciplina->modulos()->whereKey($modulo->id)->exists())) {
            throw ValidationException::withMessages(['modulo_id' => 'Escolha um módulo vinculado à disciplina e ao mesmo curso.']);
        }
        if ($aula?->exists && (int) $aula->disciplina_id !== (int) $disciplina->id) {
            throw ValidationException::withMessages(['disciplina_id' => 'Não é possível mover uma aula existente para outra disciplina nesta tela.']);
        }
        if (! filled($dados['conteudo_texto'] ?? null) &&
            ! filled($dados['video_url'] ?? null) &&
            ! $request->hasFile('materiais') &&
            ! ($aula?->anexos)) {
            throw ValidationException::withMessages(['conteudo_texto' => 'Informe texto, link de vídeo ou um material antes de salvar.']);
        }

        $novosArquivos = [];
        try {
            $aula = DB::transaction(function () use ($dados, $request, $disciplina, $modulo, $aula, &$novosArquivos) {
                $aula = $aula?->exists ? Aula::query()->lockForUpdate()->findOrFail($aula->id) : new Aula;
                $aula->fill([
                    'disciplina_id' => $disciplina->id,
                    'modulo_id' => $modulo->id,
                    'titulo' => $dados['titulo'],
                    'descricao' => $dados['descricao'] ?? null,
                    'conteudo_texto' => $dados['conteudo_texto'] ?? null,
                    'video_url' => $dados['video_url'] ?? null,
                    'transcricao_video' => $dados['transcricao_video'] ?? null,
                    'tipo_conteudo' => filled($dados['video_url'] ?? null) && filled($dados['conteudo_texto'] ?? null)
                        ? 'misto' : (filled($dados['video_url'] ?? null) ? 'video' : 'texto'),
                    'ordem' => $dados['ordem'],
                    'duracao' => $dados['duracao'] ?? 0,
                    'liberada' => $request->boolean('liberada'),
                    'permite_download' => $request->boolean('permite_download'),
                    'permite_comentarios' => $request->boolean('permite_comentarios'),
                ]);
                $aula->save();

                $anexos = $aula->anexos ?? [];
                foreach ($request->file('materiais', []) as $arquivo) {
                    $nome = Str::uuid().'.'.$arquivo->extension();
                    $caminho = $arquivo->storeAs('ensino/aulas/'.$aula->id, $nome, 'local');
                    if (! $caminho) {
                        throw ValidationException::withMessages(['materiais' => 'Não foi possível armazenar o material.']);
                    }
                    $novosArquivos[] = $caminho;
                    $anexos[] = [
                        'nome' => mb_substr($arquivo->getClientOriginalName(), 0, 180),
                        'caminho' => $caminho,
                        'mime' => $arquivo->getMimeType(),
                        'tamanho' => $arquivo->getSize(),
                    ];
                }
                if (count($anexos) > 40) {
                    throw ValidationException::withMessages(['materiais' => 'Máximo de 40 arquivos por aula.']);
                }
                $aula->anexos = $anexos;
                $aula->save();

                return $aula;
            });
        } catch (\Throwable $erro) {
            foreach ($novosArquivos as $caminho) {
                Storage::disk('local')->delete($caminho);
            }
            throw $erro;
        }

        return redirect()->route('secretaria.ensino.aula', $aula)
            ->with('status', 'Aula e materiais salvos. Anexos serão visíveis na sala aos alunos matriculados se a aula estiver liberada e downloads permitidos.');
    }

    public function material(Aula $aula, int $indice): BinaryFileResponse
    {
        $arquivos = $aula->anexos ?? [];
        $arquivo = $arquivos[$indice] ?? null;
        $caminho = $arquivo['caminho'] ?? null;
        if (! is_string($caminho) || ! str_starts_with($caminho, 'ensino/aulas/'.$aula->id.'/')) {
            abort(404);
        }
        $disco = Storage::disk('local');
        abort_unless($disco->exists($caminho), 404);

        return response()->download($disco->path($caminho), basename($arquivo['nome'] ?? 'material'), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
