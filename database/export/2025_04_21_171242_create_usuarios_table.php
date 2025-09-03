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
        Schema::disableForeignKeyConstraints();

        Schema::create('usuarios', function (Blueprint $table) {
            $table->integer('id')->primary()->autoIncrement();
            $table->string('nome_completo', 100);
            $table->string('cpf', 14)->unique();
            $table->string('email_corporativo', 100)->unique();
            $table->string('senha', 255);
            $table->string('telefone_celular', 20)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->enum('genero', [""]);
            $table->date('data_admissao');
            $table->integer('cargo_id')->nullable();
            $table->foreign('cargo_id')->references('id')->on('cargos');
            $table->integer('company_id')->index();
            $table->foreign('company_id')->references('id')->on('unidades');
            $table->integer('status_id')->index();
            $table->foreign('status_id')->references('id')->on('status_usuarios');
            $table->string('foto_perfil', 255)->nullable();
            $table->dateTime('ultimo_acesso')->nullable();
            $table->timestamp('criado_em')->nullable()->useCurrent();
            $table->timestamp('atualizado_em')->nullable()->useCurrent();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
