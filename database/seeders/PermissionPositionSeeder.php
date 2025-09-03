<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar a tabela primeiro
        DB::table('permission_position')->truncate();

        // Obter todas as permissões
        $permissions = DB::table('permissions')->get();

        // Obter todos os cargos
        $positions = DB::table('positions')->get();

        $data = [];

        // Para cada cargo, atribuir todas as permissões
        foreach ($positions as $position) {
            foreach ($permissions as $permission) {
                $data[] = [
                    'position_id' => $position->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Inserir os dados na tabela de permissões por cargo
        DB::table('permission_position')->insert($data);

        $this->command->info('Todas as ' . count($permissions) . ' permissões foram atribuídas a todos os ' . count($positions) . ' cargos.');
    }
}
