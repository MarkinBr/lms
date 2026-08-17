<?php

namespace App\Services;

use LogicException;

/**
 * Gera e normaliza as frases temporárias de acesso administrativo.
 *
 * A lista possui 128 palavras; 24 escolhas independentes fornecem 168 bits
 * de entropia. A frase nunca é persistida: somente seu HMAC-SHA-256 é
 * armazenado, usando a chave da aplicação como segredo adicional.
 */
class AdminAccessTokenService
{
    private const WORDS = [
        'abacate', 'abrigo', 'acesso', 'acordo', 'agenda', 'alegria', 'aluno', 'amparo',
        'âncora', 'andorinha', 'aprender', 'arquivo', 'assunto', 'ativo', 'aula', 'aviso',
        'banco', 'bandeira', 'barco', 'beleza', 'biblioteca', 'bondade', 'brilho', 'busca',
        'caderno', 'caminho', 'campus', 'caneta', 'canto', 'carinho', 'casa', 'certeza',
        'chave', 'ciclo', 'cidade', 'clareza', 'classe', 'código', 'colegio', 'comunidade',
        'conquista', 'controle', 'coragem', 'cuidado', 'curso', 'decisão', 'defesa', 'desafio',
        'destino', 'direito', 'disciplina', 'documento', 'domínio', 'duvida', 'eco', 'ensino',
        'escola', 'esforço', 'esperança', 'estudo', 'evento', 'exemplo', 'foco', 'fonte',
        'forma', 'futuro', 'garantia', 'gestão', 'grupo', 'guia', 'harmonia', 'história',
        'honra', 'ideia', 'igreja', 'impacto', 'início', 'inscrição', 'integração', 'jornada',
        'justiça', 'leitura', 'legado', 'liberdade', 'livro', 'luz', 'matriz', 'memória',
        'mensagem', 'mestre', 'missão', 'modelo', 'momento', 'mundo', 'nave', 'nível',
        'nome', 'norte', 'objetivo', 'ofício', 'ordem', 'origem', 'palavra', 'parceria',
        'passo', 'paz', 'pessoa', 'plano', 'ponte', 'portal', 'prática', 'presença',
        'processo', 'projeto', 'proposito', 'prova', 'qualidade', 'raiz', 'registro', 'respeito',
        'resposta', 'rota', 'sabedoria', 'segredo', 'segurança', 'sentido', 'serviço', 'sinal',
        'sistema', 'soma', 'sucesso', 'teologia', 'tempo', 'trabalho', 'turma', 'união',
        'valor', 'verdade', 'verso', 'vigia', 'vínculo', 'visão', 'vitoria', 'voz',
    ];

    public function gerar(): string
    {
        $words = [];

        for ($index = 0; $index < 24; $index++) {
            $words[] = self::WORDS[random_int(0, count(self::WORDS) - 1)];
        }

        return implode(' ', $words);
    }

    public function normalizar(string $phrase): string
    {
        return preg_replace('/\s+/u', ' ', mb_strtolower(trim($phrase))) ?? '';
    }

    public function hash(string $phrase): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new LogicException('APP_KEY é obrigatório para tokens administrativos.');
        }

        return hash_hmac('sha256', $this->normalizar($phrase), $key);
    }

    public function fraseValida(string $phrase): bool
    {
        $words = preg_split('/\s+/u', $this->normalizar($phrase), -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) !== 24) {
            return false;
        }

        foreach ($words as $word) {
            if (! preg_match('/^[\p{L}]+$/u', $word)) {
                return false;
            }
        }

        return true;
    }
}
