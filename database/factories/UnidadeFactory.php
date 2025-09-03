<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UnidadeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Gerar um código único para a unidade
        $codigoUnidade = 'UN' . Str::upper(Str::random(3)) . rand(100, 999);

        return [
            'nome_unidade' => $this->faker->company,
            'codigo_unidade' => $codigoUnidade,
            'tipo_company_id' => 1,
            'endereco_rua' => $this->faker->streetName,
            'endereco_numero' => $this->faker->buildingNumber,
            'endereco_complemento' => $this->faker->optional(0.3)->secondaryAddress,
            'endereco_bairro' => $this->faker->citySuffix,
            'endereco_cidade' => $this->faker->city,
            'endereco_estado' => $this->faker->stateAbbr,
            'endereco_cep' => str_replace('-', '', $this->faker->postcode),
            'telefone_principal' => $this->faker->phoneNumber,
            'email_unidade' => $this->faker->optional(0.8)->companyEmail,
            'data_inauguracao' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'quantidade_setores' => $this->faker->optional()->numberBetween(1, 20),
            'ativo' => 1, // 90% de chance de ser true
        ];
    }
}
