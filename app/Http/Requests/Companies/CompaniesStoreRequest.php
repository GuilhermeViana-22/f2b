<?php

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;

class CompaniesStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'company' => 'required|string|max:255|unique:companies,company',
            'abn' => 'required|string|max:255|unique:companies,abn',
            'admin_email' => 'required|email|max:255|unique:companies,admin_email',
            'invoice_email' => 'required|email|max:255|unique:companies,invoice_email',
            'status' => 'required|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'company.required' => 'Company name is required.',
            'company.string' => 'Company name must be a string.',
            'company.max' => 'Company name cannot exceed 255 characters.',
            'company.unique' => 'This company name is already registered.',

            'abn.required' => 'ABN is required.',
            'abn.string' => 'ABN must be a string.',
            'abn.max' => 'ABN cannot exceed 255 characters.',
            'abn.unique' => 'This ABN is already registered.',

            'admin_email.required' => 'Admin email is required.',
            'admin_email.email' => 'Admin email must be a valid email address.',
            'admin_email.max' => 'Admin email cannot exceed 255 characters.',
            'admin_email.unique' => 'This admin email is already registered.',

            'invoice_email.required' => 'Invoice email is required.',
            'invoice_email.email' => 'Invoice email must be a valid email address.',
            'invoice_email.max' => 'Invoice email cannot exceed 255 characters.',
            'invoice_email.unique' => 'This invoice email is already registered.',

            'status.required' => 'Status is required.',
            'status.integer' => 'Status must be an integer.',
            'status.in' => 'Status must be either 0 or 1.',
        ];
    }
}
