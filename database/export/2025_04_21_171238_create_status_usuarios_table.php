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
        Schema::create('status_usuarios', function (Blueprint $table) {
            $table->integer('id')->primary()->autoIncrement();
            $table->string('status', 50);
            $table->text('descricao')->nullable();
            $table->boolean('permite_acesso')->nullable();
            $table->timestamp('criado_em')->nullable()->useCurrent();
            $table->timestamp('atualizado_em')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_usuarios');
    }
};
