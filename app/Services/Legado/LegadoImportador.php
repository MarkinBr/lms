<?php

namespace App\Services\Legado;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class LegadoImportador
{
    private Connection $destino;

    private string $origem;

    private array $resultado;

    private array $ids;

    public function executar(string $origem, bool $aplicar): array
    {
        if (! in_array(app()->environment(), ['testing', 'homologacao'], true)) {
            throw new InvalidArgumentException('Importação permitida somente em testing ou homologacao.');
        }
        if (! preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $origem) || ! config('database.connections.'.$origem)) {
            throw new InvalidArgumentException('Conexão de origem inválida.');
        }
        $this->destino = DB::connection();
        $fonte = DB::connection($origem);
        if (app()->environment('homologacao') && (
            $this->destino->getDriverName() !== 'pgsql'
            || $this->destino->getDatabaseName() !== 'portal84_lms'
            || $fonte->getDriverName() !== 'pgsql'
            || $fonte->getDatabaseName() !== 'portal84_legado'
        )) {
            throw new InvalidArgumentException('Homologação exige os bancos isolados portal84_lms e portal84_legado.');
        }
        if ($fonte === $this->destino || $this->mesmoBanco($fonte, $this->destino)) {
            throw new InvalidArgumentException('Origem e destino precisam ser bancos distintos.');
        }
        $dados = (new FonteLegado)->ler($fonte);
        $this->origem = $origem;
        $this->ids = [];
        $this->resultado = ['previa' => ! $aplicar, 'criados' => [], 'existentes' => [], 'conflitos' => []];
        $this->destino->beginTransaction();
        try {
            $this->cursos($dados['cursos']);
            $this->modulos($dados['modulos']);
            $this->pessoas('alunos', $dados['alunos'], 'aluno');
            $this->pessoas('professores', $dados['professores'], 'professor');
            $this->vinculos($dados);
            $this->aulas($dados);
            $this->materiais($dados['materiais']);
            $this->historicos($dados['historicos']);
            if ($aplicar) {
                $this->destino->commit();
            } else {
                $this->destino->rollBack();
            }
        } catch (Throwable $erro) {
            $this->destino->rollBack();
            throw $erro;
        }

