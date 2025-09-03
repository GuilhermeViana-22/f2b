<?php

namespace App\Http\Requests\Position;

use Illuminate\Foundation\Http\FormRequest;

class PositionStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'position' => 'required|string|max:100',
            'level_hierarchical' => 'required|integer|min:1',
            'department' => 'required|string|max:50',
            'description' => 'nullable|string',

        ];
    }

    public function messages()
    {
        return [
            'position.required' => 'The position field is required.',
            'level_hierarchical.required' => 'The hierarchical level is required.',
            'department.required' => 'The department field is required.',

        ];
    }
}
