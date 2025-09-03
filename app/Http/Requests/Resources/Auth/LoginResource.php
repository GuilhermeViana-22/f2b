<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LoginResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'telefone_celular' => $this->telefone_celular,
                'foto_perfil' => $this->foto_perfil ? Storage::url($this->foto_perfil) : null,
            ],
            'company' => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ] : null,
            'position' => $this->position ? [
                'id' => $this->position->id,
                'position' => $this->position->position,
            ] : null,
            'permissions' => $this->position?->permissions->pluck('name'),
            'token' => $this->access_token,
            'token_type' => 'Bearer',
            'expires_at' => $this->expires_at,
            'logs' => $this->log,
        ];
    }
}
