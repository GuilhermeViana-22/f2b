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

        Schema::create('questionarios', function (Blueprint $table) {
            $table->integer('id')->primary()->autoIncrement();
            $table->string('titulo', 100);
            $table->text('descricao')->nullable();
            $table->enum('periodicidade', [""]);
            $table->date('data_inicio');
            $table->date('data_termino')->nullable();
            $table->boolean('ativo')->nullable();
            $table->enum('publico_alvo', [""]);
            $table->integer('criado_por');
            $table->foreign('criado_por')->references('id')->on('usuarios');
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
        Schema::dropIfExists('questionarios');
    }
};
