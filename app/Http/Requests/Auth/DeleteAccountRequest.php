<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // Não há regras específicas para deletar conta
            // A autenticação é feita via middleware
        ];
    }

    public function messages()
    {
        return [
            // Mensagens específicas se necessário
        ];
    }
}
