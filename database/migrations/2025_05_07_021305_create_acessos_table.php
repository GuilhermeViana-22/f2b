<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcessosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acessos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Nullable para acessos não autenticados');

            // Informações básicas do acesso
            $table->string('rota', 255);
            $table->string('metodo', 10)->default('GET')->comment('Método HTTP: GET, POST, PUT, DELETE, etc.');
            $table->ipAddress('ip')->comment('Endereço IP do cliente');

            // Informações do dispositivo
            $table->enum('dispositivo', ['desktop', 'mobile', 'tablet', 'outro'])->default('desktop');
            $table->text('user_agent')->nullable()->comment('Agente do usuário completo');
            $table->string('navegador', 50)->nullable()->comment('Chrome, Firefox, Safari, etc.');
            $table->string('sistema_operacional', 50)->nullable()->comment('Windows, Linux, macOS, iOS, Android');

            // Status e metadados
            $table->smallInteger('codigo_status')->default(200)->comment('Código HTTP de resposta');
            $table->enum('status', ['sucesso', 'falha', 'negado', 'erro'])->default('sucesso');
            $table->float('tempo_resposta')->nullable()->comment('Tempo de resposta em segundos');

            // Localização
            $table->string('pais', 100)->nullable();
            $table->string('regiao', 100)->nullable();

            // Controle
            $table->boolean('ativo')->default(true);
            $table->timestamp('data_acesso')->useCurrent();

            $table->timestamps();

            // Chave estrangeira
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Índices
            $table->index('user_id');
            $table->index('rota');
            $table->index('ip');
            $table->index('data_acesso');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('acessos');
    }
}
