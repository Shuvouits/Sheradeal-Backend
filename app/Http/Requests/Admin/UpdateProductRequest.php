<?php

namespace App\Http\Requests\Admin;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        // Allow partial product updates.
        $rules['title'] = [
            'sometimes',
            'required',
            'string',
            'max:255',
        ];

        $rules['price'] = [
            'sometimes',
            'required',
            'numeric',
            'min:0',
            'decimal:0,2',
        ];

        return $rules;
    }
}
