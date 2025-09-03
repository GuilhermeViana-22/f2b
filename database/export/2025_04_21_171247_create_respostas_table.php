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

        Schema::create('respostas', function (Blueprint $table) {
            $table->integer('id')->primary()->autoIncrement();
            $table->integer('usuario_id')->index();
            $table->foreign('usuario_id')->references('id')->on('usuarios');
            $table->integer('pergunta_id');
            $table->foreign('pergunta_id')->references('id')->on('perguntas');
            $table->integer('questionario_id');
            $table->foreign('questionario_id')->references('id')->on('questionarios');
            $table->integer('opcao_id')->nullable();
            $table->foreign('opcao_id')->references('id')->on('opcoes_resposta');
            $table->text('resposta_texto')->nullable();
            $table->time('hora_resposta');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('dispositivo', 100)->nullable();
            $table->string('ip', 45)->nullable();
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
        Schema::dropIfExists('respostas');
    }
};
