<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ResponderPorTipoRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'respostas' => 'required|array|min:1',
            'respostas.*.pergunta_id' => [
                'required',
                'integer',
                Rule::exists('perguntas', 'id')->where('tipo_pergunta_id', $this->route('tipoId'))
            ],
            'respostas.*.resposta' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => 'O ID do usuário é obrigatório',
            'respostas.*.pergunta_id.exists' => 'A pergunta não existe para este tipo de questionário'
        ];
    }
}