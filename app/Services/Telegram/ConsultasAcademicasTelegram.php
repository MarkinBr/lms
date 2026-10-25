<?php

namespace App\Services\Telegram;

use App\Models\Application;
use App\Models\Aula;
use App\Models\ChamadaFrequencia;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\User;

class ConsultasAcademicasTelegram
{
    private const POR_PAGINA = 5;

    private const TITULOS = [
        'alunos' => 'Alunos',
        'inscricoes' => 'Inscrições',
        'matriculas' => 'Matrículas',
        'turmas' => 'Turmas',
        'aulas' => 'Aulas',
    ];

    public function menu(): array
    {
        $teclado = [];
        foreach (self::TITULOS as $chave => $titulo) {
            $teclado[] = [['text' => $titulo, 'callback_data' => "lista:{$chave}:1"]];
        }

        return ['text' => "Menu CETSADP • Consultas\nEscolha uma área ou use /buscar NOME, /aluno ID, /notas ID e /frequencia ID.\nInformações detalhadas devem ser consultadas no painel autenticado.",
            'reply_markup' => ['inline_keyboard' => $teclado]];
    }

    public function listar(string $categoria, int $pagina): array
    {
        if (! isset(self::TITULOS[$categoria]) || $pagina < 1 || $pagina > 20) {
            return ['text' => 'Opção inválida. Use /menu.'];
        }

        $consulta = match ($categoria) {
            'alunos' => User::query()->where('nivel', 'aluno')->orderBy('id')
                ->select(['id', 'name', 'ativo']),
            'inscricoes' => Application::query()->orderBy('created_at', 'desc')->orderBy('id', 'desc')
                ->select(['id', 'nome_completo', 'status']),
            'matriculas' => Matricula::query()->orderBy('id', 'desc')
                ->select(['id', 'user_id', 'curso_id', 'turma_id', 'status'])
                ->with(['aluno:id,name', 'curso:id,titulo']),
            'turmas' => Turma::query()->orderBy('id', 'desc')
                ->select(['id', 'nome', 'codigo', 'status', 'curso_id'])
                ->with('curso:id,titulo'),
            'aulas' => Aula::query()->orderBy('id', 'desc')
                ->select(['id', 'titulo', 'disciplina_id', 'liberada']),
        };

        $itens = $consulta->skip(($pagina - 1) * self::POR_PAGINA)
            ->take(self::POR_PAGINA + 1)->get();
        $temProxima = $itens->count() > self::POR_PAGINA;
        $linhas = $itens->take(self::POR_PAGINA)->map(function ($item) use ($categoria) {
            return match ($categoria) {
                'alunos' => '#'.$item->id.' • '.$this->curto($item->name).' — '.($item->ativo ? 'ativo' : 'inativo'),
                'inscricoes' => '#'.$this->curto((string) $item->id, 36).' • '.$this->curto($item->nome_completo).' — '.$item->status,
                'matriculas' => '#'.$item->id.' • aluno #'.$item->user_id.' '.$this->curto($item->aluno?->name ?? '')
                    .' • '.$this->curto($item->curso?->titulo ?? 'Curso não informado').' — '.$item->status,
                'turmas' => '#'.$item->id.' • '.$this->curto($item->nome).' — '.$item->status,
                'aulas' => '#'.$item->id.' • '.$this->curto($item->titulo).' — '.($item->liberada ? 'liberada' : 'não liberada'),
            };
        })->all();

        $texto = self::TITULOS[$categoria]." • Página {$pagina}\n"
            .(count($linhas) ? implode("\n", $linhas) : 'Nenhum registro nesta página.')
            ."\n\nPara detalhes, utilize o painel do CETSADP.";
        $botoes = [];
        if ($pagina > 1) {
            $botoes[] = ['text' => '◀ Anterior', 'callback_data' => "lista:{$categoria}:".($pagina - 1)];
        }
        if ($temProxima && $pagina < 20) {
            $botoes[] = ['text' => 'Próxima ▶', 'callback_data' => "lista:{$categoria}:".($pagina + 1)];
        }
        $teclado = $botoes ? [$botoes] : [];
        $teclado[] = [['text' => 'Menu', 'callback_data' => 'menu']];

        return ['text' => $texto, 'reply_markup' => ['inline_keyboard' => $teclado]];
    }

