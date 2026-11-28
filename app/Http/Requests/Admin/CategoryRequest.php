<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->role === 'admin';
    }

    public function rules(): array
    {
        $id = $this->route('category');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($id),
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('deleted_at'),
            ],

            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive']),
            ],

            'is_featured' => [
                'sometimes',
                'boolean',
            ],

            'show_in_megamenu' => [
    'sometimes',
    'boolean',
],

            'display_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'tax_class' => [
                'nullable',
                'string',
                'max:100',
            ],

            'meta_title' => [
                'nullable',
                'string',
                'max:70',
            ],

            'meta_description' => [
                'nullable',
                'string',
                'max:160',
            ],

            'tags' => [
                'nullable',
                'array',
                'max:20',
            ],

            'tags.*' => [
                'string',
                'max:50',
                'distinct',
            ],
        ];
    }
}
