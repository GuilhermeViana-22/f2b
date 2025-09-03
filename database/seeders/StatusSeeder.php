<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $status = [
            [
                'id' => 1,
                'nome' => 'Administrador',
                'descricao' => 'Usuário com acesso total ao sistema',
                'cor' => '#DC2626', // Vermelho
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nome' => 'Gerente',
                'descricao' => 'Usuário com permissões de gerenciamento',
                'cor' => '#D97706', // Laranja
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'nome' => 'Supervisor',
                'descricao' => 'Usuário com permissões de supervisão',
                'cor' => '#059669', // Verde
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'nome' => 'Operador',
                'descricao' => 'Usuário com permissões operacionais',
                'cor' => '#2563EB', // Azul
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'nome' => 'Visualizador',
                'descricao' => 'Usuário com permissões apenas de visualização',
                'cor' => '#7C3AED', // Roxo
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('status')->insert($status);
    }
}
