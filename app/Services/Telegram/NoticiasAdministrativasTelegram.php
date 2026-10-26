<?php

namespace App\Services\Telegram;

use App\Models\Noticia;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NoticiasAdministrativasTelegram
{
    public function preparar(string $chatId, string $comando, string $conteudo, int $updateId): array
    {
        $partes = array_map('trim', explode('|', $conteudo, 3));
        $dados = count($partes) === 3
            ? array_combine(['titulo', 'resumo', 'texto'], $partes) : [];
        if (! in_array($comando, ['novanoticia', '/novanoticia'], true) || ! $this->conteudoValido($dados)) {
            return ['text' => 'Uso: /novanoticia TÍTULO | RESUMO | TEXTO. Texto puro: título até 200, resumo até 500 e texto até 2000 caracteres.'];
        }

        $dados['estado_hash'] = $this->estadoRegistroHash(new Noticia($dados));

        return app(ConfirmacoesTelegram::class)->prepararOperacao(
            $chatId, $updateId, 'publicar_noticia', $dados,
            "Publicar notícia institucional no site público?\nTítulo: {$dados['titulo']}\nResumo: {$dados['resumo']}\n\n{$dados['texto']}",
            'Publicar notícia',
        );
    }

    public function executar(string $acao, array $dados, User $usuario): ?Model
    {
        if ($acao !== 'publicar_noticia' || ! $this->conteudoValido($dados)
            || ! is_string($dados['estado_hash'] ?? null)) {
            return null;
        }

        $noticia = new Noticia([
            'titulo' => $dados['titulo'], 'resumo' => $dados['resumo'], 'texto' => $dados['texto'],
        ]);
        if (! hash_equals($this->estadoRegistroHash($noticia), $dados['estado_hash'])) {
            return null;
        }

        // A proposta e a autorização são bloqueadas pelo chamador; revalida a autoria atual.
        $autor = User::query()->whereKey($usuario->getKey())->lockForUpdate()->first();
        if (! $autor || ! $autor->ativo || $autor->nivel !== 'admin') {
            return null;
        }

        $noticia->fill([
            'publicada' => true, 'publicada_em' => now(), 'publicada_por' => $autor->id,
        ])->save();

        return $noticia;
    }

    private function conteudoValido(array $dados): bool
    {
        foreach (['titulo' => 200, 'resumo' => 500, 'texto' => 2000] as $campo => $limite) {
            $valor = $dados[$campo] ?? null;
            if (! is_string($valor) || ! mb_check_encoding($valor, 'UTF-8')
                || trim($valor) === '' || mb_strlen($valor) > $limite
                || preg_match($campo === 'texto' ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u', $valor) === 1) {
                return false;
            }
        }

        return true;
    }

    private function estadoRegistroHash(Model $registro): string
    {
        $atributos = $registro->getAttributes();
        ksort($atributos);

        return hash('sha256', json_encode($atributos, JSON_THROW_ON_ERROR));
    }
}
