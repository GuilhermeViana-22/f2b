<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Client;

class PassportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Verifique se o cliente pessoal já existe
//        if (!Client::where('personal_access_client', true)->exists()) {
//            Artisan::call('passport:client --personal');
//        }
    }
}
