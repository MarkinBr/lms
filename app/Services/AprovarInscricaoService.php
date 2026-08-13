<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * AprovarInscricaoService
 * ------------------------------------------------------------------
 * Fluxo oficial:
 *   Inscrição pública
 *   → análise da Secretaria
 *   → aprovação
 *   → matrícula
 *   → conta de aluno
 *   → e-mail para definir a senha
 *
 * Responsabilidades:
 *   1. Bloquear a inscrição (lockForUpdate) para evitar concorrência.
 *   2. Validar status compatível (em_analise ou aprovado).
 *   3. Localizar User por CPF normalizado, criando se necessário.
 *   4. Detectar conflito de CPF/e-mail sem sobrescrever dados.
 *   5. Criar Matricula única para (inscrição) e sem duplicidade
 *      de aluno/curso ativo.
 *   6. Gerar número de matrícula único.
 *   7. Atualizar status da inscrição para "matriculado".
 *   8. Registrar auditoria (via Log estruturado).
 *   9. Disparar Password Reset APENAS para conta recém-criada,
 *      DEPOIS do commit. Nunca expor senha em texto puro.
 *
 * Idempotência:
 *   Chamada repetida na mesma inscrição já matriculada devolve o
 *   mesmo resultado (User + Matricula existentes) sem duplicar.
 */
class AprovarInscricaoService
{
    public function __construct(
        private readonly NotificarDecisaoInscricaoService $notificador,
    ) {}

    /**
     * Aprova a inscrição e produz a matrícula/conta correspondente.
     *
     * @throws AprovarInscricaoException
     */
    public function executar(
        Application $application,
        User $operador,
        ?string $observacoes = null,
    ): AprovarInscricaoResult {
        // Estado externo à transação: precisamos saber, depois do commit,
        // se devemos ou não disparar o password reset.
        $resultado = DB::transaction(function () use ($application, $operador, $observacoes) {
            // 1) Recarregar com lock — evita corrida entre dois cliques.
            /** @var Application $inscricao */
            $inscricao = Application::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // 2) Idempotência: já matriculada → devolver o que existe.
            if ($inscricao->status === Application::STATUS_MATRICULADO) {
                return $this->reidratarResultadoJaMatriculado($inscricao);
            }

            // 3) Validar status — só em_analise ou aprovado podem virar matrícula.
            if (! in_array($inscricao->status, [
                Application::STATUS_EM_ANALISE,
                Application::STATUS_APROVADO,
            ], true)) {
                throw new AprovarInscricaoException(
                    AprovarInscricaoException::STATUS_INVALIDO,
                    "A inscrição está com status '{$inscricao->status}' e não pode ser aprovada.",
                );
            }

            // 4) Localizar/criar usuário aluno.
            [$user, $usuarioRecemCriado] = $this->localizarOuCriarUsuario($inscricao);

            // 5) Impedir matrícula ativa duplicada para mesmo (user, curso).
            $matriculaAtivaExistente = Matricula::query()
                ->where('user_id', $user->id)
                ->where('curso_id', $inscricao->curso_id)
                ->whereRaw("LOWER(COALESCE(status, '')) IN ('ativo','ativa')")
                ->where(function ($q) use ($inscricao) {
                    // Só bloqueia se a matrícula ativa NÃO for da própria inscrição.
                    $q->whereNull('application_id')
                        ->orWhere('application_id', '!=', $inscricao->id);
                })
                ->lockForUpdate()
                ->first();

            if ($matriculaAtivaExistente) {
                throw new AprovarInscricaoException(
                    AprovarInscricaoException::MATRICULA_DUPLICADA,
                    'Já existe matrícula ativa deste aluno neste curso.',
                );
            }

            // 6) Criar matrícula (ou reaproveitar se já foi criada por esta inscrição).
            $matricula = Matricula::query()
                ->where('application_id', $inscricao->id)
                ->lockForUpdate()
                ->first();

            $matriculaRecemCriada = false;

            if (! $matricula) {
                $matricula = Matricula::query()->create([
                    'user_id' => $user->id,
                    'curso_id' => $inscricao->curso_id,
                    'application_id' => $inscricao->id,
                    'turma_id' => null, // Secretaria define depois.
                    'data_matricula' => now()->toDateString(),
                    'status' => Matricula::STATUS_ATIVA,
                    'numero_matricula' => $this->gerarNumeroMatriculaUnico(),
                    'observacoes' => $observacoes,
                ]);
                $matriculaRecemCriada = true;
            }

            // 7) Atualizar inscrição → matriculado + auditoria simples.
            $inscricao->update([
                'status' => Application::STATUS_MATRICULADO,
                'analisado_por_id' => $operador->id,
                'analisado_em' => now(),
                'observacoes_secretaria' => $observacoes ?? $inscricao->observacoes_secretaria,
            ]);

            // 8) Auditoria estruturada (Log; substituível por qualquer serviço
            // de auditoria já presente no projeto — mantém contrato genérico).
            $this->registrarAuditoria('inscricao.aprovada_e_matriculada', [
                'application_id' => $inscricao->id,
                'user_id' => $user->id,
                'matricula_id' => $matricula->id,
                'numero_matricula' => $matricula->numero_matricula,
                'curso_id' => $inscricao->curso_id,
                'operador_id' => $operador->id,
                'usuario_recem_criado' => $usuarioRecemCriado,
                'matricula_recem_criada' => $matriculaRecemCriada,
            ]);

            return new AprovarInscricaoResult(
                application: $inscricao->refresh(),
                user: $user->refresh(),
                matricula: $matricula->refresh(),
                usuarioRecemCriado: $usuarioRecemCriado,
                matriculaRecemCriada: $matriculaRecemCriada,
            );
        });

        // 9) FORA da transação (após commit): disparar link seguro
        // de definição de senha SOMENTE para conta recém-criada.
        if ($resultado->usuarioRecemCriado) {
            $this->dispararLinkDefinicaoSenha($resultado->user);
        }

        if ($resultado->matriculaRecemCriada) {
            $this->notificador->notificarMatricula(
                $resultado->application,
                $resultado->matricula->numero_matricula,
            );
        }

        return $resultado;
    }

