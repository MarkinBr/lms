<?php

namespace App\Policies;

use App\Models\ApplicationDocument;
use App\Models\User;

/**
 * Autorização de download/visualização de documentos de inscrição.
 *
 * Autodiscovery do Laravel: App\Models\ApplicationDocument resolve
 * automaticamente para App\Policies\ApplicationDocumentPolicy.
 * Não existe AuthServiceProvider neste projeto — não criar um.
 */
class ApplicationDocumentPolicy
{
    /**
     * - Usuário inativo: sempre negado (mesmo sendo dono/secretaria/admin).
     * - Secretaria e admin ativos: acessam qualquer documento de inscrição.
     * - Candidato: só documentos da própria inscrição (vínculo por e-mail,
     *   o mesmo usado em uploadDocument).
     */
    public function download(User $user, ApplicationDocument $document): bool
    {
        if (! $user->ativo) {
            return false;
        }

        $application = $document->application;

        if ($application === null) {
            return false;
        }

        if (in_array($user->nivel, ['secretario', 'admin'], true)) {
            return true;
        }

        return $user->email !== null
            && mb_strtolower((string) $user->email) === mb_strtolower((string) $application->email);
    }
}
