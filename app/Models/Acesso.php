<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acesso extends Model
{
    use HasFactory;

    protected $table = 'acessos';

    protected $fillable = [
        'user_id',
        'rota',
        'metodo',
        'ip',
        'dispositivo',
        'user_agent',
        'navegador',
        'sistema_operacional',
        'codigo_status',
        'status',
        'tempo_resposta',
        'pais',
        'regiao',
        'ativo',
        'data_acesso',
    ];

    protected $casts = [
        'data_acesso' => 'datetime',
        'ativo' => 'boolean',
        'tempo_resposta' => 'float'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Escopos de consulta
    public function scopeSucesso($query)
    {
        return $query->where('status', 'sucesso');
    }

    public function scopeFalha($query)
    {
        return $query->where('status', 'falha');
    }

    public function scopeRecentes($query, $horas = 24)
    {
        return $query->where('data_acesso', '>=', now()->subHours($horas));
    }

    public function scopePorUsuario($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    public function scopePorIp($query, $ip)
    {
        return $query->where('ip', $ip);
    }
}
