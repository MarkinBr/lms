<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Matricula;
use App\Models\User;

/**
 * DTO imutável retornado pelo AprovarInscricaoService.
 *
 * Contém APENAS referências aos modelos envolvidos e sinalizadores de fluxo.
 * NUNCA carrega senha em texto puro.
 */
final class AprovarInscricaoResult
{
    public function __construct(
        public readonly Application $application,
        public readonly User $user,
        public readonly Matricula $matricula,
        public readonly bool $usuarioRecemCriado,
        public readonly bool $matriculaRecemCriada,
    ) {
    }
}
