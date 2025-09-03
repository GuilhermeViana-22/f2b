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

        Schema::create('usuario_permissao', function (Blueprint $table) {
            $table->integer('usuario_id')->primary();
            $table->foreign('usuario_id')->references('id')->on('usuarios');
            $table->integer('permissao_id')->primary();
            $table->foreign('permissao_id')->references('id')->on('permissoes');
            $table->date('data_concessao');
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
        Schema::dropIfExists('usuario_permissao');
    }
};