        return $this->resultado;
    }

    private function mesmoBanco(Connection $a, Connection $b): bool
    {
        if ($a->getDriverName() !== $b->getDriverName()) {
            return false;
        }
        if ($a->getDriverName() === 'sqlite') {
            return $a->getDatabaseName() !== ':memory:' && realpath($a->getDatabaseName()) === realpath($b->getDatabaseName());
        }

        return $a->getDatabaseName() === $b->getDatabaseName()
            && $a->getConfig('host') === $b->getConfig('host')
            && (string) $a->getConfig('port') === (string) $b->getConfig('port');
    }

    private function cursos(array $registros): void
    {
        foreach ($registros as $r) {
            $titulo = trim((string) ($r['nome'] ?? ''));
            if ($titulo === '') {
                $this->conflito('cursos', $r['id'], 'titulo_ausente');

                continue;
            }
            $existente = config('legado.cursos_existentes.'.($r['slug'] ?? ''));
            if (! $existente && ! $this->mapa('cursos', $r['id']) && $this->destino->table('cursos')->whereRaw('LOWER(titulo) = ?', [mb_strtolower($titulo)])->exists()) {
                $this->conflito('cursos', $r['id'], 'curso_exige_mapeamento_explicito');

                continue;
            }
            $this->gravar('cursos', $r['id'], 'cursos', ['titulo' => $titulo, 'descricao' => $r['descricao'] ?? null, 'ativo' => $this->booleano($r['ativo'] ?? true) && empty($r['deleted_at'])], $r, $existente ? (int) $existente : null);
        }
    }

    private function modulos(array $registros): void
    {
        foreach ($registros as $r) {
            $curso = $this->id('cursos', $r['curso_id']);
            if ($curso) {
                $this->gravar('modulos', $r['id'], 'modulos', ['curso_id' => $curso, 'titulo' => $r['titulo'], 'ordem' => (int) ($r['ordem'] ?? 0)], $r);
            }
        }
    }

    private function pessoas(string $tabela, array $registros, string $nivel): void
    {
        foreach ($registros as $r) {
            $login = $nivel === 'aluno' ? mb_strtolower(trim((string) ($r['matricula'] ?? ''))) : null;
            $email = mb_strtolower(trim((string) ($r['email'] ?? ''))) ?: null;
            $cpf = preg_replace('/\D/', '', (string) ($r['cpf'] ?? '')) ?: null;
            if (($nivel === 'aluno' && ! $login) || ! preg_match('/^\$2[aby]\$\d{2}\$[.\/A-Za-z0-9]{53}$/D', (string) ($r['senha_hash'] ?? ''))) {
                $this->conflito($tabela, $r['id'], 'credencial_requer_atendimento');

                continue;
            }
            if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->conflito($tabela, $r['id'], 'email_invalido');

                continue;
            }
            if (! $this->mapa($tabela, $r['id'])) {
                $colisao = $this->destino->table('users')->where(function ($q) use ($login, $email, $cpf): void {
                    $q->whereRaw('1 = 0');
                    if ($login) {
                        $q->orWhere('matricula_login', $login);
                    }
                    if ($email) {
                        $q->orWhereRaw('LOWER(email) = ?', [$email]);
                    }
                    if ($cpf) {
                        $q->orWhereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [$cpf]);
                    }
                })->exists();
                if ($colisao) {
                    $this->conflito($tabela, $r['id'], 'identidade_em_conflito');

                    continue;
                }
            }
            $this->gravar($tabela, $r['id'], 'users', [
                'name' => $r['nome'], 'email' => $email, 'email_verified_at' => null,
                'matricula_login' => $login, 'cpf' => $cpf, 'password' => $r['senha_hash'],
                'nivel' => $nivel, 'ativo' => $this->booleano($r['ativo'] ?? true) && ! $this->booleano($r['bloqueado'] ?? false),
            ], $r);
        }
    }

    private function vinculos(array $dados): void
    {
        $grupos = [];
        foreach (['aluno_cursos', 'matriculas'] as $tabela) {
            foreach ($dados[$tabela] as $r) {
                $r['_tabela'] = $tabela;
                $r['_aluno'] = $r['aluno_id'] ?? $r['user_id'];
                $grupos[$r['_aluno'].'|'.$r['curso_id']][] = $r;
            }
        }
        foreach ($grupos as $registros) {
            $primeiro = $registros[0];
            $aluno = $this->id('alunos', $primeiro['_aluno']);
            $curso = $this->id('cursos', $primeiro['curso_id']);
            $status = array_unique(array_map(fn ($r) => $r['_tabela'] === 'aluno_cursos' ? ($this->booleano($r['ativo']) ? 'ativa' : 'cancelada') : $this->status((string) $r['status']), $registros));
            if (count($status) !== 1 || $status[0] === null) {
                $this->conflito('vinculos', $primeiro['id'], 'status_exige_reconciliacao');

                continue;
            }
            if (! $aluno || ! $curso) {
                $this->conflito('vinculos', $primeiro['id'], 'identidade_nao_mapeada');

                continue;
            }
            $oficiais = array_values(array_filter($registros, fn ($r) => $r['_tabela'] === 'matriculas'));
            $r = $oficiais[0] ?? $primeiro;
            $idExistente = null;
            foreach ($registros as $v) {
                $idExistente ??= $this->mapa($v['_tabela'], $v['id'])?->id_destino;
            }
            if (! $idExistente && $this->destino->table('matriculas')->where('user_id', $aluno)->where('curso_id', $curso)->exists()) {
                $this->conflito('vinculos', $primeiro['id'], 'vinculo_existente_sem_mapa');

                continue;
            }
            foreach ($registros as $v) {
                $idExistente = $this->gravar($v['_tabela'], $v['id'], 'matriculas', [
                    'user_id' => $aluno, 'curso_id' => $curso, 'status' => $status[0],
                    'numero_matricula' => $r['numero_matricula'] ?? $r['matricula'] ?? null,
                    'data_matricula' => substr((string) ($r['data_matricula'] ?? $r['matriculado_em'] ?? now()->toDateString()), 0, 10),
                ], $v, $idExistente ? (int) $idExistente : null);
                // Uma falha nunca pode transformar atualização em criação no próximo vínculo.
                if ($idExistente === null) {
                    break;
                }
            }
        }
    }

    private function aulas(array $dados): void
    {
        foreach ($dados['aula_programacao'] as $r) {
            $curso = $this->id('cursos', $r['curso_id']);
            if (! $curso) {
                continue;
            }
            $modulos = array_values(array_filter($dados['modulos'], fn ($m) => (string) $m['curso_id'] === (string) $r['curso_id']));
            $modulo = count($modulos) === 1 ? $this->id('modulos', $modulos[0]['id']) : null;
            if (! $modulo) {
                $modulo = $this->gravar('organizacao_aulas', $r['curso_id'], 'modulos', ['curso_id' => $curso, 'titulo' => 'Programação importada', 'descricao' => 'Organização de transição; não define disciplina ou carga horária.', 'ordem' => 999], ['curso_id' => $r['curso_id']]);
            }
            if (! $modulo) {
                continue;
            }
            $video = trim((string) ($r['video_id'] ?? ''));
            $video = preg_match('/^[A-Za-z0-9_-]{11}$/D', $video) ? 'https://www.youtube.com/watch?v='.$video : $this->url($video);
            $materiais = [];
            foreach ($dados['materiais'] as $m) {
                if ((string) ($m['aula_id'] ?? '') === (string) $r['id'] && (string) $m['curso_id'] === (string) $r['curso_id'] && $this->url($m['url'] ?? '')) {
                    $materiais[] = ['nome' => $m['nome'], 'url' => $this->url($m['url']), 'tipo' => $m['tipo'] ?? ''];
                }
            }
            $pdf = collect($materiais)->firstWhere('tipo', 'pdf');
            $this->gravar('aula_programacao', $r['id'], 'aulas', [
                'modulo_id' => $modulo, 'titulo' => $r['titulo'], 'descricao' => $r['descricao'] ?? null,
                'video_url' => $video ?: null, 'video_provider' => $video && str_contains($video, 'youtube.com/') ? 'youtube' : 'direto',
                'duracao' => isset($r['duracao_minutos']) ? (string) $r['duracao_minutos'] : null,
                'ordem' => (int) ($r['ordem'] ?? 0), 'liberada' => ($r['status'] ?? '') === 'encerrada',
                'tipo_conteudo' => $video ? 'video' : 'texto', 'materiais' => json_encode($materiais, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'pdf_url' => $pdf['url'] ?? $this->url($r['material_url'] ?? ''), 'pdf_titulo' => $pdf['nome'] ?? null,
            ], $r);
        }
    }

    private function materiais(array $registros): void
    {
        foreach ($registros as $r) {
            $curso = $this->id('cursos', $r['curso_id'] ?? '');
            $aula = empty($r['aula_id']) ? null : $this->id('aula_programacao', $r['aula_id']);
            $url = $this->url($r['url'] ?? '');
            if (! $curso || ! $url || (! empty($r['aula_id']) && ! $aula)) {
                $this->conflito('materiais', $r['id'], 'vinculo_ou_url_exige_revisao');

                continue;
            }
            $this->gravar('materiais', $r['id'], 'legado_materiais', ['curso_id' => $curso, 'aula_id' => $aula, 'nome' => $r['nome'], 'url' => $url, 'tipo' => $r['tipo'] ?? null], $r);
        }
    }

    private function historicos(array $dados): void
    {
        foreach ($dados as $tabela => $registros) {
            foreach ($registros as $r) {
                $id = $r['id'] ?? hash('sha256', json_encode($r, JSON_THROW_ON_ERROR));
                $this->gravar('historico_'.$tabela, $id, 'legado_registros', ['origem' => $this->origem, 'tabela' => $tabela, 'id_origem' => (string) $id, 'conteudo' => json_encode($r, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)], $r);
            }
        }
    }

    private function gravar(string $tabela, mixed $origemId, string $destinoTabela, array $valores, array $fonte, ?int $idExistente = null): ?int
    {
        $mapa = $this->mapa($tabela, $origemId);
        $campos = array_keys($valores);
        if ($mapa) {
            $campos = json_decode($mapa->campos, true, flags: JSON_THROW_ON_ERROR);
            $registro = $this->destino->table($destinoTabela)->where('id', $mapa->id_destino)->first($campos);
            if (! $registro || $this->impressao((array) $registro) !== $mapa->impressao_destino || $this->impressao($fonte) !== $mapa->impressao_origem) {
                $this->conflito($tabela, $origemId, 'alteracao_exige_reconciliacao');

                return null;
            }
            $id = (int) $mapa->id_destino;
            $this->resultado['existentes'][$tabela] = ($this->resultado['existentes'][$tabela] ?? 0) + 1;
        } else {
            if ($idExistente) {
                $registro = $this->destino->table($destinoTabela)->where('id', $idExistente)->first($campos);
                if (! $registro || $this->impressao((array) $registro) !== $this->impressao($valores)) {
                    $this->conflito($tabela, $origemId, 'destino_exige_reconciliacao');

                    return null;
                }
                $id = $idExistente;
            } else {
                $id = $this->destino->table($destinoTabela)->insertGetId($valores + ['created_at' => now(), 'updated_at' => now()]);
            }
            $registro = $this->destino->table($destinoTabela)->where('id', $id)->first($campos);
            $this->destino->table('legado_mapeamentos')->insert([
                'origem' => $this->origem, 'tabela' => $tabela, 'id_origem' => (string) $origemId,
                'tabela_destino' => $destinoTabela, 'id_destino' => $id,
                'impressao_origem' => $this->impressao($fonte), 'impressao_destino' => $this->impressao((array) $registro),
                'campos' => json_encode($campos, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->resultado['criados'][$tabela] = ($this->resultado['criados'][$tabela] ?? 0) + 1;
        }
        $this->ids[$tabela][(string) $origemId] = $id;

        return $id;
    }

    private function mapa(string $tabela, mixed $id): ?object
    {
        return $this->destino->table('legado_mapeamentos')->where('origem', $this->origem)->where('tabela', $tabela)->where('id_origem', (string) $id)->first();
    }

    private function id(string $tabela, mixed $id): ?int
    {
        return $this->ids[$tabela][(string) $id] ?? null;
    }

    private function conflito(string $tabela, mixed $id, string $motivo): void
    {
        $this->resultado['conflitos'][] = ['tabela' => $tabela, 'referencia' => hash('sha256', (string) $id), 'motivo' => $motivo];
    }

    private function impressao(array $valores): string
    {
        ksort($valores);

        return hash('sha256', json_encode(array_map(fn ($v) => $v === null ? null : (string) $v, $valores), JSON_THROW_ON_ERROR));
    }

    private function booleano(mixed $v): bool
    {
        return in_array($v, [true, 1, '1', 'true', 't'], true);
    }

    private function status(string $s): ?string
    {
        return match (mb_strtolower(trim($s))) {
            'ativo', 'ativa' => 'ativa', 'concluido', 'concluida', 'concluído', 'concluída' => 'concluida',
            'cancelado', 'cancelada' => 'cancelada', 'trancado', 'trancada' => 'trancada', default => null,
        };
    }

    private function url(string $url): ?string
    {
        $url = trim($url);

        return filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) && ! parse_url($url, PHP_URL_USER) ? $url : null;
    }
}
