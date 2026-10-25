<?php

namespace App\Services\Legado;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class FonteLegado
{
    public const ENTIDADES = [
        'cursos' => ['id', 'nome', 'slug', 'descricao', 'ativo', 'deleted_at'],
        'modulos' => ['id', 'curso_id', 'titulo', 'ordem'],
        'alunos' => ['id', 'nome', 'matricula', 'email', 'cpf', 'telefone', 'senha_hash', 'ativo', 'bloqueado'],
        'professores' => ['id', 'nome', 'username', 'email', 'senha_hash', 'ativo', 'curso_id'],
        'aluno_cursos' => ['id', 'aluno_id', 'curso_id', 'matricula', 'ativo', 'matriculado_em'],
        'matriculas' => ['id', 'user_id', 'curso_id', 'numero_matricula', 'status', 'data_matricula'],
        'aula_programacao' => ['id', 'curso_id', 'titulo', 'descricao', 'data_hora', 'duracao_minutos', 'video_id', 'status', 'ordem', 'material_url'],
        'materiais' => ['id', 'nome', 'url', 'tipo', 'curso_id', 'aula_id', 'is_upload', 'file_path'],
    ];

    public const HISTORICOS = [
        'presence_log', 'frequencia_aulas', 'aula_sessoes', 'aula_sessoes_historico',
        'aula_chat_v15', 'chat_messages', 'chat_mensagens', 'hand_raise_log', 'reactions_log',
        'aula_anotacoes', 'student_notes', 'aluno_aula_progresso', 'aula_progresso_log',
        'aula_forum_respostas_base', 'aula_forum_topicos_base', 'aula_perguntas',
        'forum_perguntas', 'forum_votos', 'questions', 'question_likes', 'polls', 'poll_votes',
        'aula_config', 'curso_config', 'notifications',
    ];

    public function ler(Connection $conexao): array
    {
        return $conexao->transaction(function () use ($conexao): array {
            if ($conexao->getDriverName() === 'pgsql') {
                $conexao->statement('SET TRANSACTION READ ONLY');
            }
            $schema = Schema::connection($conexao->getName());
            $dados = [];
            foreach (self::ENTIDADES as $tabela => $campos) {
                if (! $schema->hasTable($tabela)) {
                    throw new InvalidArgumentException('A origem não contém a estrutura esperada do legado.');
                }
                $campos = array_intersect($campos, $schema->getColumnListing($tabela));
                $dados[$tabela] = $conexao->table($tabela)->select($campos)->get()->map(fn ($r) => (array) $r)->all();
            }
            $dados['historicos'] = [];
            foreach (self::HISTORICOS as $tabela) {
                if (! $schema->hasTable($tabela)) {
                    continue;
                }
                $campos = array_values(array_filter($schema->getColumnListing($tabela), fn ($c) => ! preg_match('/senha|password|token|secret|ip_address/i', $c)));
                $dados['historicos'][$tabela] = $conexao->table($tabela)->select($campos)->get()->map(fn ($r) => (array) $r)->all();
            }

            return $dados;
        });
    }
}
