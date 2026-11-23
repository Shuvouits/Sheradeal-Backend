<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user('api')?->role,
            ['admin', 'super_admin'],
            true
        );
    }

    public function rules(): array
    {
        $collectionId = $this->route('collection');

        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('collections', 'slug')
                    ->ignore($collectionId),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::in(['active', 'draft']),
            ],

            'publish_online' => [
                'required',
                'boolean',
            ],

            'publish_pos' => [
                'required',
                'boolean',
            ],

            'collection_type' => [
                'required',
                Rule::in(['manual', 'automated']),
            ],

            'sort_by' => [
                'required',
                Rule::in([
                    'manual',
                    'newest',
                    'price_asc',
                    'price_desc',
                    'title_asc',
                ]),
            ],

            'display_order' => [
                'required',
                'integer',
                'min:0',
            ],

            'rule_category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
            ],

            'rule_brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id'),
            ],

            'rule_min_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'rule_max_price' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:rule_min_price',
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

            'product_ids' => [
                'sometimes',
                'array',
                'max:500',
            ],

            'product_ids.*' => [
                'required',
                'integer',
                'distinct',
                'min:1',
            ],
        ];
    }
}
