<?php


// app/Http/Requests/Users/UserRoleRequest.php
namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

class UserRoleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'role' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!Role::where('name', $value)->exists()) {
                        $fail("The selected role is invalid.");
                    }
                }
            ]
        ];
    }
}