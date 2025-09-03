<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Position extends Model
{
    protected $fillable = [
        'position',
        'level_hierarchical',
        'department',
        'description'
    ];

    /**
     * Relacionamento many-to-many com Permission
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_position')->withTimestamps();
    }
}
