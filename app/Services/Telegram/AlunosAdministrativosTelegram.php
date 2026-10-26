<?php

namespace App\Services\Telegram;

use App\Jobs\AtivarAlunoTelegram;
use App\Models\User;
use App\Services\Auth\IdentificadorAluno;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class AlunosAdministrativosTelegram implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $alunoId = null, public ?string $estadoRecuperacaoHash = null) {}

    public function backoff(): int
    {
        $broker = config('auth.defaults.passwords', 'users');

        return max(1, (int) config('auth.passwords.'.$broker.'.throttle', 60)) + 1;
    }

    public function handle(): void
    {
        if ($this->alunoId === null || $this->estadoRecuperacaoHash === null) {
            throw new RuntimeException('Solicitação de recuperação inválida.');
        }
        $this->enviarRecuperacao($this->alunoId, $this->estadoRecuperacaoHash);
    }

    public function preparar(string $chatId, string $comando, string $conteudo, int $updateId): array
    {
        return match (ltrim($comando, '/')) {
            'novoaluno' => $this->prepararCadastro($chatId, $conteudo, $updateId),
            'editaraluno' => $this->prepararEdicao($chatId, $conteudo, $updateId),
            'recuperaracesso' => $this->prepararRecuperacao($chatId, $conteudo, $updateId),
            'ativaracesso' => $this->prepararAtivacao($chatId, $conteudo, $updateId),
            default => ['text' => 'Comando de aluno indisponível.'],
        };
    }

    private function prepararCadastro(string $chatId, string $conteudo, int $updateId): array
    {
        $partes = array_map('trim', explode('|', $conteudo));
        if (count($partes) !== 3) {
            return $this->usoCadastro();
        }
        $dados = [
            'name' => $partes[0],
            'email' => $partes[1] === '-' ? null : mb_strtolower($partes[1]),
            'cpf' => $partes[2] === '-' ? null : $partes[2],
        ];
        if (! $this->cadastroValido($dados)) {
            return $this->usoCadastro();
        }
        $dados['cpf'] = $dados['cpf'] === null ? null : preg_replace('/\D/', '', $dados['cpf']);
        if ($this->duplicado($dados)) {
            return ['text' => 'Já existe uma conta com esse CPF ou e-mail. Consulte o cadastro existente.'];
        }
        // Identificador aleatório independente do CPF, e-mail e senha; também impede repetição do cadastro.
        $dados['matricula_login'] = 'aluno-'.Str::lower(Str::random(20));
        $orientacao = $dados['email'] === null
            ? 'Primeiro acesso pendente: procure a secretaria para cadastrar um e-mail e solicitar a ativação.'
            : 'A ativação será enfileirada para o e-mail do cadastro. O envio depende de um serviço de e-mail configurado.';

        return app(ConfirmacoesTelegram::class)->prepararOperacao(
            $chatId, $updateId, 'criar_aluno', $dados,
            "Cadastrar aluno ativo: {$dados['name']}\nMatrícula de acesso: {$dados['matricula_login']}\n{$orientacao}",
            'Cadastrar aluno',
        );
    }

    private function prepararEdicao(string $chatId, string $conteudo, int $updateId): array
    {
        $partes = array_map('trim', explode('|', $conteudo));
        if (count($partes) !== 3 || ! $this->idValido($partes[0]) || ! $this->nomeValido($partes[1])
            || ! $this->telefoneValido($partes[2])) {
            return ['text' => 'Uso: /editaraluno ID | NOME | TELEFONE ou -. Nome de 2 a 255 caracteres; telefone de 8 a 15 dígitos.'];
        }
        $aluno = User::query()->where('nivel', 'aluno')->find((int) $partes[0]);
        if (! $aluno) {
            return ['text' => 'Aluno indisponível.'];
        }
        $telefone = $partes[2] === '-' ? null : preg_replace('/\D/', '', $partes[2]);

        return app(ConfirmacoesTelegram::class)->prepararOperacao(
            $chatId, $updateId, 'editar_aluno', [
                'aluno_id' => $aluno->id, 'estado_hash' => $this->estadoHash($aluno),
                'name' => $partes[1], 'telefone' => $telefone,
            ],
            "Editar aluno #{$aluno->id}\nNome: {$partes[1]}\nTelefone: ".($telefone ?? 'não informado'),
            'Salvar aluno',
        );
    }

    private function prepararRecuperacao(string $chatId, string $conteudo, int $updateId): array
    {
        if (! $this->idValido(trim($conteudo))) {
            return ['text' => 'Uso: /recuperaracesso ALUNO_ID.'];
        }
        $aluno = User::find((int) trim($conteudo));
        if (! $aluno || ! $this->podeRecuperar($aluno)) {
            return ['text' => 'Recuperação indisponível. É necessário aluno ativo com e-mail verificado e exclusivo.'];
        }
        if (! app(TransporteEmailTelegram::class)->disponivel()) {
            return ['text' => 'Recuperação indisponível: configure um serviço de envio de e-mail real. Os transportes array e log não entregam mensagens.'];
        }

        return app(ConfirmacoesTelegram::class)->prepararOperacao(
            $chatId, $updateId, 'recuperar_acesso', [
                'aluno_id' => $aluno->id, 'estado_hash' => $this->estadoHash($aluno),
            ],
            "Solicitar recuperação do aluno #{$aluno->id}? As instruções serão enfileiradas para o e-mail verificado do cadastro.",
            'Solicitar recuperação',
        );
    }

    private function prepararAtivacao(string $chatId, string $conteudo, int $updateId): array
    {
        if (! $this->idValido(trim($conteudo))) {
            return ['text' => 'Uso: /ativaracesso ALUNO_ID.'];
        }
        $aluno = User::find((int) trim($conteudo));
        if (! $aluno || ! $this->podeAtivar($aluno)) {
            return ['text' => 'Ativação indisponível. É necessário aluno ativo com primeiro acesso pendente e e-mail válido, exclusivo e ainda não confirmado.'];
        }
        if (! app(TransporteEmailTelegram::class)->disponivel()) {
            return ['text' => 'Ativação indisponível: configure um serviço de envio de e-mail real. Os transportes array e log não entregam mensagens.'];
        }

        return app(ConfirmacoesTelegram::class)->prepararOperacao(
            $chatId, $updateId, 'ativar_acesso', [
                'aluno_id' => $aluno->id, 'estado_hash' => $this->estadoHash($aluno),
            ],
            "Solicitar ativação do aluno #{$aluno->id}? O link individual para definir a senha e confirmar o e-mail será enviado por e-mail.",
            'Solicitar ativação',
        );
    }

    public function executar(string $acao, array $dados, User $usuario): ?Model
    {
        // A confirmação mantém os locks do vínculo e da proposta até o commit.
        if (DB::transactionLevel() < 1 || ! app(TelegramAdministrativo::class)->podeOperar($usuario->fresh())) {
            return null;
        }
        if ($acao === 'criar_aluno') {
            return $this->criarAluno($dados);
        }
        if (! in_array($acao, ['editar_aluno', 'recuperar_acesso', 'ativar_acesso'], true)
            || ! $this->idValido((string) ($dados['aluno_id'] ?? ''))) {
            return null;
        }
        $aluno = User::query()->lockForUpdate()->find((int) $dados['aluno_id']);
        if (! $aluno || $aluno->nivel !== 'aluno'
            || ! is_string($dados['estado_hash'] ?? null)
            || ! hash_equals($dados['estado_hash'], $this->estadoHash($aluno))) {
            return null;
        }
        if ($acao === 'editar_aluno') {
            if (! $this->nomeValido($dados['name'] ?? '')
                || ! array_key_exists('telefone', $dados)
                || ! $this->telefoneValido($dados['telefone'] ?? '-')) {
                return null;
            }
            $aluno->fill(['name' => $dados['name'], 'telefone' => $dados['telefone']])->save();

            return $aluno;
        }
        if ($acao === 'ativar_acesso') {
            if (! $this->podeAtivar($aluno) || ! app(TransporteEmailTelegram::class)->disponivel()) {
                return null;
            }
            $this->enfileirarAtivacao($aluno);

            return $aluno;
        }
        if (! $this->podeRecuperar($aluno) || ! app(TransporteEmailTelegram::class)->disponivel()) {
            return null;
        }

        $alunoId = (int) $aluno->id;
        $estadoHash = $this->estadoHash($aluno);
        $job = (new self($alunoId, $estadoHash))->beforeCommit();
        // A fila database usa o mesmo banco da confirmação: rollback desfaz também o job.
        // O worker envia o e-mail depois do commit, sem precisar de token ou HTTP Telegram.
        Queue::connection('database')->push($job, '', config('queue.connections.database.queue', 'default'));

        return $aluno;
    }

    private function criarAluno(array $dados): ?User
    {
        if (! $this->cadastroValido($dados)
            || ! is_string($dados['matricula_login'] ?? null)
            || ! preg_match('/^aluno-[a-z0-9]{20}$/D', $dados['matricula_login'])
            || $this->duplicado($dados, true)
            || User::where('matricula_login', $dados['matricula_login'])->lockForUpdate()->exists()) {
            return null;
        }
        try {
            // Savepoint preserva a transação externa em caso de corrida de unicidade no PostgreSQL.
            return DB::transaction(function () use ($dados): User {
                $aluno = User::create([
                    'name' => $dados['name'], 'email' => $dados['email'],
                    'cpf' => $dados['cpf'] === null ? null : preg_replace('/\D/', '', $dados['cpf']),
                    'matricula_login' => $dados['matricula_login'], 'nivel' => 'aluno', 'ativo' => true,
                    'password' => Hash::make(Str::random(64)),
                ]);
                $aluno->forceFill(['primeiro_acesso_pendente' => true])->save();
                $this->enfileirarAtivacao($aluno);

                return $aluno;
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    public function enviarRecuperacao(int $alunoId, string $estadoHash): void
    {
        if (DB::transactionLevel() > 0) {
            throw new RuntimeException('Recuperação deve ser enviada fora da transação administrativa.');
        }
        $aluno = User::find($alunoId);
        if (! $aluno || ! hash_equals($estadoHash, $this->estadoHash($aluno)) || ! $this->podeRecuperar($aluno)) {
            return;
        }
        if (! app(TransporteEmailTelegram::class)->disponivel()) {
            throw new RuntimeException('Configure um serviço de envio de e-mail real antes de processar a recuperação.');
        }
        $status = Password::broker()->sendResetLink([
            'id' => $aluno->id, 'email' => $aluno->email, 'nivel' => 'aluno', 'ativo' => true,
        ]);
        if ($status !== Password::RESET_LINK_SENT) {
            // Falha fica disponível na fila/failed_jobs para retentativa, sem expor destinatário ou token.
            throw new RuntimeException('Não foi possível enviar as instruções de recuperação.');
        }
    }

    private function podeRecuperar(User $aluno): bool
    {
        return $aluno->nivel === 'aluno' && $aluno->ativo && $aluno->email_verified_at !== null
            && is_string($aluno->email) && filter_var($aluno->email, FILTER_VALIDATE_EMAIL) !== false
            && app(IdentificadorAluno::class)->porEmailConfirmado($aluno->email)?->id === $aluno->id;
    }

    public function enfileirarAtivacao(User $aluno): void
    {
        if (DB::transactionLevel() < 1) {
            throw new RuntimeException('A ativação deve ser enfileirada na transação do cadastro ou da matrícula.');
        }
        $atual = User::query()->lockForUpdate()->find($aluno->id);
        if (! $atual || ! $this->podeAtivar($atual)) {
            return;
        }
        $job = (new AtivarAlunoTelegram(
            (int) $atual->id, hash('sha256', mb_strtolower(trim($atual->email))),
        ))->beforeCommit();
        Queue::connection('database')->push($job, '', config('queue.connections.database.queue', 'default'));
    }

    private function podeAtivar(User $aluno): bool
    {
        return $aluno->nivel === 'aluno' && $aluno->ativo && $aluno->primeiro_acesso_pendente
            && $aluno->email_verified_at === null && is_string($aluno->email)
            && filter_var($aluno->email, FILTER_VALIDATE_EMAIL) !== false
            && User::whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($aluno->email))])->count() === 1;
    }

    private function cadastroValido(array $dados): bool
    {
        return $this->nomeValido($dados['name'] ?? '')
            && array_key_exists('email', $dados) && array_key_exists('cpf', $dados)
            && ($dados['email'] === null || (is_string($dados['email']) && trim($dados['email']) !== ''))
            && ($dados['cpf'] === null || (is_string($dados['cpf']) && trim($dados['cpf']) !== ''))
            && Validator::make($dados, [
                'email' => ['nullable', 'string', 'email:filter', 'max:255'],
                'cpf' => ['nullable', 'string', 'regex:/^(?:[0-9]{11}|[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2})$/D', 'cpf'],
            ])->passes();
    }

    private function duplicado(array $dados, bool $bloquear = false): bool
    {
        if ($dados['email'] === null && $dados['cpf'] === null) {
            return false;
        }
        $query = User::query()->where(function (Builder $query) use ($dados): void {
            if ($dados['email'] !== null) {
                $query->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($dados['email']))]);
            }
            if ($dados['cpf'] !== null) {
                $query->orWhereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [preg_replace('/\D/', '', $dados['cpf'])]);
            }
        });
        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    private function nomeValido(mixed $nome): bool
    {
        return is_string($nome) && trim($nome) === $nome && mb_strlen($nome) >= 2 && mb_strlen($nome) <= 255
            && preg_match('/[\x00-\x1F\x7F]/u', $nome) === 0;
    }

    private function telefoneValido(mixed $telefone): bool
    {
        return $telefone === '-' || (is_string($telefone) && strlen($telefone) <= 30
            && preg_match('/^\+?[0-9 ()-]+$/D', $telefone) === 1
            && strlen(preg_replace('/\D/', '', $telefone)) >= 8
            && strlen(preg_replace('/\D/', '', $telefone)) <= 15);
    }

    private function idValido(string $id): bool
    {
        return preg_match('/^[1-9][0-9]*$/D', $id) === 1
            && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
    }

    private function estadoHash(Model $registro): string
    {
        $atributos = $registro->getAttributes();
        ksort($atributos);

        return hash('sha256', json_encode($atributos, JSON_THROW_ON_ERROR));
    }

    private function usoCadastro(): array
    {
        return ['text' => 'Uso: /novoaluno NOME | EMAIL ou - | CPF ou -. Nome de 2 a 255 caracteres; e-mail e CPF devem ser válidos e exclusivos.'];
    }
}
