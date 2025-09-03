<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateVerificationCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('verification_codes', function (Blueprint $table) {
            // Adiciona a coluna status (0 = inativo, 1 = ativo)
            $table->tinyInteger('status')->default(1)->after('code');

            // Remove a constraint unique do email se existir
            // (para permitir múltiplos códigos para o mesmo email)
            $table->dropUnique(['email']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('verification_codes', function (Blueprint $table) {
            // Remove a coluna status
            $table->dropColumn('status');

            // Recria a constraint unique do email
            $table->unique('email');
        });
    }
}
