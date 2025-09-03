<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Criar a empresa padrão do sistema - EverAdapt
        $company = Company::create([
            'company' => 'EverAdapt',
            'abn' => '12345678901', // ABN fictício para exemplo
            'admin_email' => 'admin@everadapt.com',
            'invoice_email' => 'billing@everadapt.com',
            'status' => 1,
        ]);
        
        $this->command->info('Company created: ' . $company->company . ' (ID: ' . $company->id . ')');
    }
}
