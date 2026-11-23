<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            // Basic Information
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'product_type' => [
                'sometimes',
                Rule::in(['physical', 'digital', 'service']),
            ],
            'overview' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'string'],

            // Organization
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('deleted_at'),
            ],
            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')
                    ->whereNull('deleted_at'),
            ],
            'vendor_name' => ['nullable', 'string', 'max:150'],
            'manufacturer' => ['nullable', 'string', 'max:150'],
            'distributed_by' => ['nullable', 'string', 'max:150'],

            'tags' => ['nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:100', 'distinct'],

            // Status & Publishing
            'status' => [
                'sometimes',
                Rule::in(['active', 'draft', 'archived']),
            ],
            'is_featured' => ['sometimes', 'boolean'],
            'publish_online' => ['sometimes', 'boolean'],
            'publish_pos' => ['sometimes', 'boolean'],

            // Returns & COD
            'is_final_sale' => ['sometimes', 'boolean'],
            'return_window_days' => [
                'nullable', 'integer', 'min:0', 'max:365',
            ],
            'cod_advance_required' => ['sometimes', 'boolean'],
            'cod_advance_amount' => [
                'nullable', 'numeric', 'min:0',
            ],

            // Pricing
            'price' => [
                'required', 'numeric', 'min:0',
                'decimal:0,2',
            ],
            'compare_at_price' => [
                'nullable', 'numeric', 'min:0',
                'decimal:0,2',
            ],
            'cost_per_item' => [
                'nullable', 'numeric', 'min:0',
                'decimal:0,2',
            ],
            'charge_tax' => ['sometimes', 'boolean'],
            'unit_price' => [
                'nullable', 'numeric', 'min:0',
                'decimal:0,4',
            ],
            'unit_price_measurement' => [
                'nullable', 'string', 'max:50',
            ],
            'special_price' => [
                'nullable', 'numeric', 'min:0',
                'decimal:0,2',
            ],
            'special_price_from' => ['nullable', 'date'],
            'special_price_to' => [
                'nullable', 'date',
                'after:special_price_from',
            ],

            // Identifiers
            'sku' => ['nullable', 'string', 'max:150'],
            'barcode' => ['nullable', 'string', 'max:150'],
            'barcode_standard' => [
                'nullable', 'string', 'max:40',
            ],
            'identifier_source' => [
                'nullable', 'string', 'max:50',
            ],

            // Stock configuration
            // Quantity updates will use InventoryService.
            'track_quantity' => ['sometimes', 'boolean'],
            'continue_selling_out_of_stock' => [
                'sometimes', 'boolean',
            ],

            // Quantity rules
            'min_order_quantity' => [
                'sometimes', 'integer', 'min:1', 'max:65535',
            ],
            'quantity_multiple' => [
                'sometimes', 'integer', 'min:1', 'max:65535',
            ],
            'max_order_quantity' => [
                'nullable', 'integer', 'min:1', 'max:65535',
            ],

            // Shipping
            'requires_shipping' => ['sometimes', 'boolean'],
            'weight' => [
                'sometimes', 'numeric', 'min:0',
                'decimal:0,3',
            ],
            'weight_unit' => [
                'sometimes',
                Rule::in(['kg', 'g', 'lb', 'oz']),
            ],
            'length' => [
                'sometimes', 'numeric', 'min:0',
                'decimal:0,2',
            ],
            'width' => [
                'sometimes', 'numeric', 'min:0',
                'decimal:0,2',
            ],
            'height' => [
                'sometimes', 'numeric', 'min:0',
                'decimal:0,2',
            ],
            'dimension_unit' => [
                'sometimes',
                Rule::in(['cm', 'mm', 'in']),
            ],
            'country_of_origin' => [
                'nullable', 'string', 'max:100',
            ],
            'hs_code' => ['nullable', 'string', 'max:30'],
            'customs_description' => ['nullable', 'string'],

            // SEO
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => [
                'nullable', 'string', 'max:160',
            ],

            // External data
            'external_url' => [
                'nullable', 'url:http,https', 'max:2048',
            ],
            'import_source' => [
                'nullable', 'string', 'max:100',
            ],
            'external_id' => [
                'nullable', 'string', 'max:150',
            ],
            'additional_attributes' => [
                'nullable', 'array',
            ],

            'display_order' => [
                'sometimes', 'integer', 'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Product title is required.',
            'price.required' => 'Product price is required.',
            'price.min' => 'Price cannot be negative.',
            'category_id.exists' => 'Selected category does not exist.',
            'brand_id.exists' => 'Selected brand does not exist.',
            'special_price_to.after' =>
                'Discount end date must be after its start date.',
        ];
    }
}
