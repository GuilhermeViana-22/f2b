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

        Schema::create('perguntas', function (Blueprint $table) {
            $table->integer('id')->primary()->autoIncrement();
            $table->integer('questionario_id')->index();
            $table->foreign('questionario_id')->references('id')->on('questionarios');
            $table->text('enunciado');
            $table->enum('tipo_resposta', [""]);
            $table->boolean('obrigatoria')->nullable();
            $table->integer('ordem_exibicao');
            $table->string('categoria', 50)->nullable();
            $table->boolean('ativo')->nullable();
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
        Schema::dropIfExists('perguntas');
    }
};
