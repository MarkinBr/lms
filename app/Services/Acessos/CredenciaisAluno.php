<?php

namespace App\Services\Acessos;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CredenciaisAluno
{
    /** O segredo só existe no retorno em memória; nunca deve ir para auditoria. */
    public function emitir(User $ator, User $aluno, string $tipo, string $canal, string $motivo): array
    {
        return DB::transaction(function () use ($ator, $aluno, $tipo, $canal, $motivo) {
            // Mesma ordem de locks do atendimento e da fila: ator, depois aluno.
            $ator = User::query()->lockForUpdate()->find($ator->id);
            $aluno = User::query()->lockForUpdate()->find($aluno->id);
            abort_unless($ator && $aluno, 403);
            AtendimentoAluno::autorizar($ator, $aluno);
            abort_unless($aluno->ativo && $aluno->nivel === 'aluno', 403);
            if (! in_array($tipo, ['ativacao', 'recuperacao', 'temporaria'], true)
                || ! in_array($canal, ['email', 'whatsapp', 'presencial'], true)
                || ($canal === 'presencial' && $tipo !== 'temporaria')) {
                throw ValidationException::withMessages(['tipo' => 'Escolha uma credencial e um canal válidos.']);
            }
            $destino = $this->destino($aluno, $tipo, $canal);
            $anteriores = DB::table('acesso_credenciais')->where('aluno_id', $aluno->id);
            if ((clone $anteriores)->where('created_at', '>', now()->subMinute())->exists()
                || (clone $anteriores)->where('created_at', '>', now()->subHour())->count() >= 10) {
                throw ValidationException::withMessages(['credencial' => 'Aguarde antes de solicitar outra credencial.']);
            }
            AtendimentoAluno::revogar($aluno);
            $aluno->refresh();
            // A senha antiga deixa de autenticar imediatamente, em qualquer canal.
            $aluno->forceFill(['password' => Hash::make(Str::random(80)), 'senha_definitiva_pendente' => true])->save();
            DB::table('acesso_credenciais')->where('aluno_id', $aluno->id)->whereNull('revogada_em')->update(['revogada_em' => now(), 'updated_at' => now()]);
            $segredo = $tipo === 'temporaria' ? str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT) : bin2hex(random_bytes(32));
            $id = (string) Str::uuid();
            $expira = now()->addMinutes($tipo === 'temporaria' ? 30 : 60);
            DB::table('acesso_credenciais')->insert([
                'id' => $id, 'ator_id' => $ator->id, 'aluno_id' => $aluno->id,
                'tipo' => $tipo, 'canal' => $canal, 'segredo_hash' => Hash::make($segredo),
                'destino_hash' => hash('sha256', $destino), 'acesso_versao' => $aluno->acesso_versao,
                'tentativas' => 0, 'expira_em' => $expira, 'created_at' => now(), 'updated_at' => now(),
            ]);
            AtendimentoAluno::registrar($ator, $aluno, 'credencial_emitida', $motivo, ['tipo' => $tipo, 'canal' => $canal]);
            $url = $tipo === 'temporaria' ? route('aluno.assistido.formulario') : route('aluno.assistido.link', $id).'#segredo='.$segredo;
            $instrucao = $tipo === 'temporaria' ? 'Código temporário: '.substr($segredo, 0, 4).' '.substr($segredo, 4).". Acesse:\n".$url : "Defina sua senha pelo link individual:\n".$url;

            return ['id' => $id, 'segredo' => $segredo, 'url' => $url, 'mensagem' => 'CETSADP — Matrícula de acesso: '.$aluno->matricula_login.".\n".$instrucao."\nVálido até ".$expira->copy()->timezone('America/Sao_Paulo')->format('d/m/Y H:i').'. Não compartilhe.', 'expira_em' => $expira, 'canal' => $canal];
        });
    }

    /** Compatibilidade para os jobs: reautoriza sem revelar o segredo. */
    public function vigente(string $id): bool
    {
        return $this->vigenteParaEntrega($id);
    }

    public function vigenteParaEntrega(string $id): bool
    {
        $registro = DB::table('acesso_credenciais')->find($id);
        if (! $registro) {
            return false;
        }
        $aluno = User::find($registro->aluno_id);

        return $aluno && $this->valida($registro, $aluno);
    }

    public function consumirTemporaria(User $aluno, string $codigo): ?array
    {
        return DB::transaction(function () use ($aluno, $codigo) {
            $atual = User::query()->lockForUpdate()->find($aluno->id);
            if (! $atual) {
                return null;
            }
            $registro = DB::table('acesso_credenciais')->where('aluno_id', $atual->id)->where('tipo', 'temporaria')->where('acesso_versao', $atual->acesso_versao)->whereNull('revogada_em')->lockForUpdate()->first();

            return $this->consumir($registro, $atual, str_replace(' ', '', $codigo));
        });
    }

    public function consumirLink(string $id, string $segredo): ?array
    {
        $registro = DB::table('acesso_credenciais')->find($id);
        if (! $registro || $registro->tipo === 'temporaria') {
            return null;
        }

        return DB::transaction(function () use ($registro, $id, $segredo) {
            $aluno = User::query()->lockForUpdate()->find($registro->aluno_id);
            $atual = DB::table('acesso_credenciais')->where('id', $id)->lockForUpdate()->first();

            return $aluno ? $this->consumir($atual, $aluno, $segredo) : null;
        });
    }

    public function validarSessao(array $sessao): bool
    {
        $registro = DB::table('acesso_credenciais')->find($sessao['credencial_id'] ?? '');
        $aluno = User::find($sessao['aluno_id'] ?? 0);

        return $aluno && $registro && $registro->consumida_em !== null
            && (int) ($sessao['acesso_versao'] ?? -1) === (int) $aluno->acesso_versao
            && (int) $registro->aluno_id === (int) $aluno->id
            && $this->valida($registro, $aluno, true);
    }

    public function definirSenha(array $sessao, string $senha, string $confirmacao): void
    {
        validator(
            ['password' => $senha, 'password_confirmation' => $confirmacao],
            ['password' => ['required', 'string', 'max:1024', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()]],
            [
                'password.required' => 'Informe sua nova senha.',
                'password.string' => 'Informe uma senha válida.',
                'password.max' => 'A senha não pode ultrapassar 1024 caracteres.',
                'password.confirmed' => 'A confirmação da senha não corresponde.',
                'password.min' => 'A senha deve ter pelo menos 12 caracteres.',
                'password.mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
                'password.numbers' => 'A senha deve conter pelo menos um número.',
                'password.symbols' => 'A senha deve conter pelo menos um símbolo.',
            ],
            ['password' => 'nova senha', 'password_confirmation' => 'confirmação da senha'],
        )->validate();
        DB::transaction(function () use ($sessao, $senha) {
            $aluno = User::query()->lockForUpdate()->find($sessao['aluno_id'] ?? 0);
            $registro = DB::table('acesso_credenciais')->where('id', $sessao['credencial_id'] ?? '')->lockForUpdate()->first();
            abort_unless($aluno && $registro && $this->validarSessao($sessao), 403, 'Atendimento expirado. Solicite uma nova credencial.');
            $campos = [
                'password' => Hash::make($senha), 'senha_definitiva_pendente' => false,
                'primeiro_acesso_pendente' => false, 'senha_alterada_em' => now(),
                'email_confirmacao_pendente' => null, 'email_confirmacao_token' => null, 'email_confirmacao_expira_em' => null,
            ];
            if ($registro->canal === 'email' && $registro->tipo !== 'temporaria') {
                $campos['email_verified_at'] = now();
            }
            AtendimentoAluno::revogar($aluno);
            $aluno->forceFill($campos)->save();
            DB::table('acesso_credenciais')->where('id', $registro->id)->update(['concluida_em' => now(), 'updated_at' => now()]);
        });
    }

    private function consumir(?object $registro, User $aluno, string $segredo): ?array
    {
        if (! $registro || ! $this->valida($registro, $aluno)) {
            return null;
        }
        // Limite agregado persistente: reemitir não zera os erros da conta.
        $erros = DB::table('acesso_credenciais')->where('aluno_id', $aluno->id)->where('updated_at', '>=', now()->subMinutes(30))->sum('tentativas');
        if ($erros >= 5) {
            return null;
        }
        $formato = $registro->tipo === 'temporaria' ? '/^[0-9]{8}$/D' : '/^[a-f0-9]{64}$/D';
        if (! preg_match($formato, $segredo) || ! Hash::check($segredo, $registro->segredo_hash)) {
            $tentativas = (int) $registro->tentativas + 1;
            DB::table('acesso_credenciais')->where('id', $registro->id)->update(['tentativas' => $tentativas, 'revogada_em' => $tentativas >= 5 ? now() : null, 'updated_at' => now()]);

            return null;
        }
        DB::table('acesso_credenciais')->where('id', $registro->id)->update(['consumida_em' => now(), 'updated_at' => now()]);

        return ['aluno_id' => $aluno->id, 'credencial_id' => $registro->id, 'acesso_versao' => (int) $aluno->acesso_versao];
    }

    private function valida(object $registro, User $aluno, bool $consumida = false): bool
    {
        if (! $aluno->ativo || $aluno->nivel !== 'aluno' || ! $aluno->senha_definitiva_pendente
            || (int) $registro->acesso_versao !== (int) $aluno->acesso_versao
            || $registro->revogada_em !== null || $registro->concluida_em !== null
            || (! $consumida && $registro->consumida_em !== null)
            || (int) $registro->tentativas >= 5 || Carbon::parse($registro->expira_em)->lte(now())) {
            return false;
        }
        try {
            $ator = User::find($registro->ator_id);
            if (! $ator) {
                return false;
            }
            AtendimentoAluno::autorizar($ator, $aluno);

            return hash_equals($registro->destino_hash, hash('sha256', $this->destino($aluno, $registro->tipo, $registro->canal)));
        } catch (ValidationException|HttpException) {
            return false;
        }
    }

    private function destino(User $aluno, string $tipo, string $canal): string
    {
        if ($canal === 'presencial') {
            return 'presencial:'.$aluno->id;
        }
        if ($canal === 'whatsapp') {
            $numero = preg_replace('/\D/', '', (string) $aluno->whatsapp);
            if (! preg_match('/^[1-9][0-9]{9,14}$/D', $numero)) {
                throw ValidationException::withMessages(['whatsapp' => 'Cadastre e confira o WhatsApp do próprio aluno, com código do país.']);
            }

            return $numero;
        }
        $email = mb_strtolower(trim((string) $aluno->email));
        $exclusivo = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->count() === 1;
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || str_ends_with($email, '.local') || ! $exclusivo
            || ($tipo === 'recuperacao' && ! $aluno->email_verified_at)) {
            throw ValidationException::withMessages(['email' => 'Informe um e-mail exclusivo e, para recuperação, já confirmado.']);
        }

        return $email;
    }
}
