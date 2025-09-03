<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'telefone_celular' => $this->telefone_celular,
            'cpf' => $this->cpf,
            'data_nascimento' => $this->data_nascimento ? Carbon::parse($this->data_nascimento)->format('d/m/Y') : null,
            'genero' => $this->genero,
            'position_id' => $this->position_id,
            'company_id' => $this->company_id,
            'status_id' => $this->status_id,
            'situacao_id' => $this->situacao_id,
            'ativo' => $this->ativo,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('d/m/Y H:i:s') : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('d/m/Y H:i:s') : null,

            // Foto em base64
            'foto_perfil' => $this->getFotoBase64(),

            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'company' => $this->company->company
                ];
            }),

            'position' => $this->whenLoaded('position', function () {
                return [
                    'id' => $this->position->id,
                    'position' => $this->position->position,
                    'nivel_hierarquico' => $this->position->nivel_hierarquico,
                    'departamento' => $this->position->departamento,

                    // Lista de permissions (sem whenLoaded)
                    'permissions' => $this->position->relationLoaded('permissions')
                        ? $this->position->permissions->map(function ($permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'description' => $permission->description,
                            ];
                        })
                        : [],
                ];
            }),

            'logs' => $this->whenLoaded('logs', function () {
                return $this->logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'route' => $log->rota,
                        'authenticated' => (bool) $log->autenticado,
                        'created_at' => $log->created_at ? Carbon::parse($log->created_at)->format('d/m/Y H:i:s') : null,
                        'updated_at' => $log->updated_at ? Carbon::parse($log->updated_at)->format('d/m/Y H:i:s') : null
                    ];
                });
            }),

            'token' => $this->when(isset($this->access_token), function () {
                return $this->access_token;
            }),
            'token_type' => $this->when(isset($this->access_token), function () {
                return 'Bearer';
            }),
            'foto_perfil' => $this->getFotoBase64(),
            'expires_at' => $this->when(isset($this->expires_at), function () {
                return $this->expires_at;
            }),
        ];
    }

    private function formatarTelefone($telefone)
    {
        // Remove tudo que não for número
        $telefone = preg_replace('/\D/', '', $telefone);

        // Aplica o formato (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
        if (strlen($telefone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
        } elseif (strlen($telefone) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
        }

        // Retorna sem formatação se não estiver nos formatos esperados
        return $telefone;
    }


    private function getFotoBase64()
    {
        if (!$this->foto_perfil || !Storage::disk('public')->exists($this->foto_perfil)) {
            return null;
        }

        try {
            $file = Storage::disk('public')->get($this->foto_perfil);
            $mime = Storage::disk('public')->mimeType($this->foto_perfil);
            return "data:{$mime};base64," . base64_encode($file);
        } catch (\Exception $e) {
            Log::warning('Erro ao carregar foto_perfil base64', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
