<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company',
        'abn',
        'admin_email',
        'invoice_email',
        'status',
    ];

    protected $casts = [
        'status' => 'integer', // garante que 0/1 seja tratado como número
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'company_id');
    }
}
