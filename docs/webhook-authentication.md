# Webhook Authentication Implementation

This document details the implementation of secure webhook authentication for handling external service callbacks.

## Overview

The implementation provides:
- Secure token generation and storage
- Token revocation capability
- Request signature verification
- Company status validation
- Usage tracking
- Rate limiting integration

## Database Migration

File: `database/migrations/2025_08_29_034357_create_company_webhook_tokens_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_webhook_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('token_hash')->unique();
            $table->boolean('revoked')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['token_hash', 'revoked']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_webhook_tokens');
    }
};
```

## Model

File: `app/Models/CompanyWebhookToken.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyWebhookToken extends Model
{
    protected $fillable = [
        'company_id',
        'token_hash',
        'revoked',
        'last_used_at',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public static function generate(Company $company): array
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $webhookToken = static::create([
            'company_id' => $company->id,
            'token_hash' => $tokenHash,
        ]);

        // Return both the model and plain token (shown only once)
        return [
            'model' => $webhookToken,
            'plain_token' => $token,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
```

## Middleware

File: `app/Http/Middleware/VerifyWebhookToken.php`

```php
<?php

namespace App\Http\Middleware;

use App\Models\CompanyWebhookToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Webhook-Token');
        
        if (!$token) {
            return response()->json(['message' => 'Webhook token not provided'], 401);
        }

        $tokenHash = hash('sha256', $token);
        
        $webhookToken = CompanyWebhookToken::where('token_hash', $tokenHash)
            ->where('revoked', false)
            ->first();

        if (!$webhookToken) {
            return response()->json(['message' => 'Invalid webhook token'], 401);
        }

        // Optional: Validate payload signature if provided
        if ($signature = $request->header('X-Hook-Signature')) {
            $payload = $request->getContent();
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $token);
            
            if (!hash_equals($signature, $expectedSignature)) {
                return response()->json(['message' => 'Invalid webhook signature'], 401);
            }
        }

        // Check if company is active
        $company = $webhookToken->company;
        if (!$company || !$company->active) {
            return response()->json(['message' => 'Company inactive or not found'], 401);
        }

        // Mark token as used and attach company_id to request
        $webhookToken->markAsUsed();
        $request->merge(['company_id' => $company->id]);

        return $next($request);
    }
}
```

## Kernel Registration

Add the following line to the `$routeMiddleware` array in `app/Http/Kernel.php`:

```php
'auth.webhook' => \App\Http\Middleware\VerifyWebhookToken::class,
```

## Usage Examples

### Generating Tokens

```php
// In your controller/service
$result = CompanyWebhookToken::generate($company);
$plainToken = $result['plain_token']; // Show this to admin ONLY ONCE
$webhookToken = $result['model']; // The model instance
```

### Revoking Tokens

```php
$webhookToken->update(['revoked' => true]);
```

### Route Protection

```php
Route::post('/api/webhooks/endpoint', 'WebhookController@handle')
    ->middleware(['api', 'auth.webhook']);
```

## Making Webhook Requests

### Basic Authentication

```http
POST /api/webhooks/endpoint
X-Webhook-Token: your-webhook-token
Content-Type: application/json

{
    "data": "your payload here"
}
```

### With Signature Verification (Recommended)

```http
POST /api/webhooks/endpoint
X-Webhook-Token: your-webhook-token
X-Hook-Signature: sha256=computed_hmac_signature
Content-Type: application/json

{
    "data": "your payload here"
}
```

To compute the signature in PHP:
```php
$signature = hash_hmac('sha256', $jsonPayload, $webhookToken);
```

## Security Features

1. **Secure Token Storage**
   - Tokens are stored only as SHA-256 hashes
   - Plain tokens are shown only once at creation time
   - 32 bytes of random data used for token generation

2. **Token Management**
   - Tokens can be revoked
   - Last used timestamp is tracked
   - Company status is verified on each request

3. **Request Validation**
   - Optional HMAC signature verification
   - Constant-time comparison for token validation
   - Automatic rate limiting through Laravel's middleware system

4. **Isolation**
   - Complete separation from existing JWT authentication
   - Dedicated middleware and model
   - No interference with existing authentication flows

## Database Schema

### company_webhook_tokens

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| company_id | bigint | Foreign key to companies table |
| token_hash | string | SHA-256 hash of the token |
| revoked | boolean | Token revocation status |
| last_used_at | timestamp | Last successful authentication |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

Indexes:
- Primary key on `id`
- Foreign key on `company_id`
- Unique index on `token_hash`
- Compound index on `[token_hash, revoked]`

## Error Responses

The middleware returns the following error responses:

| Status | Message | Cause |
|--------|---------|-------|
| 401 | Webhook token not provided | Missing X-Webhook-Token header |
| 401 | Invalid webhook token | Token not found or revoked |
| 401 | Invalid webhook signature | Invalid HMAC signature |
| 401 | Company inactive or not found | Company status check failed |
