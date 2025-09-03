<?php

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;

class CompaniesUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company' => 'sometimes|string|max:255',
            'abn' => 'sometimes|string|max:255',
            'admin_email' => 'sometimes|email|max:255',
            'invoice_email' => 'sometimes|email|max:255',
            'status' => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'company.string' => 'Company name must be a string.',
            'company.max' => 'Company name cannot exceed 255 characters.',

            'abn.string' => 'ABN must be a string.',
            'abn.max' => 'ABN cannot exceed 255 characters.',

            'admin_email.email' => 'Admin email must be a valid email address.',
            'admin_email.max' => 'Admin email cannot exceed 255 characters.',

            'invoice_email.email' => 'Invoice email must be a valid email address.',
            'invoice_email.max' => 'Invoice email cannot exceed 255 characters.',

            'status.integer' => 'Status must be an integer.',
            'status.in' => 'Status must be either 0 or 1.',
        ];
    }
}
