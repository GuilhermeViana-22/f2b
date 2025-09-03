
<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

// Define a rota para a raiz da aplicação
Route::get('/', function () {
    return view('welcome');
});

Route::get('/testar-email', function () {
    try {
        Mail::raw('Teste de conexão SMTP', function ($message) {
            $message->to('seu-email@pessoal.com')->subject('Teste Hostinger');
        });
        return response()->json(['success' => 'E-mail enviado!']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
