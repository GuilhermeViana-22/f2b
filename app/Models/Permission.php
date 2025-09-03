<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * Relacionamento many-to-many com Positions
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'permission_position')->withTimestamps();
    }
}
