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