    /**
     * Rejeita a inscrição exigindo justificativa. Não cria conta nem matrícula.
     *
     * @throws AprovarInscricaoException
     */
    public function rejeitar(
        Application $application,
        User $operador,
        string $motivo,
    ): Application {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new AprovarInscricaoException(
                AprovarInscricaoException::STATUS_INVALIDO,
                'A justificativa da rejeição é obrigatória.',
            );
        }

        $inscricaoRejeitada = DB::transaction(function () use ($application, $operador, $motivo) {
            /** @var Application $inscricao */
            $inscricao = Application::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($inscricao->status === Application::STATUS_MATRICULADO) {
                throw new AprovarInscricaoException(
                    AprovarInscricaoException::INSCRICAO_JA_MATRICULADA,
                    'Não é possível rejeitar uma inscrição já matriculada.',
                );
            }

            if (! in_array($inscricao->status, [
                Application::STATUS_EM_ANALISE,
                Application::STATUS_APROVADO,
            ], true)) {
                throw new AprovarInscricaoException(
                    AprovarInscricaoException::STATUS_INVALIDO,
                    "A inscrição está com status '{$inscricao->status}' e não pode ser rejeitada.",
                );
            }

            $inscricao->update([
                'status' => Application::STATUS_REJEITADO,
                'analisado_por_id' => $operador->id,
                'analisado_em' => now(),
                'observacoes_secretaria' => $motivo,
            ]);

            $this->registrarAuditoria('inscricao.rejeitada', [
                'application_id' => $inscricao->id,
                'operador_id' => $operador->id,
                'motivo' => $motivo,
            ]);

            return $inscricao->refresh();
        });

        $this->notificador->notificarRejeicao($inscricaoRejeitada, $motivo);

