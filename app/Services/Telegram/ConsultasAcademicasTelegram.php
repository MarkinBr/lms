<?php

namespace App\Services\Telegram;

use App\Models\Application;
use App\Models\Aula;
use App\Models\AulaAoVivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\User;
use App\Services\GestaoAcademica\PainelAcademicoQuery;

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

        $teclado[] = [['text' => 'Painel do dia', 'callback_data' => 'hoje']];
        $teclado[] = [['text' => 'Relatórios', 'callback_data' => 'relatorios'], ['text' => 'Agenda 7 dias', 'callback_data' => 'agenda7']];
        $teclado[] = [['text' => 'Aulas não liberadas', 'callback_data' => 'aulaspendentes']];
        $teclado[] = [['text' => 'Pendências', 'callback_data' => 'pendencias']];
        $teclado[] = [['text' => 'Agenda', 'callback_data' => 'agenda'], ['text' => 'Cursos', 'callback_data' => 'cursos']];

        return ['text' => "Menu CETSADP • Consultas\nEscolha uma área ou use /buscar NOME, /aluno ID, /notas ID e /frequencia ID.\nInformações detalhadas devem ser consultadas no painel autenticado.",
            'reply_markup' => ['inline_keyboard' => $teclado]];
    }

    public function listar(string $categoria, int $pagina): array
    {
        if (isset(self::TITULOS[$categoria]) === false || $pagina < 1 || $pagina > 20) {
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
        if ($aluno === null) {
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
        if (User::query()->where('nivel', 'aluno')->whereKey($id)->doesntExist()) {
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
        if (User::query()->where('nivel', 'aluno')->whereKey($id)->doesntExist()) {
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

    public function pendencias(): array
    {
        $academico = app(PainelAcademicoQuery::class);
        $estruturais = $academico->pendencias();
        $linhas = ['CETSADP • Pendências', 'Inscrições em análise: '.Application::query()->emAnalise()->count()];

        foreach ([
            'disciplinas_sem_modulo' => 'Disciplinas sem módulo',
            'aulas_sem_disciplina' => 'Aulas sem disciplina',
            'turmas_sem_periodo' => 'Turmas sem período',
            'turmas_sem_disciplina' => 'Turmas sem disciplina',
        ] as $chave => $titulo) {
            $linhas[] = $titulo.': '.($estruturais[$chave]['count'] ?? 0);
        }

        return ['text' => implode("\n", $linhas)."\nConsulte e resolva no painel autenticado."];
    }

    public function agenda(): array
    {
        $eventos = app(PainelAcademicoQuery::class)->proximasAulasAoVivo()->take(5);
        $linhas = $eventos->map(fn (array $aula) => $this->curto((string) $aula['titulo'])
            .' • '.($aula['data_hora'] ?? 'Data não informada')
            .' • '.$this->curto((string) ($aula['curso'] ?? 'Curso não informado')))->all();

        return ['text' => "Próximos encontros\n".($linhas ? implode("\n", $linhas) : 'Nenhum encontro previsto.')
            ."\nLinks de transmissão e informações completas: painel autenticado."];
    }

    public function cursos(): array
    {
        $cursos = Curso::query()->where('ativo', true)->orderBy('titulo')
            ->limit(5)->get(['id', 'titulo']);
        $linhas = $cursos->map(fn (Curso $curso) => '#'.$curso->id.' • '.$this->curto($curso->titulo))->all();

        return ['text' => "Cursos ativos\n".($linhas ? implode("\n", $linhas) : 'Nenhum curso ativo.')
            ."\nExibindo até cinco cursos; consulte os demais no painel."];
    }

    public function turma(int $id): array
    {
        $turma = Turma::query()->with('curso:id,titulo')->find($id);
        if ($id < 1 || $turma === null) {
            return ['text' => 'Turma não encontrada.'];
        }

        return ['text' => 'Turma #'.$turma->id.' • '.$this->curto((string) $turma->nome)
            ."\nCurso: ".$this->curto((string) ($turma->curso?->titulo ?? 'Não informado'))
            ."\nSituação: ".($turma->status ?? 'não informada')
            ."\nModalidade: ".($turma->modalidade ?? 'não informada')
            ."\nConsulte os vínculos individuais no painel."];
    }

    public function matricula(int $id): array
    {
        $matricula = Matricula::query()->with(['aluno:id,name', 'curso:id,titulo', 'turma:id,nome'])->find($id);
        if ($id < 1 || $matricula === null) {
            return ['text' => 'Matrícula não encontrada.'];
        }

        return ['text' => 'Matrícula #'.$matricula->id
            ."\nAluno #".$matricula->user_id.' • '.$this->curto((string) ($matricula->aluno?->name ?? 'Não informado'))
            ."\nCurso: ".$this->curto((string) ($matricula->curso?->titulo ?? 'Não informado'))
            ."\nTurma: ".$this->curto((string) ($matricula->turma?->nome ?? 'Não vinculada no campo legado'))
            ."\nSituação: ".($matricula->status ?? 'não informada')
            ."\nVínculos adicionais de turma: consultar no painel."];
    }

    public function inscricao(string $id): array
    {
        if (preg_match('/^[0-9a-fA-F-]{36}$/D', $id) !== 1) {
            return ['text' => 'Identificador de inscrição inválido.'];
        }

        $inscricao = Application::query()->find($id, ['id', 'nome_completo', 'status', 'curso_id']);
        if ($inscricao === null) {
            return ['text' => 'Inscrição não encontrada.'];
        }

        return ['text' => 'Inscrição #'.$this->curto($inscricao->id, 36)
            ."\nCandidato: ".$this->curto($inscricao->nome_completo)
            ."\nCurso ID: ".($inscricao->curso_id ?? 'não informado')
            ."\nSituação: ".$inscricao->status
            ."\nDocumentos e dados pessoais: apenas no painel autenticado."];
    }


    public function hoje(): array
    {
        $academico = app(PainelAcademicoQuery::class);
        $resumo = $academico->resumo();
        $pendencias = $academico->pendencias();
        $inscricoes = Application::query()->emAnalise()->count();

        return ['text' => "CETSADP • Painel do dia\n"
            .'Alunos ativos: '.$resumo['alunos']."\n"
            .'Matrículas ativas: '.$resumo['matriculas_ativas']."\n"
            .'Inscrições em análise: '.$inscricoes."\n"
            .'Aulas sem disciplina: '.$pendencias['aulas_sem_disciplina']['count']."\n"
            .'Encontros próximos: '.$academico->proximasAulasAoVivo()->count()
            ."\nIndicadores do banco conectado; consulte os detalhes no painel."];
    }

    public function agendaSemanal(): array
    {
        $inicio = now()->startOfDay();
        $fim = now()->addDays(7)->startOfDay();
        $eventos = AulaAoVivo::query()->proximas()
            ->where('data_hora', '<', $fim)
            ->with('curso:id,titulo')->limit(5)->get();
        $linhas = $eventos->map(fn (AulaAoVivo $aula) => $this->curto((string) $aula->titulo)
            .' • '.$aula->data_hora?->format('d/m H:i')
            .' • '.$this->curto((string) ($aula->curso?->titulo ?? 'Curso não informado')))->all();

        return ['text' => "Agenda de sete dias\n"
            .($linhas ? implode("\n", $linhas) : 'Nenhum encontro previsto.')
            ."\nNo máximo cinco encontros. Horários do LMS, sem links privados."];
    }

    public function aulasPendentes(): array
    {
        $q = Aula::query()->where('liberada', false);
        $total = (clone $q)->count();
        $linhas = $q->orderBy('id')->limit(5)->get(['id', 'titulo'])
            ->map(fn (Aula $a) => '#'.$a->id.' • '.$this->curto($a->titulo))->all();

        return ['text' => "Aulas não liberadas: {$total}\n"
            .($linhas ? implode("\n", $linhas) : 'Nenhuma aula nessa situação.')
            ."\nAté cinco exemplos. Não publica ou altera aulas."];
    }

    public function relatorios(): array
    {
        $cursos = Curso::query()->where('ativo', true)->orderBy('titulo')->limit(5)
            ->withCount(['matriculas as matriculas_ativas_count' => fn ($q) => $q->ativas()])
            ->get(['id', 'titulo']);
        $linhas = $cursos->map(fn (Curso $curso) => '#'.$curso->id.' • '.$this->curto($curso->titulo)
            .' • matrículas ativas: '.$curso->matriculas_ativas_count)->all();

        return ['text' => "Relatório por curso\n"
            .($linhas ? implode("\n", $linhas) : 'Nenhum curso ativo.')
            ."\nAté cinco cursos; escopo das matrículas considera status legados."];
    }

    public function filtrarAlunos(string $tipo, string $valor, int $pagina): array
    {
        if ($pagina < 1 || $pagina > 20
            || ! in_array($tipo, ['curso', 'turma', 'status'], true)
            || ! preg_match('/^(ativo|inativo|[1-9][0-9]{0,8})$/D', $valor)) {
            return ['text' => 'Opção inválida. Use /menu.'];
        }

        $query = User::query()->where('nivel', 'aluno');
        if ($tipo === 'status') {
            if (! in_array($valor, ['ativo', 'inativo'], true)) {
                return ['text' => 'Opção inválida. Use /menu.'];
            }
            $query->where('ativo', $valor === 'ativo');
        } elseif ($tipo === 'curso') {
            $query->whereHas('matriculas', fn ($q) => $q->where('curso_id', (int) $valor));
        } else {
            $query->whereHas('matriculas', function ($q) use ($valor) {
                $q->where('turma_id', (int) $valor)
                    ->orWhereHas('turmasHistorico', fn ($t) => $t->where('turmas.id', (int) $valor)
                        ->where('matricula_turma.status', 'ativo'));
            });
        }

        $registros = $query->orderBy('id')->skip(($pagina - 1) * 5)->limit(6)->get(['id', 'name', 'ativo']);
        $linhas = $registros->take(5)->map(fn (User $a) => '#'.$a->id.' • '.$this->curto($a->name)
            .' — '.($a->ativo ? 'ativo' : 'inativo'))->all();
        $teclado = [];
        if ($pagina > 1) {
            $teclado[] = ['text' => '◀', 'callback_data' => "filtro:{$tipo}:{$valor}:".($pagina - 1)];
        }
        if ($registros->count() > 5 && $pagina < 20) {
            $teclado[] = ['text' => '▶', 'callback_data' => "filtro:{$tipo}:{$valor}:".($pagina + 1)];
        }

        return ['text' => "Alunos por {$tipo}: {$valor} • página {$pagina}\n"
            .($linhas ? implode("\n", $linhas) : 'Nenhum aluno encontrado.'),
            'reply_markup' => ['inline_keyboard' => array_merge($teclado ? [$teclado] : [],
                [[['text' => 'Menu', 'callback_data' => 'menu']]])]];
    }

    private function curto(string $texto, int $limite = 42): string
    {
        $texto = preg_replace('/[\r\n\t]+/u', ' ', $texto) ?? '';

        return mb_strimwidth($texto, 0, $limite, '…');
    }
}
