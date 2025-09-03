<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unidade; // Certifique-se de usar o namespace correto do seu modelo Unidade

class UnidadeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Unidade::create([
            'company' => 'EverAdapt',
            'situacao' => 'ativa',
            'ativo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
