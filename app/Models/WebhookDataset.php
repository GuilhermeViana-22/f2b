<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookDataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'filename',
        'path',
        'created_at',
        'project_number',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
