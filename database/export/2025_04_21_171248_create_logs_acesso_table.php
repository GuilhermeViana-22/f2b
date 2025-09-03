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

        Schema::create('logs_acesso', function (Blueprint $table) {
            $table->integer('id')->primary()->autoIncrement();
            $table->integer('usuario_id')->index();
            $table->foreign('usuario_id')->references('id')->on('usuarios');
            $table->dateTime('data_hora_acesso')->index();
            $table->enum('tipo_acesso', [""]);
            $table->string('ip', 45);
            $table->string('dispositivo', 100)->nullable();
            $table->string('sistema_operacional', 50)->nullable();
            $table->string('navegador', 50)->nullable();
            $table->timestamp('criado_em')->nullable()->useCurrent();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_acesso');
    }
};
