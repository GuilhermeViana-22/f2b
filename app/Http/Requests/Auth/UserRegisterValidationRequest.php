<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UserRegisterValidationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'telefone_celular' => 'required|string|max:20',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|min:8|same:password',

            // Campos opcionais
            'cpf' => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date|before:today',
            'genero' => 'nullable|string|in:masculino,feminino,outro,prefiro não informar',
            'position_id' => 'nullable|integer|exists:positions,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'status_id' => 'nullable|integer|exists:status,id',
            'foto_perfil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ativo' => 'nullable|boolean',
            'situacao_id' => 'nullable|integer'
        ];
    }

    public function messages()
    {
        return [
            // Mensagens para campos obrigatórios
            'name.required' => 'O nome completo é obrigatório',
            'name.string' => 'O nome deve ser um texto',
            'name.max' => 'O nome não pode exceder 255 caracteres',
            
            'email.required' => 'O email é obrigatório',
            'email.email' => 'Digite um email válido',
            'email.max' => 'O email não pode exceder 255 caracteres',
            'email.unique' => 'Este email já está cadastrado',
            
            'telefone_celular.required' => 'O telefone é obrigatório',
            'telefone_celular.string' => 'O telefone deve ser um texto',
            'telefone_celular.max' => 'O telefone não pode exceder 20 caracteres',
            
            'password.required' => 'A senha é obrigatória',
            'password.string' => 'A senha deve ser um texto',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres',
            
            'password_confirmation.required' => 'Confirme sua senha',
            'password_confirmation.string' => 'A confirmação deve ser um texto',
            'password_confirmation.min' => 'A confirmação deve ter no mínimo 8 caracteres',
            'password_confirmation.same' => 'As senhas não coincidem',

            // Mensagens para campos opcionais
            'cpf.string' => 'O CPF deve ser um texto',
            'cpf.max' => 'O CPF não pode exceder 14 caracteres',
            
            'data_nascimento.date' => 'Digite uma data válida',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje',
            
            'genero.string' => 'O gênero deve ser um texto',
            'genero.in' => 'O gênero deve ser: masculino, feminino, outro ou prefiro não informar',
            
            'position_id.integer' => 'O cargo deve ser um número',
            'position_id.exists' => 'O cargo selecionado não existe',
            
            'company_id.integer' => 'A empresa deve ser um número',
            'company_id.exists' => 'A empresa selecionada não existe',
            
            'status_id.integer' => 'O status deve ser um número',
            'status_id.exists' => 'O status selecionado não existe',
            
            'foto_perfil.image' => 'O arquivo deve ser uma imagem válida',
            'foto_perfil.mimes' => 'A imagem deve ser: jpeg, png, jpg ou gif',
            'foto_perfil.max' => 'A imagem não pode exceder 2MB',
            
            'ativo.boolean' => 'O campo ativo deve ser verdadeiro ou falso',
            'situacao_id.integer' => 'A situação deve ser um número'
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validação adicional se necessário
            if ($this->has('password') && $this->has('password_confirmation')) {
                if ($this->password !== $this->password_confirmation) {
                    $validator->errors()->add('password_confirmation', 'As senhas não coincidem');
                }
            }
        });
    }
}
