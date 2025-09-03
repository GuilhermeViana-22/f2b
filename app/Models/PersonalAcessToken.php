<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAcessToken extends Model
{
    protected $table = 'personal_access_tokens';
    protected $fillable = [
        'tokenable',
        'tokenable_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
    ];
}
