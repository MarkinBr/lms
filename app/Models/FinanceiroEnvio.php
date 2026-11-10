<?php

namespace App\Models;

use App\Services\Acessos\CadastroAluno;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceiroEnvio extends Model
{
    protected $table = 'financeiro_envios';

    protected $fillable = [
        'mensalidade_id', 'aluno_id', 'acesso_versao', 'criado_por', 'canal', 'modelo', 'status',
        'destinatario', 'agendado_para', 'enviado_em', 'erro',
    ];

    protected $casts = [
        'aluno_id' => 'integer',
        'acesso_versao' => 'integer',
        'agendado_para' => 'datetime',
        'enviado_em' => 'datetime',
    ];

    public static function referenciaContato(?User $aluno, string $canal): string
    {
        return hash('sha256', json_encode([$aluno?->id, $aluno?->acesso_versao, self::destinoAutorizado($aluno, $canal)], JSON_THROW_ON_ERROR));
    }

    public static function destinoAutorizado(?User $aluno, string $canal): ?string
    {
        if (! $aluno || ! $aluno->ativo || $aluno->nivel !== 'aluno') {
            return null;
        }

        if ($canal === 'email' && $aluno->email_verified_at && $aluno->receber_notificacoes_email
            && blank($aluno->email_confirmacao_pendente)) {
            $email = mb_strtolower(trim((string) $aluno->email));

            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }

        if ($canal === 'whatsapp') {
            $numero = CadastroAluno::normalizarWhatsapp((string) $aluno->whatsapp);

            return preg_match('/^[1-9][0-9]{11,14}$/D', $numero) ? $numero : null;
        }

        return null;
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function mensalidade(): BelongsTo
    {
        return $this->belongsTo(Mensalidade::class);
    }
}
