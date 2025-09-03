<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    protected $signature = 'test:email';
    protected $description = 'Testar envio de email SMTP';

    public function handle()
    {
        try {
            Mail::raw('Teste de email do sistema', function ($message) {
                $message->to('gguicido.viana@gmail.com')
                    ->subject('Teste SMTP');
            });
            $this->info('✅ Email enviado com sucesso!');
        } catch (\Exception $e) {
            $this->error('❌ Erro: ' . $e->getMessage());
        }
    }
}
