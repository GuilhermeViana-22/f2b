<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('status', function (Blueprint $table) {
            $table->string('nome', 100); // Nome do status
            $table->string('descricao', 255)->nullable(); // Descrição opcional
            $table->string('cor', 7)->nullable(); // Cor para interface (hex)
            $table->boolean('ativo')->default(true); // Se o status está ativo
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('status', function (Blueprint $table) {
            $table->dropColumn(['nome', 'descricao', 'cor', 'ativo']);
        });
    }
}
