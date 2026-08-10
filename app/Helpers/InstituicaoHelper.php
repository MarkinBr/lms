<?php

namespace App\Helpers;

class InstituicaoHelper
{
    /**
     * Retorna o endereço completo formatado
     */
   public static function enderecoCompleto(): string
    {
        $end = config('instituicao.endereco');
        
        // Verifica se a configuração existe antes de tentar acessar
        if (!$end) {
            return "Endereço não configurado";
        }
        
        // Usa o operador ?? para garantir que não quebre se faltar uma chave específica
        $rua = $end['rua'] ?? '';
        $numero = $end['numero'] ?? 'S/N';
        
        $endereco = "{$rua}, Nº{$numero}";
        
        if (!empty($end['complemento'])) {
            $endereco .= ", {$end['complemento']}";
        }
        
        $bairro = $end['bairro'] ?? '';
        $cidade = $end['cidade'] ?? '';
        $estado = $end['estado'] ?? '';
        
        $endereco .= ", {$bairro} - {$cidade} - {$estado}";
        
        return $endereco;
    }
    
    /**
     * Retorna o endereço com CEP
     */
    public static function enderecoComCep(): string
    {
        return self::enderecoCompleto() . " | CEP: " . config('instituicao.endereco.cep');
    }
    
    /**
     * Retorna o nome completo da instituição
     */
    public static function nomeCompleto(): string
    {
        return config('instituicao.nome_completo');
    }
    
    /**
     * Retorna o nome extenso da instituição
     */
    public static function nomeExtenso(): string
    {
        return config('instituicao.nome_extenso');
    }
    
    /**
     * Retorna o telefone formatado
     */
    public static function telefone(): string
    {
        return config('instituicao.contatos.telefone');
    }
    
    /**
     * Retorna o email
     */
    public static function email(): string
    {
        return config('instituicao.contatos.email');
    }
    
    /**
     * Retorna o website
     */
    public static function website(): string
    {
        return config('instituicao.contatos.website');
    }
    
    /**
     * Retorna o caminho da logo
     */
    public static function logo(): string
    {
        return asset(config('instituicao.logo.path'));
    }
    
    /**
     * Retorna informações do sistema de validação
     */
    public static function sistemaValidacao(): array
    {
        return config('instituicao.sistema_validacao');
    }
}