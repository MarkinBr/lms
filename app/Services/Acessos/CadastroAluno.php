<?php

namespace App\Services\Acessos;

use App\Models\Application;
use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CadastroAluno
{
    public const CAMPOS = ['name', 'cpf', 'rg', 'data_nasc', 'genero', 'estado_civil', 'nacionalidade', 'naturalidade_cidade', 'naturalidade_estado', 'email', 'telefone', 'whatsapp', 'cep', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'escolaridade', 'ano_conclusao', 'formacao_teologica', 'instituicao_anterior', 'como_conheceu', 'indicacao_nome'];

    private const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    private const ESCOLARIDADES = ['fundamental_incompleto', 'fundamental_completo', 'medio_incompleto', 'medio_completo', 'superior_incompleto', 'superior_completo', 'pos_graduacao', 'mestrado', 'doutorado'];

    private const ESTADOS_CIVIS = ['Solteiro(a)', 'Casado(a)', 'Viúvo(a)', 'Divorciado(a)', 'Separado(a)', 'União estável'];

    public function validar(array $dados, bool $novo = false): array
    {
        if (array_diff(array_keys($dados), self::CAMPOS)) {
            throw ValidationException::withMessages(['cadastro' => 'O cadastro contém campos que não podem ser alterados neste atendimento.']);
        }
        foreach ($dados as $campo => $valor) {
            if (is_string($valor)) {
                $dados[$campo] = trim($valor) === '' ? null : trim($valor);
            }
        }
        foreach (['email'] as $campo) {
            if (is_string($dados[$campo] ?? null)) {
                $dados[$campo] = mb_strtolower($dados[$campo]);
            }
        }
        foreach (['cpf', 'cep', 'telefone', 'whatsapp'] as $campo) {
            if (is_string($dados[$campo] ?? null)) {
                $normalizado = $campo === 'whatsapp'
                    ? self::normalizarWhatsapp($dados[$campo])
                    : preg_replace('/\D/', '', $dados[$campo]);
                if ($normalizado === '') {
                    throw ValidationException::withMessages([$campo => 'Informe um valor válido para este campo.']);
                }
                $dados[$campo] = $normalizado;
            }
        }
        foreach (['estado', 'naturalidade_estado'] as $campo) {
            if (is_string($dados[$campo] ?? null)) {
                $dados[$campo] = strtoupper($dados[$campo]);
            }
        }
        if (is_string($dados['genero'] ?? null)) {
            $dados['genero'] = ['masculino' => 'M', 'feminino' => 'F', 'outro' => 'Outro'][$dados['genero']] ?? $dados['genero'];
        }
        $regras = array_fill_keys(self::CAMPOS, ['nullable', 'string', 'max:255']);
        $regras['name'] = [$novo ? 'required' : 'sometimes', 'required', 'string', 'min:2', 'max:255'];
        $regras['email'] = ['bail', 'nullable', 'string', 'email:filter', 'max:255'];
        $regras['cpf'] = ['bail', 'nullable', 'string', 'size:11', 'cpf'];
        $regras['data_nasc'] = ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'];
        $regras['genero'] = ['nullable', 'string', Rule::in(['M', 'F', 'Outro'])];
        $regras['estado_civil'] = ['nullable', 'string', Rule::in(self::ESTADOS_CIVIS)];
        $regras['escolaridade'] = ['nullable', 'string', Rule::in(self::ESCOLARIDADES)];
        $regras['como_conheceu'] = ['nullable', 'string', Rule::in(Application::ORIGENS_CONHECIMENTO)];
        $regras['ano_conclusao'] = ['nullable', 'integer', 'min:1900', 'max:'.now()->year];
        $regras['formacao_teologica'] = ['nullable', 'boolean'];
        $regras['telefone'] = ['bail', 'nullable', 'string', 'digits_between:8,15'];
        $regras['whatsapp'] = ['bail', 'nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/D'];
        $regras['cep'] = ['bail', 'nullable', 'string', 'digits:8'];
        $regras['estado'] = $regras['naturalidade_estado'] = ['nullable', 'string', Rule::in(self::UFS)];

        return Validator::make($dados, $regras)->validate();
    }

    public static function normalizarWhatsapp(string $numero): string
    {
        $numero = preg_replace('/\D/', '', $numero);
        if (in_array(strlen($numero), [10, 11], true)) {
            $numero = '55'.$numero;
        }

        return $numero;
    }

    public function criar(User $ator, array $dados, string $motivo): User
    {
        $dados = $this->validar($dados, true);

        return $this->transacionar(function () use ($ator, $dados, $motivo) {
            User::query()->whereKey($ator->id)->lockForUpdate()->firstOrFail();
            AtendimentoAluno::autorizar($ator);
            AtendimentoAluno::reservarEmail($dados['email'] ?? null);
            $this->disponibilidade($dados);
            $aluno = new User;
            $aluno->forceFill($dados + [
                'email' => null, 'nivel' => 'aluno', 'ativo' => true,
                'matricula_login' => 'aluno-'.Str::lower(Str::random(20)),
                'password' => Hash::make(Str::random(64)), 'primeiro_acesso_pendente' => true,
            ])->save();
            AtendimentoAluno::registrar($ator, $aluno, 'cadastro_criado', $motivo, ['campos' => array_keys($dados)]);

            return $aluno;
        }, $dados);
    }

    public function atualizar(User $ator, User $aluno, array $dados, string $estado, string $motivo): User
    {
        $dados = $this->validar($dados);

        return $this->transacionar(function () use ($ator, $aluno, $dados, $estado, $motivo) {
            User::query()->whereKey($ator->id)->lockForUpdate()->firstOrFail();
            $aluno = User::query()->whereKey($aluno->id)->lockForUpdate()->firstOrFail();
            AtendimentoAluno::autorizar($ator, $aluno);
            AtendimentoAluno::conferir($aluno, $estado);
            AtendimentoAluno::reservarEmail($dados['email'] ?? null);
            $this->disponibilidade($dados, $aluno);
            $campos = array_keys($dados);
            if (array_key_exists('email', $dados) && $dados['email'] !== $aluno->email) {
                if ($dados['email'] === null && $aluno->email !== null) {
                    throw ValidationException::withMessages(['email' => 'Para corrigir o e-mail, informe o novo endereço.']);
                }
                AtendimentoAluno::revogar($aluno);
                $aluno->forceFill(['email_confirmacao_pendente' => $dados['email']]);
                unset($dados['email']);
            } elseif (array_key_exists('whatsapp', $dados) && $dados['whatsapp'] !== $aluno->whatsapp) {
                AtendimentoAluno::revogar($aluno);
            }
            $aluno->forceFill($dados)->save();
            AtendimentoAluno::registrar($ator, $aluno, 'cadastro_atualizado', $motivo, ['campos' => $campos]);

            return $aluno->fresh();
        }, $dados, $aluno);
    }

    private function transacionar(Closure $operacao, array $dados, ?User $aluno = null): User
    {
        try {
            return DB::transaction($operacao);
        } catch (UniqueConstraintViolationException $exception) {
            // Só consultar após o rollback: no PostgreSQL, a transação em erro está abortada.
            $this->disponibilidade($dados, $aluno);
            $erroBanco = (string) ($exception->errorInfo[2] ?? $exception->getPrevious()?->getMessage());
            foreach (['email', 'cpf'] as $campo) {
                if (preg_match('/\b(?:users_'.$campo.'_unique|users\.'.$campo.')\b/i', $erroBanco)) {
                    throw ValidationException::withMessages([$campo => 'Este dado foi cadastrado em outro atendimento. Confira o aluno existente.']);
                }
            }

            throw $exception;
        }
    }

    private function disponibilidade(array $dados, ?User $aluno = null): void
    {
        foreach (['email', 'cpf'] as $campo) {
            if (empty($dados[$campo])) {
                continue;
            }
            $consulta = User::query()->when($aluno, fn ($q) => $q->whereKeyNot($aluno->id));
            $consulta = $campo === 'email'
                ? $consulta->where(fn ($q) => $q->whereRaw('LOWER(TRIM(email)) = ?', [$dados[$campo]])->orWhereRaw('LOWER(TRIM(email_confirmacao_pendente)) = ?', [$dados[$campo]]))
                : $consulta->whereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [$dados[$campo]]);
            if ($consulta->exists()) {
                throw ValidationException::withMessages([$campo => 'Este dado já pertence a outro cadastro. Confira o aluno existente.']);
            }
        }
    }
}
