<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class Log extends Model
{
    use HasFactory;

    // Adicionei o trait HasFactory para facilitar a criação de registros

    protected $table = 'logs';

    // Defina explicitamente se está usando timestamps
    public $timestamps = true; // ou false, dependendo da sua tabela

    protected $fillable = [
        'user_id',
        'ip',
        'name',
        'autenticado',
        'rota',
        // Adicione outros campos que sua tabela possa ter
    ];

    // Adicione casts para campos específicos se necessário
    protected $casts = [
        'autenticado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Método para registrar logs de erro
     */
    public static function logError($exception, $user_id = null, $ip = null, $name = null, $autenticado = false, $rota = null)
    {
        try {
            // Primeiro tenta salvar no log normal
            $log = self::create([
                'user_id' => $user_id,
                'ip' => $ip,
                'name' => $name ?? 'Erro desconhecido',
                'autenticado' => $autenticado,
                'rota' => $rota ?? 'N/A'
            ]);

            // Depois registra no failed_jobs se for uma exceção importante
            DB::table('failed_jobs')->insert([
                'uuid' => Uuid::uuid4()->toString(),
                'connection' => config('database.default'),
                'queue' => 'default',
                'payload' => json_encode([
                    'log_id' => $log->id,
                    'user_id' => $user_id,
                    'ip' => $ip,
                    'name' => $name,
                    'autenticado' => $autenticado,
                    'rota' => $rota
                ]),
                'exception' => (string)$exception,
                'failed_at' => now(),
            ]);

            return $log;
        } catch (\Exception $e) {
            // Fallback: Registrar em um arquivo de log se tudo falhar
            \Log::error('Falha ao registrar log e erro', [
                'original_exception' => (string)$exception,
                'log_error' => $e->getMessage(),
                'context' => [
                    'user_id' => $user_id,
                    'ip' => $ip,
                    'name' => $name
                ]
            ]);

            return false;
        }
    }

    /**
     * Método para registrar logs comuns
     */
    /**
     * Método para registrar logs comuns
     */
    public static function registrar(
        int    $user_id,
        string $ip,
        string $name,
        string $rota,
        bool   $autenticado = true
    )
    {
        try {
            return self::create([
                'user_id' => $user_id,
                'ip' => $ip,
                'name' => $name,
                'autenticado' => $autenticado,
                'rota' => $rota
            ]);
        } catch (\Exception $e) {
            \Log::error('Falha ao registrar log', [
                'error' => $e->getMessage(),
                'context' => [
                    'user_id' => $user_id,
                    'ip' => $ip,
                    'name' => $name
                ]
            ]);

            return false;
        }
    }
}
