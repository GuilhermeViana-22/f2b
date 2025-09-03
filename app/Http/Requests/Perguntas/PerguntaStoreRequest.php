<?php

namespace App\Http\Requests\Perguntas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PerguntaStoreRequest extends FormRequest
{

    /**
     * Regras de validação.
     */
    public function rules()
    {
        return [
            'pergunta' => 'required|string|max:255',
            'tipo_pergunta_id' => 'required|integer',
            'tipo_periodicidade_id' => 'required|integer',
        ];
    }

    /**
     * Mensagens de erro personalizadas.
     */
    public function messages()
    {
        return [
            'pergunta.required' => 'O campo "pergunta" é obrigatório.',
            'pergunta.string' => 'O campo "pergunta" deve ser um texto.',
            'pergunta.max' => 'O campo "pergunta" não pode exceder 255 caracteres.',

            'tipo_pergunta_id.required' => 'O tipo de pergunta é obrigatório.',
            'tipo_pergunta_id.integer' => 'O tipo de pergunta deve ser um ID válido.',
            'tipo_pergunta_id.exists' => 'O tipo de pergunta selecionado não existe.',

            'tipo_periodicidade_id.required' => 'A periodicidade é obrigatória.',
            'tipo_periodicidade_id.integer' => 'A periodicidade deve ser um ID válido.',
            'tipo_periodicidade_id.exists' => 'A periodicidade selecionada não existe.',

            'departamento.in' => 'O departamento deve ser: diretoria, gerencia ou operacional.',
        ];
    }

    /**
     * Retorna erros em formato JSON (API REST).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erro na validação dos dados.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
