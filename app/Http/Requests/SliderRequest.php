<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post') && !$this->has('_method');

        return [
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'desktop_image' => [
                $creating ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
            'mobile_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_link' => [
                'nullable',
                'string',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if (!preg_match('~^(https?://[^[:space:]]+|/(?!/)[^[:space:]]*)$~i', $value)) {
                        $fail('Use an internal path or an HTTP/HTTPS URL.');
                    }
                },
            ],
            'text_position' => ['nullable', 'in:left,center,right'],
            'text_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'overlay_opacity' => ['nullable', 'integer', 'between:0,100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }
}
