<?php

namespace App\Services\Acessos;

use App\Jobs\EntregarAcessoAluno;
use App\Mail\AtendimentoAcessoMail;
use App\Models\User;
use App\Services\Telegram\TransporteEmailTelegram;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class EntregasAcesso
{
    public function preparar(User $ator, User $aluno, string $canal, string $tipo, string $mensagem, DateTimeInterface $expira, array $extra = []): string
    {
        return DB::transaction(function () use ($ator, $aluno, $canal, $tipo, $mensagem, $expira, $extra) {
            $ator = User::query()->lockForUpdate()->findOrFail($ator->id);
            $aluno = User::query()->lockForUpdate()->findOrFail($aluno->id);
            AtendimentoAluno::autorizar($ator, $aluno);
            abort_unless($aluno->ativo && in_array($canal, ['email', 'whatsapp', 'presencial'], true), 403);
            $destino = $this->destino($aluno, $canal, $tipo);
            if ($canal === 'email' && ! app(TransporteEmailTelegram::class)->disponivel()) {
                throw ValidationException::withMessages(['canal' => 'O serviço de e-mail está indisponível. Escolha o atendimento pelo WhatsApp.']);
            }
            $id = (string) Str::uuid();
            DB::table('acesso_entregas')->insert([
                'id' => $id, 'ator_id' => $ator->id, 'aluno_id' => $aluno->id, 'canal' => $canal, 'tipo' => $tipo,
                'estado' => 'preparado', 'versao' => (int) $aluno->acesso_versao, 'destino_hash' => hash('sha256', $destino),
                'conteudo' => Crypt::encryptString(json_encode(['mensagem' => $mensagem, 'destino' => $destino] + $extra, JSON_THROW_ON_ERROR)),
                'expira_em' => $expira, 'created_at' => now(), 'updated_at' => now(),
            ]);
            AtendimentoAluno::registrar($ator, $aluno, 'entrega_preparada', 'Entrega de instruções solicitada no atendimento.', ['canal' => $canal, 'tipo' => $tipo, 'entrega_id' => $id]);

            return $id;
        });
    }

    public function revelar(User $ator, User $aluno, string $id): array
    {
        return DB::transaction(function () use ($ator, $aluno, $id) {
            AtendimentoAluno::autorizar($ator, $aluno);
            $linha = DB::table('acesso_entregas')->where('id', $id)->lockForUpdate()->first();
            abort_unless($linha && (int) $linha->ator_id === $ator->id && (int) $linha->aluno_id === $aluno->id, 404);
            abort_unless($linha->estado === 'preparado' && $linha->visualizado_em === null && $this->vigente($linha), 410, 'Esta entrega já foi exibida ou perdeu a validade. Volte à ficha para iniciar outro atendimento.');
            DB::table('acesso_entregas')->where('id', $id)->update(['visualizado_em' => now(), 'updated_at' => now()]);

            return json_decode(Crypt::decryptString($linha->conteudo), true, flags: JSON_THROW_ON_ERROR) + [
                'canal' => $linha->canal, 'tipo' => $linha->tipo, 'expira_em' => Carbon::parse($linha->expira_em),
            ];
        });
    }

    public function confirmar(User $ator, User $aluno, string $id): ?string
    {
        return DB::transaction(function () use ($ator, $aluno, $id) {
            $ator = User::query()->lockForUpdate()->findOrFail($ator->id);
            $aluno = User::query()->lockForUpdate()->findOrFail($aluno->id);
            AtendimentoAluno::autorizar($ator, $aluno);
            $linha = DB::table('acesso_entregas')->where('id', $id)->lockForUpdate()->first();
            abort_unless($linha && (int) $linha->ator_id === $ator->id && (int) $linha->aluno_id === $aluno->id, 404);
            abort_unless($linha->estado === 'preparado' && $linha->visualizado_em !== null && $this->vigente($linha), 410);
            if ($linha->canal === 'email') {
                DB::table('acesso_entregas')->where('id', $id)->update(['estado' => 'enfileirado', 'updated_at' => now()]);
                Queue::connection('database')->push((new EntregarAcessoAluno($id))->beforeCommit(), '', config('queue.connections.database.queue', 'default'));

                return null;
            }
            $dados = json_decode(Crypt::decryptString($linha->conteudo), true, flags: JSON_THROW_ON_ERROR);
            DB::table('acesso_entregas')->where('id', $id)->update(['estado' => 'abertura_solicitada', 'conteudo' => null, 'updated_at' => now()]);
            AtendimentoAluno::registrar($ator, $aluno, $linha->canal === 'whatsapp' ? 'whatsapp_abertura_solicitada' : 'entrega_presencial_confirmada', $linha->canal === 'whatsapp' ? 'A equipe confirmou destinatário e mensagem. O envio é concluído no WhatsApp.' : 'A equipe confirmou a entrega presencial ao aluno.', ['entrega_id' => $id, 'canal' => $linha->canal]);

            return $linha->canal === 'whatsapp' ? 'https://wa.me/'.$dados['destino'].'?text='.rawurlencode($dados['mensagem']) : null;
        });
    }

    public function enviarEmail(string $id): void
    {
        $original = DB::table('acesso_entregas')->where('id', $id)->first();
        if (! $original) {
            return;
        }
        DB::transaction(function () use ($id, $original) {
            User::query()->whereKey($original->ator_id)->lockForUpdate()->first();
            User::query()->whereKey($original->aluno_id)->lockForUpdate()->first();
            $linha = DB::table('acesso_entregas')->where('id', $id)->lockForUpdate()->first();
            if (! $linha || $linha->estado !== 'enfileirado' || $linha->canal !== 'email') {
                return;
            }
            if (! $this->vigente($linha)) {
                DB::table('acesso_entregas')->where('id', $id)->update(['estado' => 'cancelado', 'conteudo' => null, 'updated_at' => now()]);

                return;
            }
            if (! app(TransporteEmailTelegram::class)->disponivel()) {
                throw new RuntimeException('Transporte de e-mail indisponível no atendimento.');
            }
            $dados = json_decode(Crypt::decryptString($linha->conteudo), true, flags: JSON_THROW_ON_ERROR);
            Mail::to($dados['destino'])->send(new AtendimentoAcessoMail($dados['mensagem']));
            DB::table('acesso_entregas')->where('id', $id)->update(['estado' => 'enviado', 'conteudo' => null, 'updated_at' => now()]);
            AtendimentoAluno::registrar(User::findOrFail($linha->ator_id), User::findOrFail($linha->aluno_id), 'email_aceito_transporte', 'O transporte de e-mail aceitou a mensagem. A chegada à caixa de entrada não foi confirmada.', ['entrega_id' => $id, 'canal' => 'email']);
        });
    }

    public function limparExpiradas(): int
    {
        return DB::table('acesso_entregas')->whereNotNull('conteudo')->where('expira_em', '<=', now())
            ->update(['estado' => 'expirado', 'conteudo' => null, 'updated_at' => now()]);
    }

    private function vigente(object $linha): bool
    {
        $ator = User::find($linha->ator_id);
        $aluno = User::find($linha->aluno_id);
        if (! $ator || ! $ator->ativo || ! in_array($ator->nivel, ['admin', 'secretario'], true)
            || ! $aluno || ! $aluno->ativo || $aluno->nivel !== 'aluno'
            || (int) $aluno->acesso_versao !== (int) $linha->versao
            || ! $linha->conteudo || Carbon::parse($linha->expira_em)->isPast()) {
            return false;
        }
        try {
            $conteudo = json_decode(Crypt::decryptString($linha->conteudo), true, flags: JSON_THROW_ON_ERROR);
            if (! empty($conteudo['credencial_id']) && ! app(CredenciaisAluno::class)->vigenteParaEntrega($conteudo['credencial_id'])) {
                return false;
            }
            if ($linha->tipo === 'confirmar_email' && (! isset($conteudo['email_token_hash'])
                || ! hash_equals((string) $aluno->email_confirmacao_token, $conteudo['email_token_hash']))) {
                return false;
            }

            return hash_equals($linha->destino_hash, hash('sha256', $this->destino($aluno, $linha->canal, $linha->tipo)));
        } catch (ValidationException) {
            return false;
        }
    }

    private function destino(User $aluno, string $canal, string $tipo): string
    {
        if ($canal === 'presencial') {
            return 'presencial';
        }
        $valor = $canal === 'whatsapp' ? (string) $aluno->whatsapp
            : mb_strtolower(trim((string) ($tipo === 'confirmar_email' ? $aluno->email_confirmacao_pendente : $aluno->email)));
        if (($canal === 'whatsapp' && ! preg_match('/^[1-9][0-9]{7,14}$/D', $valor))
            || ($canal === 'email' && ! filter_var($valor, FILTER_VALIDATE_EMAIL))) {
            throw ValidationException::withMessages(['canal' => 'Cadastre e confira o contato do aluno para este canal.']);
        }

        return $valor;
    }
}
