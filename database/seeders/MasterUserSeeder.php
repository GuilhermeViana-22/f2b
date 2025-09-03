<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterUserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Diego',
                'cpf' => '000.000.000-01',
                'email' => 'everadapthdg@gmail.com',
                'password' => 'abcd=1234',
                'data_nascimento' => '1985-01-15',
                'telefone_celular' => '(11) 99999-0001',
                'genero' => 'Masculino',
                'position_id' => 1, // General Director
                'company_id' => 1,
                'status_id' => 1,
                'foto_perfil' => null,
                'ultimo_acesso' => now(),
                'ativo' => true,
                'situacao_id' => 1,
            ],
            [
                'name' => 'Gabriel Dias',
                'cpf' => '000.000.000-02',
                'email' => 'gabriel.eduardo.dias@gmail.com',
                'password' => 'abcd=1234',
                'data_nascimento' => '1990-05-22',
                'telefone_celular' => '(11) 99999-0002',
                'genero' => 'Masculino',
                'position_id' => 2, // General User
                'company_id' => 1,
                'status_id' => 1,
                'foto_perfil' => null,
                'ultimo_acesso' => now(),
                'ativo' => true,
                'situacao_id' => 1,
            ],
            [
                'name' => 'Guilherme',
                'cpf' => '000.000.000-03',
                'email' => 'gguicido.viana@gmail.com',
                'password' => 'abcd=1234',
                'data_nascimento' => '1992-09-10',
                'telefone_celular' => '(11) 99999-0003',
                'genero' => 'Masculino',
                'position_id' => 2, // General User
                'company_id' => 1,
                'status_id' => 1,
                'foto_perfil' => null,
                'ultimo_acesso' => now(),
                'ativo' => true,
                'situacao_id' => 1,
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'cpf' => $data['cpf'],
                    'password' => Hash::make($data['password']),
                    'data_nascimento' => $data['data_nascimento'],
                    'telefone_celular' => $data['telefone_celular'],
                    'genero' => $data['genero'],
                    'position_id' => $data['position_id'],
                    'company_id' => $data['company_id'],
                    'status_id' => $data['status_id'],
                    'foto_perfil' => $data['foto_perfil'],
                    'ultimo_acesso' => $data['ultimo_acesso'],
                    'ativo' => $data['ativo'],
                    'situacao_id' => $data['situacao_id'],
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info("Usuário {$user->name} criado ou atualizado.");
        }
    }
}