        return $inscricaoRejeitada;
    }

    /**
     * Localiza um User por CPF normalizado; cria se não existir.
     * Detecta conflitos de CPF ↔ e-mail antes de escrever.
     *
     * @return array{0: User, 1: bool} [user, usuarioRecemCriado]
     *
     * @throws AprovarInscricaoException
     */
    protected function localizarOuCriarUsuario(Application $inscricao): array
    {
        $cpf = $this->normalizarCpf($inscricao->cpf);
        $email = strtolower(trim($inscricao->email));

        $porCpf = User::query()->where('cpf', $cpf)->lockForUpdate()->first();
        $porEmail = User::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();

        // Conflito: e-mail existe em outro CPF, ou CPF existe com outro e-mail.
        if ($porCpf && $porEmail && $porCpf->id !== $porEmail->id) {
            throw new AprovarInscricaoException(
                AprovarInscricaoException::CPF_EMAIL_CONFLITO,
                'Conflito: o CPF e o e-mail da inscrição pertencem a usuários diferentes no sistema.',
            );
        }

        if ($porCpf && ! $porEmail) {
            // CPF já existe, mas com outro e-mail — não sobrescrevemos.
            if (strtolower(trim((string) $porCpf->email)) !== $email) {
                throw new AprovarInscricaoException(
                    AprovarInscricaoException::CPF_EMAIL_CONFLITO,
                    'Já existe um usuário com este CPF cadastrado com outro e-mail. Verifique manualmente antes de matricular.',
                );
            }
        }

        if ($porEmail && ! $porCpf) {
            // E-mail já existe, mas com outro CPF ou sem CPF — não sobrescrevemos CPF alheio.
            $cpfExistente = $this->normalizarCpf((string) $porEmail->cpf);
            if ($cpfExistente !== '' && $cpfExistente !== $cpf) {
                throw new AprovarInscricaoException(
                    AprovarInscricaoException::CPF_EMAIL_CONFLITO,
                    'Já existe um usuário com este e-mail cadastrado com outro CPF. Verifique manualmente antes de matricular.',
                );
            }
        }

        $existente = $porCpf ?? $porEmail;

        if ($existente) {
            return [$existente, false];
        }

        // Criar novo usuário aluno.
        $novo = User::query()->create([
            'name' => $inscricao->nome_completo,
            'email' => $email,
            'cpf' => $cpf,
            'telefone' => $inscricao->telefone,
            'data_nasc' => $inscricao->data_nascimento,
            'cep' => $inscricao->cep,
            'endereco' => $inscricao->logradouro,
            'numero' => $inscricao->numero,
            'complemento' => $inscricao->complemento,
            'bairro' => $inscricao->bairro,
            'cidade' => $inscricao->cidade,
            'estado' => $inscricao->estado,
            'nivel' => 'aluno',
            'ativo' => true,
            // Senha aleatória forte, gravada apenas como hash pelo cast 'hashed'.
            // Nunca retornada, exibida ou logada em texto puro.
            'password' => Str::random(48),
        ]);

        return [$novo, true];
    }

    /**
     * Rehidrata o resultado quando a inscrição já está matriculada
     * (garante idempotência do serviço).
     *
     * @throws AprovarInscricaoException
     */
    protected function reidratarResultadoJaMatriculado(Application $inscricao): AprovarInscricaoResult
    {
        $matricula = Matricula::query()
            ->where('application_id', $inscricao->id)
            ->first();

        if (! $matricula) {
            // Estado inconsistente: inscrição matriculada sem matrícula vinculada.
            throw new AprovarInscricaoException(
                AprovarInscricaoException::INSCRICAO_JA_MATRICULADA,
                'Inscrição marcada como matriculada, mas nenhuma matrícula vinculada foi encontrada. Verifique manualmente.',
            );
        }

        $user = User::query()->findOrFail($matricula->user_id);

        return new AprovarInscricaoResult(
            application: $inscricao,
            user: $user,
            matricula: $matricula,
            usuarioRecemCriado: false,
            matriculaRecemCriada: false,
        );
    }

    /**
     * Gera um número de matrícula único, formato: AAAA-NNNNNN.
     * Colisão altamente improvável; ainda assim é revalidado.
     */
    protected function gerarNumeroMatriculaUnico(): string
    {
        $ano = now()->format('Y');

        for ($i = 0; $i < 5; $i++) {
            $numero = $ano.'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $existe = Matricula::query()->where('numero_matricula', $numero)->exists();
            if (! $existe) {
                return $numero;
            }
        }

        // Fallback: usa uuid curto para garantir unicidade.
        return $ano.'-'.strtoupper(substr((string) Str::uuid(), 0, 8));
    }

    /**
     * Dispara o link seguro de definição de senha via broker nativo do Laravel.
     * Falha do envio não desfaz a matrícula: registra erro e sinaliza a Secretaria.
     */
    protected function dispararLinkDefinicaoSenha(User $user): void
    {
        try {
            $status = Password::broker()->sendResetLink(['email' => $user->email]);

            $this->registrarAuditoria('usuario.link_definicao_senha_enviado', [
                'user_id' => $user->id,
                'status' => (string) $status,
            ]);
        } catch (\Throwable $e) {
            // Não relançamos: a matrícula não pode ser desfeita por falha de e-mail.
            Log::error('Falha ao enviar link de definição de senha para novo aluno.', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);

            $this->registrarAuditoria('usuario.link_definicao_senha_falhou', [
                'user_id' => $user->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normaliza CPF: mantém somente dígitos.
     */
    protected function normalizarCpf(?string $cpf): string
    {
        return preg_replace('/\D/', '', (string) $cpf) ?? '';
    }

    /**
     * Registra evento de auditoria. Se o projeto expuser um serviço
     * dedicado (`AuditLogger` ou similar), este método pode ser
     * facilmente substituído sem alterar o restante do serviço.
     */
    protected function registrarAuditoria(string $evento, array $contexto): void
    {
        Log::channel(config('logging.default'))->info("[AUDITORIA] {$evento}", $contexto);
    }
}
