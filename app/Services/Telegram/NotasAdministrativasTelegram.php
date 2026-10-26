<?php

namespace App\Services\Telegram;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotasAdministrativasTelegram
{
    public function preparar(string $chatId, string $comando, string $conteudo, int $updateId): array
    {
        $comando = ltrim($comando, '/');
        $partes = array_map('trim', explode('|', $conteudo));
        $correcao = $comando === 'corrigirnota';
        if (! in_array($comando, ['lancarnota', 'corrigirnota'], true)
            || count($partes) !== ($correcao ? 5 : 7)) {
            return $this->uso($correcao);
        }

        $id = $this->idValido($partes[0]);
        $valores = $this->avaliacoes(array_slice($partes, $correcao ? 1 : 3));
        if ($id === null || $valores === null) {
            return $this->uso($correcao);
        }

        $nota = null;
        if ($correcao) {
            $nota = Nota::find($id);
            $contexto = $nota ? $this->contextoNota($nota) : null;
            if ($contexto === null || ! $this->periodoValido($nota->periodo)) {
                return ['text' => 'Nota indisponível ou sem vínculo acadêmico ativo e inequívoco. Consulte a secretaria.'];
            }
            $periodo = $nota->periodo;
        } else {
            $disciplinaId = $this->idValido($partes[1]);
            if ($disciplinaId === null || ! $this->periodoValido($partes[2])) {
                return $this->uso(false);
            }
            $periodo = $partes[2];
            $contexto = $this->contexto($id, $disciplinaId);
            if ($contexto === null) {
                return ['text' => 'Aluno ou disciplina indisponível, ou sem matrícula ativa e válida no curso da disciplina.'];
            }
            if ($this->duplicada($id, $contexto['disciplina']->nome, $periodo)) {
                return ['text' => 'Já existe nota para este aluno, disciplina e período. Use /corrigirnota NOTA_ID | AV1 | AV2 | AV3 | FALTAS.'];
            }
        }

        $aluno = $contexto['aluno'];
        $disciplina = $contexto['disciplina'];
        $dados = [
            'user_id' => $aluno->id,
            'disciplina_id' => $disciplina->id,
            'matricula_id' => $contexto['matricula']->id,
            'periodo' => $periodo,
            'valores' => $valores,
            'nota_id' => $nota?->id,
            'estado_hash' => $this->snapshot($contexto, $nota),
        ];
        $previa = ($correcao ? "Corrigir nota #{$nota->id}" : 'Lançar nota')
            ."\nAluno #{$aluno->id} — {$aluno->name}"
            ."\nDisciplina #{$disciplina->id} — {$disciplina->nome}"
            ."\nCurso: {$contexto['curso']->titulo}\nPeríodo: {$periodo}";
        if ($nota) {
            $previa .= "\nAntes: AV1 {$nota->nota_av1}; AV2 {$nota->nota_av2}; AV3 {$nota->nota_av3}; faltas {$nota->faltas}.";
        }
        $previa .= "\nNovos valores: AV1 {$valores['nota_av1']}; AV2 {$valores['nota_av2']}; AV3 {$valores['nota_av3']}; faltas {$valores['faltas']}.";

        return app(ConfirmacoesTelegram::class)->prepararOperacao(
            $chatId, $updateId, $correcao ? 'corrigir_nota' : 'lancar_nota', $dados, $previa,
            $correcao ? 'Corrigir nota' : 'Lançar nota'
        );
    }

    /** A confirmação chama este método dentro da transação e após autorizar o dono. */
    public function executar(string $acao, array $dados, User $usuario): ?Model
    {
        if (! in_array($acao, ['lancar_nota', 'corrigir_nota'], true)) {
            return null;
        }
        $contexto = $this->contexto($dados['user_id'], $dados['disciplina_id'], $dados['matricula_id'], true);
        if ($contexto === null) {
            return null;
        }

        $nota = $acao === 'corrigir_nota'
            ? Nota::whereKey($dados['nota_id'])->lockForUpdate()->first() : null;
        if (($acao === 'corrigir_nota' && ($nota === null
                || (int) $nota->user_id !== (int) $dados['user_id']
                || $nota->disciplina !== $contexto['disciplina']->nome || $nota->periodo !== $dados['periodo']
                || $this->contextoNota($nota) === null))
            || ! hash_equals($dados['estado_hash'], $this->snapshot($contexto, $nota))) {
            return null;
        }

        if ($acao === 'lancar_nota') {
            // O lock do aluno serializa os lançamentos do bot, inclusive propostas distintas.
            if ($this->duplicada($dados['user_id'], $contexto['disciplina']->nome, $dados['periodo'], true)) {
                return null;
            }
            $nota = new Nota([
                'user_id' => $dados['user_id'], 'disciplina' => $contexto['disciplina']->nome,
                'periodo' => $dados['periodo'],
            ]);
        }
        $nota->fill($dados['valores']);
        $nota->save();

        return $nota;
    }

