<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    public function rules()
    {
        $userId = $this->route('id');

        return [
            'name'                  => 'sometimes|string|max:255',
            'email'                 => 'sometimes|email|max:255|unique:users,email,' . $userId,
            'cpf'                   => 'nullable|string|max:14|unique:users,cpf,' . $userId,
            'data_nascimento'       => 'nullable',
            'telefone_celular'      => 'nullable|string|max:20',
            'genero'                => 'nullable|string|in:masculino,feminino,outro',
            'position_id'           => 'nullable|exists:positions,id',
            'company_id'            => 'sometimes|exists:companies,id',
            'status_id'             => 'nullable|exists:status,id',
            'situacao_id'           => 'nullable|exists:situacoes,id',
            'foto_perfil'           => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ativo'                 => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.string'  => 'The name must be a valid string.',
            'name.max'     => 'The name may not be greater than 255 characters.',

            'email.email'  => 'The email must be a valid email address.',
            'email.unique' => 'This email is already in use.',

            'cpf.unique'  => 'This CPF is already registered.',
            'cpf.max'     => 'The CPF may not be greater than 14 characters.',

            'telefone_celular.max' => 'The phone number may not be greater than 20 characters.',

            'genero.in' => 'The selected gender is invalid.',

            'position_id.exists' => 'The selected position is invalid.',
            'company_id.exists'  => 'The selected company is invalid.',
            'status_id.exists'   => 'The selected status is invalid.',
            'situacao_id.exists' => 'The selected situation is invalid.',

            'foto_perfil.image' => 'The profile picture must be an image.',
            'foto_perfil.mimes' => 'The profile picture must be a file of type: jpeg, png, jpg, gif.',
            'foto_perfil.max'   => 'The profile picture may not be greater than 2MB.',

            'ativo.boolean' => 'The active field must be true or false.',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'cpf' => 'CPF',
            'data_nascimento' => 'data de nascimento',
            'telefone_celular' => 'telefone celular',
            'genero' => 'gênero',
            'position_id' => 'cargo',
            'company_id' => 'empresa',
            'status_id' => 'status',
            'situacao_id' => 'situação',
            'foto_perfil' => 'foto de perfil',
            'ativo' => 'ativo',
        ];
    }
}
