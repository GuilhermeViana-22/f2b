<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'foto_perfil' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'foto_perfil.required' => 'A foto de perfil é obrigatória.',
            'foto_perfil.image' => 'O arquivo precisa ser uma imagem.',
            'foto_perfil.mimes' => 'A imagem deve ser do tipo JPEG ou PNG.',
            'foto_perfil.max' => 'A imagem deve ter no máximo 2MB (ideal para fotos de perfil).',
        ];
    }
}
