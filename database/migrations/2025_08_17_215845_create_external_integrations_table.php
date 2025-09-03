<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('base_url');
            $table->string('auth_token_url')->nullable();
            $table->string('data_endpoint');
            $table->json('auth_config'); // clientId, clientSecret, grantType, scope, additionalParams
            $table->json('request_config'); // method, headers, params, body
            $table->enum('data_format', ['json', 'xml']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used')->nullable();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('external_integrations');
    }
}
