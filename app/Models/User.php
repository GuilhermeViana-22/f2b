<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User Model",
 *     description="Modelo de usuário do sistema com status de ativação",
 *     required={"name", "email", "password"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID único do usuário",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nome completo do usuário",
 *         example="João Silva"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="E-mail do usuário",
 *         example="joao@exemplo.com"
 *     ),
 *     @OA\Property(
 *         property="email_verified_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de verificação do e-mail",
 *         nullable=true,
 *         example="2023-01-01 12:00:00"
 *     ),
 *     @OA\Property(
 *         property="active",
 *         type="integer",
 *         description="Status de ativação do usuário (0=Inativo, 1=Ativo)",
 *         enum={0, 1},
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="situacao_id",
 *         type="integer",
 *         description="ID da situação do usuário",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de criação",
 *         example="2023-01-01 12:00:00"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de atualização",
 *         example="2023-01-01 12:00:00"
 *     )
 * )
 */
class User extends Authenticatable implements MustVerifyEmail
{
    // Situações nas quais o usuário se encontra ativo dentro do sistema
    const USUARIO_ATIVO = 1;
    const SITUACAO_ATIVA = 1;

    // Situações nas quais o usuário não se encontra mais ativo dentro do sistema
    const USUARIO_INATIVO = 0;
    const SITUACAO_INATIVO = 0;

    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'password',
        'situacao_id',
        'email',
        'data_nascimento',
        'telefone_celular',
        'position_id',
        'company_id',
        'status_id',
        'foto_perfil',
        'ativo',
        'situacao_id',
        'ultimo_acesso'
    ];

    /**
     * The attributes that should be hidden for serialization.
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function logs()
    {
        // Alterado de client_id para user_id (ajuste padronização)
        return $this->hasMany(Log::class, 'user_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
