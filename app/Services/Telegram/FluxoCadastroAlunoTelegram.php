<?php

namespace App\Services\Telegram;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use JsonException;

/**
 * Atendimento guiado: o rascunho expira e nunca cria contas diretamente.
 * A efetivação continua no serviço de cadastro e na confirmação administrativa.
 */
class FluxoCadastroAlunoTelegram
{
    private const MINUTOS = 15;

    public function iniciar(string $chatId): array
    {
        $estado = ['etapa' => 0, 'dados' => []];
        $this->salvar($chatId, $estado);

        return $this->pergunta($estado);
    }

    public function ativo(string $chatId): bool
    {
        return $this->carregar($chatId) !== null;
    }

    public function responder(string $chatId, string $texto): ?array
    {
        $estado = $this->carregar($chatId);
        if ($estado === null) {
            return null;
        }

        $etapa = (int) $estado['etapa'];
        if ($etapa >= 3) {
            return $this->pergunta($estado, 'Use os botões para revisar o cadastro.');
        }

        $texto = trim($texto);
        $erro = $this->validar($etapa, $texto);
        if ($erro !== null) {
            return $this->pergunta($estado, $erro);
        }

        $estado['dados'][$etapa] = $texto;
        $estado['etapa']++;
        $this->salvar($chatId, $estado);

        return $this->pergunta($estado);
    }

    public function acao(string $chatId, string $codigo, int $updateId): array
    {
        if ($codigo === 'novoaluno') {
            return $this->iniciar($chatId);
        }

        $estado = $this->carregar($chatId);
        if ($estado === null) {
            return [
                'text' => 'O cadastro guiado expirou ou foi cancelado. Inicie novamente.',
                'reply_markup' => ['inline_keyboard' => [
                    [['text' => 'Cadastrar aluno', 'callback_data' => 'fluxo:novoaluno']],
                    [['text' => 'Menu principal', 'callback_data' => 'menu']],
                ]],
            ];
        }

        if ($codigo === 'cancelar') {
            $this->limpar($chatId);

            return [
                'text' => 'Cadastro cancelado. Nenhum aluno foi criado.',
                'reply_markup' => ['inline_keyboard' => [
                    [['text' => 'Menu principal', 'callback_data' => 'menu']],
                ]],
            ];
        }

        if ($codigo === 'voltar') {
            $estado['etapa'] = max(0, (int) $estado['etapa'] - 1);
            unset($estado['dados'][$estado['etapa']]);
            $this->salvar($chatId, $estado);

            return $this->pergunta($estado);
        }

        if ($codigo === 'pular') {
            if (in_array($estado['etapa'], [1, 2], true) === false) {
                return $this->pergunta($estado);
            }

            return $this->responder($chatId, '-') ?? $this->iniciar($chatId);
        }

        if ($codigo !== 'preparar' || $estado['etapa'] !== 3) {
            return $this->pergunta($estado);
        }

        $dados = $estado['dados'];
        if (isset($dados[0], $dados[1], $dados[2]) === false) {
            $this->limpar($chatId);

            return ['text' => 'Rascunho incompleto. Inicie o cadastro novamente.'];
        }

        // As regras de CPF, unicidade e confirmação permanecem no serviço de domínio.
        $resposta = app(AlunosAdministrativosTelegram::class)->preparar(
            $chatId, 'novoaluno', implode(' | ', [$dados[0], $dados[1], $dados[2]]), $updateId
        );

        if (isset($resposta['reply_markup'])) {
            $this->limpar($chatId);

            return $resposta;
        }

        // Sem confirmação pronta, mantém o rascunho para correção.
        return $this->pergunta($estado, $resposta['text'] ?? 'Não foi possível validar o cadastro.');
    }

    public function cancelar(string $chatId): void
    {
        $this->limpar($chatId);
    }

    private function validar(int $etapa, string $valor): ?string
    {
        if ($etapa === 0) {
            return mb_strlen($valor) >= 2 && mb_strlen($valor) <= 255
                && preg_match('/[\x00-\x1F\x7F|]/u', $valor) === 0
                ? null : 'Informe um nome de 2 a 255 caracteres, sem caracteres de controle.';
        }

        if ($etapa === 1) {
            return $valor === '-' || (mb_strlen($valor) <= 255 && filter_var($valor, FILTER_VALIDATE_EMAIL))
                ? null : 'Digite um e-mail válido ou toque em Pular.';
        }

        if ($etapa === 2) {
            return $valor === '-' || Validator::make(['cpf' => $valor], [
                'cpf' => ['required', 'regex:/^(?:[0-9]{11}|[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2})$/D', 'cpf'],
            ])->passes() ? null : 'Digite um CPF válido ou toque em Pular.';
        }

        return 'Etapa inválida.';
    }

    private function pergunta(array $estado, ?string $erro = null): array
    {
        $etapa = (int) $estado['etapa'];
        $dados = $estado['dados'];

        $texto = match ($etapa) {
            0 => "CETSADP • Cadastrar aluno\n1 de 3 • Qual é o nome completo do aluno?",
            1 => "CETSADP • Cadastrar aluno\n2 de 3 • Qual é o e-mail? (Opcional)",
            2 => "CETSADP • Cadastrar aluno\n3 de 3 • Qual é o CPF? (Opcional)",
            default => "CETSADP • Revisar cadastro\n"
                .'Nome: '.($dados[0] ?? '')."\n"
                .'E-mail: '.(($dados[1] ?? '-') === '-' ? 'Não informado' : $dados[1])."\n"
                .'CPF: '.(($dados[2] ?? '-') === '-' ? 'Não informado' : 'Informado')."\n\n"
                .'Ao avançar, você receberá a confirmação final. Nenhum aluno foi criado ainda.',
        };

        $botoes = [];
        if ($etapa === 3) {
            $botoes[] = [['text' => 'Preparar confirmação', 'callback_data' => 'fluxo:preparar']];
        } elseif (in_array($etapa, [1, 2], true)) {
            $botoes[] = [['text' => 'Pular esta etapa', 'callback_data' => 'fluxo:pular']];
        }

        if ($etapa > 0) {
            $botoes[] = [['text' => 'Voltar', 'callback_data' => 'fluxo:voltar']];
        }
        $botoes[] = [['text' => 'Cancelar', 'callback_data' => 'fluxo:cancelar']];

        return [
            'text' => ($erro ? $erro."\n\n" : '').$texto."\n\nO rascunho expira após 15 minutos sem atividade.",
            'reply_markup' => ['inline_keyboard' => $botoes],
        ];
    }

    private function chave(string $chatId): string
    {
        return 'telegram:cadastro-guiado:'.hash('sha256', $chatId.'|'.(string) config('services.telegram.admin_user_id'));
    }

    private function salvar(string $chatId, array $estado): void
    {
        Cache::put($this->chave($chatId), Crypt::encryptString(json_encode($estado, JSON_THROW_ON_ERROR)),
            now()->addMinutes(self::MINUTOS));
    }

    private function carregar(string $chatId): ?array
    {
        $texto = Cache::get($this->chave($chatId));
        if (is_string($texto) === false) {
            return null;
        }

        try {
            $estado = json_decode(Crypt::decryptString($texto), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            $this->limpar($chatId);

            return null;
        }

        return is_array($estado) && isset($estado['etapa'], $estado['dados'])
            && is_int($estado['etapa']) && is_array($estado['dados']) ? $estado : null;
    }

    private function limpar(string $chatId): void
    {
        Cache::forget($this->chave($chatId));
    }
}
