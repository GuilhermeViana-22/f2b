<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $table = 'status';

    protected $fillable = [
        'nome',
        'descricao',
        'cor',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    // Relacionamento com usuários
    public function users()
    {
        return $this->hasMany(User::class, 'status_id');
    }

    // Scope para status ativos
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }
}
