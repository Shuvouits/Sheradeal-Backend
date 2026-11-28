<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => [
                'required',
                'regex:/^01[3-9]\d{8}$/',
            ],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'shipping_location' => [
                'required',
                Rule::in(['inside_dhaka', 'outside_dhaka']),
            ],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'payment_method' => [
                'sometimes',
                Rule::in(['cod']),
            ],

            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => [
                'required',
                'integer',
                'min:1',
                'exists:products,id',
            ],
            'items.*.variant_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_phone.regex' =>
                'Enter a valid Bangladeshi mobile number.',
            'payment_method.in' =>
                'Only Cash on Delivery is available.',
            'items.required' =>
                'Your shopping cart is empty.',
        ];
    }
}