    public function buscarAluno(string $termo): array
    {
        $termo = trim($termo);
        if (mb_strlen($termo) < 2 || mb_strlen($termo) > 60) {
            return ['text' => 'Informe entre 2 e até 60 caracteres: /buscar NOME'];
        }
        $escape = addcslashes($termo, '%_\\');
        $alunos = User::query()->where('nivel', 'aluno')
            ->where('name', 'like', '%'.$escape.'%')
            ->orderBy('id')->limit(5)->get(['id', 'name', 'ativo']);
        $lista = $alunos->map(fn ($a) => '#'.$a->id.' • '.$this->curto($a->name)
            .' — '.($a->ativo ? 'ativo' : 'inativo'))->all();

        return ['text' => "Busca de alunos\n"
            .($lista ? implode("\n", $lista) : 'Nenhum aluno encontrado.')
            ."\nExibindo no máximo cinco. Consulte o painel para a lista completa."];
    }

    public function aluno(int $id): array
    {
        if ($id < 1) {
            return ['text' => 'Identificador inválido.'];
        }
        $aluno = User::query()->where('nivel', 'aluno')->find($id, ['id', 'name', 'ativo']);
        if (! $aluno) {
            return ['text' => 'Aluno não encontrado.'];
        }
        $matriculas = Matricula::query()->where('user_id', $id)
            ->orderByDesc('id')->limit(5)->with('curso:id,titulo')->get();
        $texto = "Ficha acadêmica resumida\n#{$aluno->id} • ".$this->curto($aluno->name)
            ."\nCadastro: ".($aluno->ativo ? 'ativo' : 'inativo');
        foreach ($matriculas as $matricula) {
            $texto .= "\nMatrícula #{$matricula->id}: ".$this->curto($matricula->curso?->titulo ?? 'Curso não informado')
                ." ({$matricula->status})";
        }

        return ['text' => $texto."\nSem documentos ou dados pessoais. Consulte detalhes no painel autenticado."];
    }

    public function notas(int $id): array
    {
        if (! User::query()->where('nivel', 'aluno')->whereKey($id)->exists()) {
            return ['text' => 'Aluno não encontrado.'];
        }
        $notas = Nota::query()->where('user_id', $id)->orderByDesc('id')->limit(5)->get();
        $linhas = $notas->map(fn ($n) => $this->curto($n->disciplina ?? 'Disciplina não informada')
            .' • média: '.($n->media_final ?? 'não lançada').' • '.$this->curto($n->situacao ?? 'sem situação'))->all();

        return ['text' => "Notas legadas do aluno #{$id}\n"
            .($linhas ? implode("\n", $linhas) : 'Nenhuma nota lançada.')
            ."\nRegistros associados ao aluno; não vinculados inequivocamente à matrícula/disciplina curricular."];
    }

    public function frequencia(int $id): array
    {
        if (! User::query()->where('nivel', 'aluno')->whereKey($id)->exists()) {
            return ['text' => 'Aluno não encontrado.'];
        }
        $matriculas = Matricula::query()->where('user_id', $id)->orderByDesc('id')->limit(5)->get();
        $linhas = $matriculas->map(function ($matricula) {
            $resumo = $matricula->resumoFrequencia();

            return 'Matrícula #'.$matricula->id.': '.$resumo['presentes'].' presenças, '
                .$resumo['faltas'].' faltas, '.$resumo['faltas_justificadas'].' justificadas; '
                .$resumo['percentual'].'% ('.$resumo['chamadas_concluidas'].' chamadas concluídas)';
        })->all();

        return ['text' => "Frequência do aluno #{$id}\n"
            .($linhas ? implode("\n", $linhas) : 'Nenhuma matrícula encontrada.')
            ."\nSomente chamadas concluídas. Confira detalhes no painel."];
    }

    private function curto(string $texto, int $limite = 42): string
    {
        $texto = preg_replace('/[\r\n\t]+/u', ' ', $texto) ?? '';
        return mb_strimwidth($texto, 0, $limite, '…');
    }
}
