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

        Schema::create('opcoes_resposta', function (Blueprint $table) {
            $table->integer('id')->primary()->autoIncrement();
            $table->integer('pergunta_id')->index();
            $table->foreign('pergunta_id')->references('id')->on('perguntas');
            $table->string('texto_opcao', 100);
            $table->integer('valor_opcao');
            $table->integer('ordem_exibicao');
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
        Schema::dropIfExists('opcoes_resposta');
    }
};
