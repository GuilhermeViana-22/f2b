<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UsersIndexRequest extends FormRequest
{

    public function rules()
    {
        return [

        'page' => 'sometimes|integer|min:1',
        'per_page' => 'sometimes|integer|min:1|max:100',
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|string|email|max:255',
        'ativo' => 'sometimes|boolean',
        'company_id' => 'sometimes|integer|exists:companies,id',
        'position_id' => 'sometimes|integer|exists:positions,id',
        'sort' => 'sometimes|string|in:name,email,created_at,updated_at',
        'order' => 'sometimes|string|in:asc,desc',
        ];
    }
}
