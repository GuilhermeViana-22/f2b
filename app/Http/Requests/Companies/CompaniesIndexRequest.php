<?php

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;

class CompaniesIndexRequest extends FormRequest
{
    public function rules()
    {
        return [
            'search' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'abn' => 'nullable|string',
            'admin_email' => 'nullable|email|max:255',
            'invoice_email' => 'nullable|email|max:255',
            'status' => 'nullable|integer|between:0,1',

            // Paginação
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',

            // Data no formato Y-m-d
            'created_at' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function messages()
    {
        return [
            'search.string' => 'The search term must be a string.',
            'search.max' => 'The search term may not be greater than 255 characters.',
            
            'company.string' => 'The company name must be a string.',
            'company.max' => 'The company name may not be greater than 255 characters.',

            'abn.string' => 'The ABN must be a string.',

            'admin_email.email' => 'The admin email must be a valid email address.',
            'admin_email.max' => 'The admin email may not be greater than 255 characters.',

            'invoice_email.email' => 'The invoice email must be a valid email address.',
            'invoice_email.max' => 'The invoice email may not be greater than 255 characters.',

            'status.integer' => 'The status must be an integer.',
            'status.between' => 'The status must be either 0 or 1.',

            'page.integer' => 'The page must be an integer.',
            'page.min' => 'The page must be at least 1.',

            'per_page.integer' => 'The per_page must be an integer.',
            'per_page.min' => 'The per_page must be at least 1.',
            'per_page.max' => 'The per_page may not be greater than 50.',

            'created_at.date_format' => 'The created_at does not match the format Y-m-d.',
        ];
    }
}
