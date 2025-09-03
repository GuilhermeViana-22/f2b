<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // User Permissions
        Permission::create(['name' => 'view-users', 'description' => 'Permission to read/view Users']);
        Permission::create(['name' => 'create-users', 'description' => 'Permission to create a new User']);
        Permission::create(['name' => 'edit-users', 'description' => 'Permission to update existing Users data']);
        Permission::create(['name' => 'delete-users', 'description' => 'Permission to delete Users']);

        // Position Permissions
        Permission::create(['name' => 'view-positions', 'description' => 'Permission to read/view Positions']);
        Permission::create(['name' => 'create-positions', 'description' => 'Permission to create a new Positions']);
        Permission::create(['name' => 'edit-positions', 'description' => 'Permission to update existing positions data']);
        Permission::create(['name' => 'delete-positions', 'description' => 'Permission to delete positions']);

        // Adicionando novas permissões
        Permission::create(['name' => 'generate_reports', 'description' => 'Permission to generate reports']);
        Permission::create(['name' => 'export_files', 'description' => 'Permission to export files']);
        Permission::create(['name' => 'download_system_info', 'description' => 'Permission to download system data']);

    }
}
