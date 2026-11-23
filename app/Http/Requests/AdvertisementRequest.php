<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->role === 'admin'
            || $this->user('api')?->role === 'super_admin';
    }

    public function rules(): array
    {
        $safeLink = function ($attribute, $value, $fail) {
            if (
                !preg_match(
                    '~^(https?://[^[:space:]]+|/(?!/)[^[:space:]]*)$~i',
                    $value
                )
            ) {
                $fail('Use an internal path or HTTP/HTTPS URL.');
            }
        };

        return [
            'name' => ['required', 'string', 'max:255'],
            'badge_text' => ['nullable', 'string', 'max:100'],
            'heading' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],

            'desktop_image' => [
                'nullable',
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

            'background_color' => [
                'nullable',
                'regex:/^#[0-9a-fA-F]{6}$/',
            ],

            'text_color' => [
                'nullable',
                'regex:/^#[0-9a-fA-F]{6}$/',
            ],

            'primary_button_text' => [
                'nullable',
                'string',
                'max:100',
            ],
            'primary_button_link' => [
                'nullable',
                'string',
                'max:2048',
                $safeLink,
            ],

            'secondary_button_text' => [
                'nullable',
                'string',
                'max:100',
            ],
            'secondary_button_link' => [
                'nullable',
                'string',
                'max:2048',
                $safeLink,
            ],

            'display_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],

            'starts_at' => [
                'nullable',
                'date',
            ],
            'ends_at' => [
                'nullable',
                'date',
                'after:starts_at',
            ],
        ];
    }
}
