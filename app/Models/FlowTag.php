<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class FlowTag extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'flow_tags';

    protected $fillable = [
        'name', 'color', 'description'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relacionamento many-to-many com FlowSteps
    public function flowSteps()
    {
        return $this->belongsToMany(FlowStep::class, null, 'tag_ids', 'step_ids');
    }

    // Scope para buscar tags por nome
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    // Scope para buscar tags por cor
    public function scopeByColor($query, $color)
    {
        return $query->where('color', $color);
    }
}
