<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    protected $table = 'verification_codes';
    protected $fillable = [
        'user_id',
        'email',
        'code',
        'status'
    ];
}
