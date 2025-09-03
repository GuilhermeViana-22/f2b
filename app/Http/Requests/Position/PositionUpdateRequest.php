<?php

namespace App\Http\Requests\Position;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PositionUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'position' => 'required|string|max:100',
            'level_hierarchical' => 'required|integer|min:1',
            'department' => 'required|string|max:50',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }


    public function messages()
    {
        return [
            'position.required' => 'The position field is required.',
            'position.string' => 'The position field must be a string.',
            'position.max' => 'The position field must not exceed 100 characters.',
            'level_hierarchical.required' => 'The level_hierarchical field is required.',
            'level_hierarchical.integer' => 'The level_hierarchical field must be an integer.',
            'level_hierarchical.min' => 'The level_hierarchical field must be greater than or equal to 1.',
            'department.required' => 'The department field is required.',
            'department.string' => 'The department field must be a string.',
            'department.max' => 'The department field must not exceed 50 characters.',
            'description.string' => 'The description field must be a string.',
            'permissions.array' => 'The permissions must be an array.',
            'permissions.*.integer' => 'Each permission must be an integer.',
            'permissions.*.exists' => 'The permission ID does not exist.',
        ];
    }

    protected function prepareForValidation()
    {
        $input = $this->all();

        // Remove espaços extras do nome
        if (isset($input['name'])) {
            $input['name'] = trim($input['name']);
        }

        // Formata data de nascimento se preenchida e não nula
        if (isset($input['data_nascimento']) && !empty($input['data_nascimento']) && $input['data_nascimento'] !== 'null') {
            $input['data_nascimento'] = $this->formatDate($input['data_nascimento']);
        } else {
            $input['data_nascimento'] = null; // Garante que seja null
        }

        $this->replace($input);
    }

    protected function formatDate($date)
    {
        // Se for null ou vazio, retorna null
        if (empty($date) || $date === 'null') {
            return null;
        }

        // d/m/Y → Y-m-d
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Y-m-d válido
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
