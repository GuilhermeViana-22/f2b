<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ExternalIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'base_url',
        'data_endpoint',
        'auth_type',
        'auth_configuration',
        'oauth_token_url',
        'oauth_grant_type',
        'oauth_token_content_type',
        'request_method',
        'request_content_type',
        'request_headers',
        'request_parameters',
        'request_body',
        'data_format',
        'is_active',
        'last_used',
        'company_id',
    ];

    protected $casts = [
        'auth_configuration' => 'array',
        'request_headers' => 'array',
        'request_parameters' => 'array',
        'is_active' => 'boolean',
        'last_used' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
        'data_format' => 'json',
    ];

    /**
     * Get the company that owns the external integration.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to get only active integrations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get integrations for a specific company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Update the last used timestamp.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used' => Carbon::now()]);
    }

    /**
     * Check if the integration is properly configured.
     */
    public function isConfigured(): bool
    {
        $basicRequirements = !empty($this->name) &&
                           !empty($this->base_url) &&
                           !empty($this->data_endpoint) &&
                           !empty($this->auth_type);
        
        if (!$basicRequirements) {
            return false;
        }
        
        // Check auth-specific requirements
        switch ($this->auth_type) {
            case 'oauth2':
                return !empty($this->oauth_token_url) && 
                       !empty($this->oauth_grant_type) &&
                       !empty($this->auth_configuration);
            case 'api_key_header':
            case 'api_key_query':
            case 'bearer_token':
            case 'basic_auth':
                return !empty($this->auth_configuration);
            case 'none':
            default:
                return true;
        }
    }
}
