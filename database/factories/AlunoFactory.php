<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Aluno>
 */
class AlunoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Gera uma data de nascimento aleatória
        $dataNascimento = fake()->dateTimeBetween('-40 years', '-10 years');
        
        // Calcula a idade para saber se precisa de responsável
        $idade = \Carbon\Carbon::parse($dataNascimento)->age;
        $ehMenor = $idade < 18;

        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'cpf' => fake()->cpf(false), // Gera CPF válido (false = sem pontuação)
            'telefone' => fake()->cellphoneNumber(),
            'data_nascimento' => $dataNascimento,
            
            // Endereço Fake
            'endereco' => fake()->streetName() . ', ' . fake()->buildingNumber(),
            'bairro' => fake()->word(),
            'cidade' => fake()->city(),
            'estado' => fake()->stateAbbr(),
            'cep' => fake()->postcode(),

            // Se for menor, cria dados do responsável. Se não, deixa NULL.
            'nome_responsavel' => $ehMenor ? fake()->name() : null,
            'telefone_responsavel' => $ehMenor ? fake()->cellphoneNumber() : null,
            'cpf_responsavel' => $ehMenor ? fake()->cpf(false) : null,
            
            'ativo' => true,
        ];
    }
}
