<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GlobalVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->role === 'admin';
    }

    public function rules(): array
    {
        $id = $this->route('variant');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::unique('global_variants', 'slug')->ignore($id),
            ],

            'visual_type' => [
                'required',
                Rule::in([
                    'color',
                    'rectangle',
                    'pill',
                    'dropdown',
                ]),
            ],

            'display_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'options' => [
                'required',
                'array',
                'min:1',
                'max:200',
            ],

            'options.*.id' => [
                'sometimes',
                'integer',
                'distinct',
            ],

            'options.*.label' => [
                'required',
                'string',
                'max:150',
            ],

            'options.*.value' => [
                'nullable',
                'string',
                'max:180',
            ],

            'options.*.color_code' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'options.*.display_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Variant name is required.',
            'visual_type.required' => 'Select a visual type.',
            'options.required' => 'Add at least one option.',
            'options.min' => 'Add at least one option.',
            'options.*.label.required' => 'Option name cannot be empty.',
            'options.*.color_code.regex' => 'Enter a valid HEX color.',
        ];
    }
}
