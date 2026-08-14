<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.string' => 'Category name must be a string.',
            'name.max' => 'Category name cannot exceed 255 characters.',

            'parent_id.integer' => 'Invalid parent category.',
            'parent_id.exists' => 'The selected parent category does not exist.',
        ];
    }
}