    private function contexto(int $alunoId, int $disciplinaId, ?int $matriculaId = null, bool $bloquear = false): ?array
    {
        $aluno = User::whereKey($alunoId)->when($bloquear, fn ($q) => $q->lockForUpdate())->first();
        $disciplina = Disciplina::whereKey($disciplinaId)->when($bloquear, fn ($q) => $q->lockForUpdate())->first();
        if (! $aluno || ! $aluno->ativo || $aluno->nivel !== 'aluno' || ! $disciplina || ! $disciplina->ativa) {
            return null;
        }
        $curso = Curso::whereKey($disciplina->curso_id)->when($bloquear, fn ($q) => $q->lockForUpdate())->first();
        if (! $curso || ! $curso->ativo) {
            return null;
        }
        $matricula = Matricula::where('user_id', $alunoId)->where('curso_id', $curso->id)->ativas()
            ->where(fn ($q) => $q->whereNull('data_validade')->orWhereDate('data_validade', '>=', today()->toDateString()))
            ->when($matriculaId !== null, fn ($q) => $q->whereKey($matriculaId))
            ->orderBy('id')->when($bloquear, fn ($q) => $q->lockForUpdate())->first();

        return $matricula ? compact('aluno', 'disciplina', 'curso', 'matricula') : null;
    }

    private function contextoNota(Nota $nota): ?array
    {
        if (! $nota->user_id) {
            return null;
        }
        // O legado guarda o título, sem disciplina_id. Recusa títulos ambíguos
        // entre disciplinas elegíveis, em vez de escolher um curso por acaso.
        $contexto = null;
        foreach (Disciplina::where('nome', $nota->disciplina)->ativas()->orderBy('id')->get() as $disciplina) {
            $candidato = $this->contexto((int) $nota->user_id, $disciplina->id);
            if ($candidato !== null) {
                if ($contexto !== null) {
                    return null;
                }
                $contexto = $candidato;
            }
        }

        return $contexto;
    }

    private function duplicada(int $alunoId, string $disciplina, string $periodo, bool $bloquear = false): bool
    {
        return Nota::where('user_id', $alunoId)->where('disciplina', $disciplina)->where('periodo', $periodo)
            ->when($bloquear, fn ($q) => $q->lockForUpdate())->first() !== null;
    }

    private function snapshot(array $contexto, ?Nota $nota): string
    {
        $atributos = [];
        foreach ($contexto + ['nota' => $nota] as $nome => $registro) {
            $atributos[$nome] = $registro?->getAttributes();
            if (is_array($atributos[$nome])) {
                ksort($atributos[$nome]);
            }
        }
        ksort($atributos);

        return hash('sha256', json_encode($atributos, JSON_THROW_ON_ERROR));
    }

    private function idValido(string $valor): ?int
    {
        if (! preg_match('/^[1-9][0-9]*$/D', $valor)) {
            return null;
        }
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function periodoValido(string $periodo): bool
    {
        return $periodo !== '' && mb_strlen($periodo) <= 255 && ! preg_match('/[\x00-\x1F\x7F]/u', $periodo);
    }

    private function avaliacoes(array $partes): ?array
    {
        if (count($partes) !== 4) {
            return null;
        }
        $notas = [];
        foreach (array_slice($partes, 0, 3) as $indice => $valor) {
            $valor = str_replace(',', '.', $valor);
            if (! preg_match('/^[0-9]{1,2}(\.[0-9]{1,2})?$/D', $valor) || (float) $valor > 10) {
                return null;
            }
            $notas['nota_av'.($indice + 1)] = $valor;
        }
        if (! preg_match('/^[0-9]+$/D', $partes[3])) {
            return null;
        }
        $faltas = filter_var($partes[3], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
        if ($faltas === false) {
            return null;
        }

        return $notas + ['faltas' => $faltas];
    }

    private function uso(bool $correcao): array
    {
        return ['text' => ($correcao
            ? 'Uso: /corrigirnota NOTA_ID | AV1 | AV2 | AV3 | FALTAS.'
            : 'Uso: /lancarnota ALUNO_ID | DISCIPLINA_ID | PERIODO | AV1 | AV2 | AV3 | FALTAS.')
            .' Notas de 0 a 10 (até 2 casas decimais); faltas inteiras de 0 a 2147483647.'];
    }
}
