<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Generator as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Faker $faker)
    {

        // Criando 30 usuários
        foreach (range(1, 30) as $index) {
            User::create([
                'name' => $faker->name,
                'password' => Hash::make('password123'), // Senha fixa para todos os usuários
                'cpf' => $faker->numerify('###########'), // Gerar um CPF com 11 números
                'email' => $faker->unique()->userName.'@empresa.com',
                'data_nascimento' => $faker->date(),
                'telefone_celular' => $faker->phoneNumber,
                'genero' => $faker->randomElement(['masculino', 'feminino', 'outro']),
                'position_id' => $faker->numberBetween(1, 2), // Exemplo, ajuste conforme seus cargos
                'company_id' => $faker->numberBetween(1, 5), // Exemplo, ajuste conforme suas unidades
                'status_id' => $faker->numberBetween(1, 3), // Exemplo, ajuste conforme seus status
                'situacao_id' => $faker->numberBetween(1, 2), // Exemplo, ajuste conforme suas situações
                'foto_perfil' => null,
                'ativo' => 1
            ]);
        }
    }
}
