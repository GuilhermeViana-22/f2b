<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Requests\Auth\UserRegisterValidationRequest;
use Illuminate\Http\Request;

class TestRegisterValidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:register-validation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa a validação do registro de usuário';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testando validação do registro...');

        // Dados de teste
        $testData = [
            'name' => 'João da Silva',
            'email' => 'joao@empresa.com',
            'password' => '1Marmaduki&',
            'password_confirmation' => '1Marmaduki&',
            'telefone_celular' => '(11) 99999-9999'
        ];

        // Criar uma requisição simulada
        $request = Request::create('/api/auth/register', 'POST', $testData);
        
        // Criar o request de validação
        $validationRequest = new UserRegisterValidationRequest();
        $validationRequest->merge($testData);

        // Validar
        $validator = validator($testData, $validationRequest->rules(), $validationRequest->messages());

        if ($validator->fails()) {
            $this->error('Validação falhou:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('- ' . $error);
            }
            
            $this->info('Dados enviados:');
            $this->line(json_encode($testData, JSON_PRETTY_PRINT));
            
            return 1;
        }

        $this->info('✅ Validação passou com sucesso!');
        $this->info('Dados válidos:');
        $this->line(json_encode($testData, JSON_PRETTY_PRINT));
        
        return 0;
    }
}
