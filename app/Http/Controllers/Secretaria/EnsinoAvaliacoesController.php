<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\BancoQuestao;
use App\Models\Disciplina;
use App\Models\InstrumentoAvaliacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EnsinoAvaliacoesController extends Controller
{
    public function salvarQuestao(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'disciplina_id' => ['required', 'integer', 'exists:disciplinas,id'],
            'aula_id' => ['nullable', 'integer', 'exists:aulas,id'],
            'tipo' => ['required', Rule::in(['unica', 'multipla', 'verdadeiro_falso', 'dissertativa'])],
            'dificuldade' => ['required', Rule::in(['facil', 'medio', 'dificil'])],
            'enunciado' => ['required', 'string', 'min:12', 'max:4000'],
            'alternativas' => ['nullable', 'array'],
            'alternativas.A' => ['nullable', 'string', 'max:1000'],
            'alternativas.B' => ['nullable', 'string', 'max:1000'],
            'alternativas.C' => ['nullable', 'string', 'max:1000'],
            'alternativas.D' => ['nullable', 'string', 'max:1000'],
            'gabarito' => ['nullable', 'array', 'max:4'],
            'gabarito.*' => ['string', Rule::in(['A', 'B', 'C', 'D', 'V', 'F'])],
            'explicacao' => ['nullable', 'string', 'max:4000'],
            'referencia' => ['nullable', 'string', 'max:1000'],
            'revisada' => ['nullable', 'boolean'],
        ]);

        $disciplina = Disciplina::query()->findOrFail($dados['disciplina_id']);
        if (! empty($dados['aula_id'])) {
            $aula = Aula::query()->findOrFail($dados['aula_id']);
            if ((int) $aula->disciplina_id !== (int) $disciplina->id) {
                throw ValidationException::withMessages(['aula_id' => 'A aula não pertence à disciplina selecionada.']);
            }
        }

        $tipo = $dados['tipo'];
        $alternativas = [];
        $gabarito = array_values(array_unique($dados['gabarito'] ?? []));
        if (in_array($tipo, ['unica', 'multipla'], true)) {
            foreach (['A', 'B', 'C', 'D'] as $letra) {
                $texto = trim((string) ($dados['alternativas'][$letra] ?? ''));
                if ($texto === '') {
                    throw ValidationException::withMessages(['alternativas' => 'Preencha as quatro alternativas.']);
                }
                $alternativas[$letra] = $texto;
            }
            if (count(array_unique(array_map('mb_strtolower', $alternativas))) !== 4) {
                throw ValidationException::withMessages(['alternativas' => 'As alternativas precisam ser diferentes.']);
            }
            if (($tipo === 'unica' && count($gabarito) !== 1)
                || ($tipo === 'multipla' && (count($gabarito) < 2 || count($gabarito) > 3))
                || array_diff($gabarito, array_keys($alternativas))) {
                throw ValidationException::withMessages(['gabarito' => 'Selecione um gabarito válido para o tipo de questão.']);
            }
        } elseif ($tipo === 'verdadeiro_falso') {
            if (count($gabarito) !== 1 || ! in_array($gabarito[0], ['V', 'F'], true)) {
                throw ValidationException::withMessages(['gabarito' => 'Selecione Verdadeiro ou Falso.']);
            }
        } elseif ($gabarito !== []) {
            throw ValidationException::withMessages(['gabarito' => 'Questões dissertativas precisam ser corrigidas por um professor.']);
        }

        BancoQuestao::query()->create([
            'disciplina_id' => $disciplina->id,
            'aula_id' => $dados['aula_id'] ?? null,
            'criada_por' => $request->user()->id,
            'tipo' => $tipo,
            'dificuldade' => $dados['dificuldade'],
            'enunciado' => trim($dados['enunciado']),
            'alternativas' => $alternativas,
            'gabarito' => $gabarito,
            'explicacao' => $dados['explicacao'] ?? null,
            'referencia' => $dados['referencia'] ?? null,
            'origem' => 'manual',
            'status' => $request->boolean('revisada') ? 'revisada' : 'rascunho',
        ]);

        return redirect()->route('secretaria.ensino.questoes')->with('status', 'Questão adicionada ao banco.');
    }

    /**
     * Assistente determinístico: produz EXCERTOS rastreáveis do texto fornecido
     * pela aula, sem inventar gabaritos nem afirmar que assistiu ao vídeo.
     * Todos os resultados exigem edição e revisão humana antes da publicação.
     */
    public function sugerirQuestao(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'aula_id' => ['required', 'integer', 'exists:aulas,id'],
            'dificuldade' => ['required', Rule::in(['facil', 'medio', 'dificil'])],
            'quantidade' => ['required', 'integer', 'min:1', 'max:3'],
        ]);
        $aula = Aula::query()->findOrFail($dados['aula_id']);
        abort_unless($aula->disciplina_id, 422);
        $fonte = trim(strip_tags((string) ($aula->transcricao_video ?: $aula->conteudo_texto)));
        if (mb_strlen($fonte) < 80) {
            throw ValidationException::withMessages(['aula_id' =>
                'A aula precisa ter pelo menos 80 caracteres de texto ou transcrição revisada. Não é possível interpretar o link do vídeo diretamente.']);
        }
        $trechos = collect(preg_split('/(?<=[.!?])\s+/u', $fonte))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => mb_strlen($v) >= 70 && mb_strlen($v) <= 600)
            ->take($dados['quantidade'])->values();
        if ($trechos->isEmpty()) {
            throw ValidationException::withMessages(['aula_id' => 'O texto não contém trechos adequados para criar rascunhos; revise sua transcrição.']);
        }

        DB::transaction(function () use ($trechos, $dados, $aula, $request) {
            foreach ($trechos as $trecho) {
                BancoQuestao::query()->create([
                    'disciplina_id' => $aula->disciplina_id,
                    'aula_id' => $aula->id,
                    'criada_por' => $request->user()->id,
                    'tipo' => 'dissertativa',
                    'dificuldade' => $dados['dificuldade'],
                    'enunciado' => 'Explique, com suas palavras, a ideia principal do trecho estudado.',
                    'alternativas' => [],
                    'gabarito' => [],
                    'explicacao' => null,
                    'referencia' => mb_substr($trecho, 0, 500),
                    'origem' => 'texto_assistido',
                    'status' => 'rascunho',
                ]);
            }
        });

        return redirect()->route('secretaria.ensino.questoes')
            ->with('status', 'Rascunhos criados com referência ao texto. Revise o enunciado e o gabarito antes de usar.');
    }

    public function salvarInstrumento(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'disciplina_id' => ['required', 'integer', 'exists:disciplinas,id'],
            'aula_id' => ['nullable', 'integer', 'exists:aulas,id'],
            'tipo' => ['required', Rule::in(['quiz', 'prova', 'avaliacao', 'simulado'])],
            'titulo' => ['required', 'string', 'max:255'],
            'instrucoes' => ['nullable', 'string', 'max:5000'],
            'tempo_limite_minutos' => ['nullable', 'integer', 'min:1', 'max:600'],
            'tentativas_permitidas' => ['required', 'integer', 'min:1', 'max:10'],
            'embaralhar' => ['nullable', 'boolean'],
        ]);
        if ($dados['tipo'] === 'quiz') {
            if (empty($dados['aula_id'])) {
                throw ValidationException::withMessages(['aula_id' => 'Um quiz precisa estar dentro de uma aula.']);
            }
            $aula = Aula::query()->findOrFail($dados['aula_id']);
            if ((int) $aula->disciplina_id !== (int) $dados['disciplina_id']) {
                throw ValidationException::withMessages(['aula_id' => 'A aula precisa pertencer à disciplina do quiz.']);
            }
        } elseif (! empty($dados['aula_id'])) {
            throw ValidationException::withMessages(['aula_id' => 'Provas, avaliações e simulados ficam separados das aulas.']);
        }

        $instrumento = InstrumentoAvaliacao::query()->create([
            ...$dados,
            'aula_id' => $dados['tipo'] === 'quiz' ? $dados['aula_id'] : null,
            'criado_por' => $request->user()->id,
            'embaralhar' => $request->boolean('embaralhar'),
            'status' => 'rascunho',
        ]);

        return redirect()->route('secretaria.ensino.instrumento', $instrumento)
            ->with('status', 'Instrumento criado em rascunho. Associe questões revisadas antes de publicar.');
    }

    public function associarQuestao(Request $request, InstrumentoAvaliacao $instrumento): RedirectResponse
    {
        $dados = $request->validate([
            'questao_id' => ['required', 'integer', 'exists:banco_questoes,id'],
            'pontos' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        DB::transaction(function () use ($instrumento, $dados) {
            $instrumento = InstrumentoAvaliacao::query()->lockForUpdate()->findOrFail($instrumento->id);
            if ($instrumento->status !== 'rascunho') {
                throw ValidationException::withMessages(['questao_id' => 'Instrumentos publicados não podem ser alterados.']);
            }
            $questao = BancoQuestao::query()->findOrFail($dados['questao_id']);
            if ((int) $questao->disciplina_id !== (int) $instrumento->disciplina_id ||
                $questao->status !== 'revisada') {
                throw ValidationException::withMessages(['questao_id' => 'Escolha uma questão revisada da mesma disciplina.']);
            }
            if ($instrumento->questoes()->where('banco_questoes.id', $questao->id)->exists()) {
                throw ValidationException::withMessages(['questao_id' => 'Esta questão já faz parte do instrumento.']);
            }
            $ordem = (int) $instrumento->questoes()->count() + 1;
            if ($ordem > 50) {
                throw ValidationException::withMessages(['questao_id' => 'Limite de 50 questões por instrumento.']);
            }
            $instrumento->questoes()->attach($questao->id, ['ordem' => $ordem, 'pontos' => $dados['pontos']]);
        });

        return back()->with('status', 'Questão adicionada ao instrumento.');
    }

    public function publicar(Request $request, InstrumentoAvaliacao $instrumento): RedirectResponse
    {
        $request->validate(['confirmar_publicacao' => ['accepted']]);
        DB::transaction(function () use ($instrumento) {
            $instrumento = InstrumentoAvaliacao::query()->lockForUpdate()->findOrFail($instrumento->id);
            if ($instrumento->status !== 'rascunho' || ! $instrumento->questoes()->exists()) {
                throw ValidationException::withMessages(['confirmar_publicacao' => 'Adicione questões antes de publicar.']);
            }
            if ($instrumento->questoes()->where('status', '!=', 'revisada')->exists()) {
                throw ValidationException::withMessages(['confirmar_publicacao' => 'Existem questões não revisadas.']);
            }
            $instrumento->update(['status' => 'publicado']);
        });

        return back()->with('status', 'Instrumento aprovado para publicação no módulo. Integração ao portal do aluno permanece separada.');
    }
}
