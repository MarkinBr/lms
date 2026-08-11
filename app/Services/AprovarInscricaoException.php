<?php

namespace App\Services;

use RuntimeException;

/**
 * Exceção de domínio lançada pelo AprovarInscricaoService quando
 * a operação não pode ser concluída de forma segura.
 *
 * Códigos possíveis:
 *  - STATUS_INVALIDO       : inscrição não está em estado que permita aprovação
 *  - CPF_EMAIL_CONFLITO    : CPF/e-mail bate com usuário existente de forma inconsistente
 *  - MATRICULA_DUPLICADA   : já existe matrícula ativa do mesmo usuário no mesmo curso
 *  - INSCRICAO_JA_MATRICULADA : a própria inscrição já gerou uma matrícula
 */
class AprovarInscricaoException extends RuntimeException
{
    public const STATUS_INVALIDO = 'STATUS_INVALIDO';
    public const CPF_EMAIL_CONFLITO = 'CPF_EMAIL_CONFLITO';
    public const MATRICULA_DUPLICADA = 'MATRICULA_DUPLICADA';
    public const INSCRICAO_JA_MATRICULADA = 'INSCRICAO_JA_MATRICULADA';

    public function __construct(
        public readonly string $codigoDominio,
        string $message,
    ) {
        parent::__construct($message);
    }
}
