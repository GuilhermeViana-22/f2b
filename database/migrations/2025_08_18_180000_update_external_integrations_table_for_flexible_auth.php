<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateExternalIntegrationsTableForFlexibleAuth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('external_integrations', function (Blueprint $table) {
            // Remove old columns that will be replaced
            $table->dropColumn(['auth_token_url', 'auth_config', 'request_config']);
            
            // Add new flexible authentication structure
            $table->enum('auth_type', [
                'none',           // No authentication
                'api_key_header', // API Key in header
                'api_key_query',  // API Key in query parameter
                'bearer_token',   // Static Bearer token
                'basic_auth',     // Basic authentication (username:password)
                'token_login',    // Email/Password login to get Bearer token
                'oauth2'          // OAuth2 flow (needs token endpoint)
            ])->after('data_endpoint');
            
            // Authentication configuration (flexible JSON)
            $table->json('auth_configuration')->nullable()->after('auth_type');
            
            // OAuth2 specific fields (only used when auth_type = oauth2)
            $table->string('oauth_token_url')->nullable()->after('auth_configuration');
            $table->enum('oauth_grant_type', ['client_credentials', 'authorization_code', 'password'])->nullable()->after('oauth_token_url');
            $table->enum('oauth_token_content_type', ['application/json', 'application/x-www-form-urlencoded'])->default('application/x-www-form-urlencoded')->after('oauth_grant_type');
            
            // Request configuration
            $table->enum('request_method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])->default('GET')->after('oauth_token_content_type');
            $table->enum('request_content_type', ['application/json', 'application/x-www-form-urlencoded', 'multipart/form-data', 'text/plain'])->nullable()->after('request_method');
            $table->json('request_headers')->nullable()->after('request_content_type'); // Custom headers
            $table->json('request_parameters')->nullable()->after('request_headers'); // Query/form parameters
            $table->text('request_body')->nullable()->after('request_parameters'); // Raw body for POST/PUT
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('external_integrations', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn([
                'auth_type',
                'auth_configuration', 
                'oauth_token_url',
                'oauth_grant_type',
                'oauth_token_content_type',
                'request_method',
                'request_content_type',
                'request_headers',
                'request_parameters',
                'request_body'
            ]);
            
            // Restore old columns
            $table->string('auth_token_url')->nullable();
            $table->json('auth_config');
            $table->json('request_config');
        });
    }
}
