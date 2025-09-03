<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        if ($this->has('data_nascimento') && !is_null($this->input('data_nascimento'))) {
            $data = $this->input('data_nascimento');

            // Corrigir data no formato "YYYY-MM-DD"
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                try {
                    $parsed = \Carbon\Carbon::parse($data);
                    $this->merge([
                        'data_nascimento' => $parsed->format('Y-m-d')
                    ]);
                } catch (\Exception $e) {
                    // Ignora e tenta os outros formatos
                }
            }

            // Tentativas alternativas
            $formatos = ['d-m-Y', 'd/m/Y', 'm/d/Y'];
            foreach ($formatos as $formato) {
                try {
                    $parsed = \Carbon\Carbon::createFromFormat($formato, $data);
                    $this->merge([
                        'data_nascimento' => $parsed->format('Y-m-d')
                    ]);
                    break;
                } catch (\Exception $e) {
                    // Continua tentando
                }
            }
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users,email',
            'password'              => ['required', 'confirmed', Password::defaults()],
            'password_confirmation' => 'required',
            'cpf'                   => 'nullable|string|max:14|unique:users,cpf',
            'data_nascimento'       => 'nullable|date',
            'telefone_celular'      => 'nullable|string|max:20',
            'genero'                => 'nullable|string|in:masculino,feminino,outro',
            'position_id'           => 'nullable|exists:positions,id',
            'company_id'            => 'required|exists:companies,id',
            'status_id'             => 'nullable|exists:status,id',
            'situacao_id'           => 'nullable|exists:situacoes,id',
            'foto_perfil'           => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ativo'                 => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required'                  => 'The name field is required.',
            'email.required'                 => 'The email field is required.',
            'email.email'                    => 'The email must be a valid email address.',
            'email.unique'                   => 'This email is already in use.',
            'password.required'               => 'The password field is required.',
            'password.confirmed'              => 'The password confirmation does not match.',
            'password_confirmation.required' => 'The password confirmation field is required.',

            'cpf.unique'                      => 'This CPF is already registered.',
            'telefone_celular.max'            => 'The phone number may not be greater than 20 characters.',
            'genero.in'                       => 'The selected gender is invalid.',
            'position_id.exists'              => 'The selected position is invalid.',

            'company_id.required'             => 'The company_id field is required.',
            'company_id.exists'               => 'The selected company_id is invalid.',

            'status_id.exists'                => 'The selected status is invalid.',
            'situacao_id.exists'              => 'The selected situation is invalid.',

            'foto_perfil.image'               => 'The profile picture must be an image.',
            'foto_perfil.mimes'               => 'The profile picture must be a file of type: jpeg, png, jpg, gif.',
            'foto_perfil.max'                 => 'The profile picture may not be greater than 2MB.',

            'ativo.boolean'                   => 'The active field must be true or false.',
        ];
    }

}
