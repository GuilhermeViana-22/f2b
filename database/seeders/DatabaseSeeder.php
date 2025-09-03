<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AcessoSeeder::class,
            LogSeeder::class,
            PermissionSeeder::class,
            PositionSeeder::class,
            PermissionPositionSeeder::class, // Deve vir após PermissionSeeder e PositionSeeder
            PassportSeeder::class,
            CompanySeeder::class,
            // FlowSeeder::class,
            // UserSeeder::class,
            MasterUserSeeder::class
        ]);
    }
}
