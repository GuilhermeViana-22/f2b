<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Flow extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'flows';

    protected $fillable = [
        'name', 'note', 'active', 'user_id', 'company_id',
        'selection_template_id', 'quantities_template_id',
        'external_integration_id', 'webhook_dataset_id'
    ];

    protected $casts = [
        'active' => 'boolean',
        'user_id' => 'integer',
        'company_id' => 'integer'
    ];

    public function steps()
    {
        return $this->hasMany(FlowStep::class, 'flow_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function selectionTemplate()
    {
        return $this->belongsTo(FlowDatasetTemplate::class, 'selection_template_id');
    }

    public function quantitiesTemplate()
    {
        return $this->belongsTo(FlowDatasetTemplate::class, 'quantities_template_id');
    }
}
