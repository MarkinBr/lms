<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\SsoController;
use ReflectionMethod;

/**
 * CETSADP v9.3.5 — Testes Unitários: Validação de CPF
 * 
 * Testa o algoritmo de validação de CPF implementado no SsoController.
 * Usa Reflection para acessar o método privado validarCpf().
 */
class CpfValidationTest extends TestCase
{
    private $controller;
    private ReflectionMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SsoController();
        $this->method = new ReflectionMethod(SsoController::class, 'validarCpf');
        $this->method->setAccessible(true);
    }

    /**
     * CPFs válidos devem retornar true.
     */
    public function test_cpf_validos(): void
    {
        $cpfsValidos = [
            '52998224725',  // CPF válido gerado
            '11144477735',  // CPF válido gerado
            '71428793860',  // CPF de exemplo válido
        ];

        foreach ($cpfsValidos as $cpf) {
            $this->assertTrue(
                $this->method->invoke($this->controller, $cpf),
                "CPF $cpf deveria ser válido"
            );
        }
    }

    /**
     * CPFs com formato incorreto devem retornar false.
     */
    public function test_cpf_tamanho_incorreto(): void
    {
        $this->assertFalse($this->method->invoke($this->controller, '1234567890'));    // 10 dígitos
        $this->assertFalse($this->method->invoke($this->controller, '123456789012')); // 12 dígitos
        $this->assertFalse($this->method->invoke($this->controller, ''));              // vazio
        $this->assertFalse($this->method->invoke($this->controller, '12345'));         // 5 dígitos
    }

    /**
     * Sequências repetidas devem retornar false.
     */
    public function test_cpf_sequencias_repetidas(): void
    {
        $sequencias = [
            '00000000000',
            '11111111111',
            '22222222222',
            '33333333333',
            '44444444444',
            '55555555555',
            '66666666666',
            '77777777777',
            '88888888888',
            '99999999999',
        ];

        foreach ($sequencias as $cpf) {
            $this->assertFalse(
                $this->method->invoke($this->controller, $cpf),
                "CPF $cpf (sequência repetida) deveria ser inválido"
            );
        }
    }

    /**
     * CPFs com dígitos verificadores incorretos devem retornar false.
     */
    public function test_cpf_digitos_incorretos(): void
    {
        $this->assertFalse($this->method->invoke($this->controller, '52998224726')); // último dígito errado
        $this->assertFalse($this->method->invoke($this->controller, '52998224715')); // ambos errados
        $this->assertFalse($this->method->invoke($this->controller, '12345678901')); // dígitos inválidos
    }

    /**
     * CPFs com caracteres não-numéricos devem ser limpos e validados.
     */
    public function test_cpf_com_formatacao(): void
    {
        $this->assertTrue($this->method->invoke($this->controller, '529.982.247-25'));
        $this->assertTrue($this->method->invoke($this->controller, '111.444.777-35'));
    }
}
