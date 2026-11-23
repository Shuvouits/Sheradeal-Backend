<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->role === 'admin';
    }

    public function rules(): array
    {
        $brandId = $this->route('brand');

        $isUpdate = in_array(
            $this->method(),
            ['PUT', 'PATCH']
        );

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
                Rule::unique('brands', 'slug')
                    ->ignore($brandId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'website' => [
                'nullable',
                'url:http,https',
                'max:255',
            ],

            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'archived']),
            ],

            'source' => [
                'sometimes',
                Rule::in(['official', 'vendor']),
            ],

            'approval_status' => [
                'sometimes',
                Rule::in(['pending', 'approved', 'rejected']),
            ],

            'is_featured' => [
                'sometimes',
                'boolean',
            ],

            'display_order' => [
                'sometimes',
                'integer',
                'min:0',
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
        ];
    }
}
