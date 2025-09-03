<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SituacoesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $situacoes = [
            [
                'id' => 1,
                'nome' => 'Ativo',
                'descricao' => 'Usuário ativo no sistema',
                'cor' => '#10B981', // Verde
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nome' => 'Inativo',
                'descricao' => 'Usuário temporariamente inativo',
                'cor' => '#F59E0B', // Amarelo
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'nome' => 'Suspenso',
                'descricao' => 'Usuário suspenso por violação',
                'cor' => '#EF4444', // Vermelho
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'nome' => 'Férias',
                'descricao' => 'Usuário em período de férias',
                'cor' => '#3B82F6', // Azul
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'nome' => 'Licença Médica',
                'descricao' => 'Usuário afastado por motivo médico',
                'cor' => '#8B5CF6', // Roxo
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('situacoes')->insert($situacoes);
    }
}
