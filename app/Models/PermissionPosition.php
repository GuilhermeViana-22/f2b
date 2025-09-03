<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionPosition extends Model
{
    use HasFactory;

    protected $table = 'permission_position';

    protected $fillable = [
        'permission_id',
        'position_id'
    ];

    /**
     * Relacionamento com Permission
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Relacionamento com Position
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
