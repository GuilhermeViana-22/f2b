<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetRequest extends FormRequest
{

    /**
     * Obtém as regras de validação que se aplicam à solicitação.
     * @return array
     */
    public function rules()
    {


        return [
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8'
        ];
    }

    /**
     * Obtém as mensagens de erro personalizadas para a validação de regras.
     * @return array
     */
    public function messages()
    {
        return [
            'password.required' => 'A nova senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres',
            'password.confirmed' => 'As senhas não coincidem',
            'password_confirmation.required' => 'Confirme sua nova senha',
            'password_confirmation.min' => 'A confirmação deve ter no mínimo 8 caracteres'
        ];
    }
}
