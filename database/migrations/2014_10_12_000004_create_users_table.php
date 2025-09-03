<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('cpf', 14)->nullable();
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->date('data_nascimento')->nullable(); // Alterado para date
            $table->string('telefone_celular', 20);
            $table->string('genero', 20)->nullable(); // Alterado para string
            $table->integer('position_id')->nullable(); // Mantido como inteiro sem FK
            $table->integer('company_id')->nullable(); // Mantido como inteiro sem FK
            $table->integer('status_id')->nullable(); // Mantido como inteiro sem FK
            $table->string('foto_perfil', 255)->nullable();
            $table->timestamp('ultimo_acesso')->nullable(); // Alterado para timestamp
            $table->boolean('ativo')->default(true); // Alterado para boolean
            $table->integer('situacao_id')->nullable(); // Mantido como inteiro sem FK
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
