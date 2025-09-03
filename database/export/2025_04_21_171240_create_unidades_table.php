<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->integer('id')->primary()->autoIncrement();
            $table->string('nome_unidade', 100);
            $table->string('codigo_unidade', 20)->unique();
            $table->enum('tipo_unidade', [""]);
            $table->string('endereco_rua', 100);
            $table->string('endereco_numero', 20);
            $table->string('endereco_complemento', 50)->nullable();
            $table->string('endereco_bairro', 50);
            $table->string('endereco_cidade', 50);
            $table->char('endereco_estado', 2);
            $table->string('endereco_cep', 10);
            $table->string('telefone_principal', 20);
            $table->string('email_unidade', 100)->nullable();
            $table->date('data_inauguracao')->nullable();
            $table->integer('quantidade_setores')->nullable();
            $table->boolean('ativo')->nullable();
            $table->timestamp('criado_em')->nullable()->useCurrent();
            $table->timestamp('atualizado_em')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
