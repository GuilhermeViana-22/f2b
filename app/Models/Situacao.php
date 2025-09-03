<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Situacao extends Model
{
    use HasFactory;

    protected $table = 'situacoes';

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
        return $this->hasMany(User::class, 'situacao_id');
    }

    // Scope para situações ativas
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }
}
