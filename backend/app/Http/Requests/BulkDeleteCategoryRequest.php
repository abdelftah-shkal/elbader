<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => [
                'required',
                'array',
                'min:1',
            ],

            'ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:categories,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Please select at least one category.',

            'ids.array' => 'The selected categories must be an array.',

            'ids.min' => 'Please select at least one category.',

            'ids.*.required' => 'Each category ID is required.',

            'ids.*.integer' => 'Each category ID must be an integer.',

            'ids.*.distinct' => 'Duplicate category IDs are not allowed.',

            'ids.*.exists' => 'One or more selected categories do not exist.',
        ];
    }
}