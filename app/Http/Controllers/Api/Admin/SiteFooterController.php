<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteFooterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteFooterController extends Controller
{
    public function __construct(
        private SiteFooterService $service
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->get(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:100'],
            'brand_tagline' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'community_title' => ['nullable', 'string', 'max:100'],

            'trust_badges' => ['required', 'array', 'max:4'],
            'trust_badges.*.icon' => [
                'required', 'string',
                'in:shield,truck,wallet,refresh,star,package,headphones',
            ],
            'trust_badges.*.title' => ['required', 'string', 'max:100'],
            'trust_badges.*.subtitle' => ['nullable', 'string', 'max:180'],

            'link_columns' => ['required', 'array', 'max:3'],
            'link_columns.*.title' => ['required', 'string', 'max:100'],
            'link_columns.*.links' => ['required', 'array', 'max:15'],
            'link_columns.*.links.*.label' => [
                'required', 'string', 'max:100',
            ],
            'link_columns.*.links.*.url' => [
                'required', 'string', 'max:500',
                'regex:~^(https?://|/|#|mailto:|tel:)~i',
            ],

            'social_links' => ['required', 'array', 'max:10'],
            'social_links.*.platform' => [
                'required', 'string',
                'in:facebook,instagram,youtube,tiktok,whatsapp,messenger',
            ],
            'social_links.*.url' => [
                'nullable', 'string', 'max:500',
                'regex:~^(https?://|/)~i',
            ],

            'contact' => ['required', 'array'],
            'contact.hotline' => ['nullable', 'string', 'max:40'],
            'contact.email' => ['nullable', 'email', 'max:180'],
            'contact.address' => ['nullable', 'string', 'max:500'],
            'contact.store_text' => ['nullable', 'string', 'max:100'],
            'contact.store_url' => [
                'nullable', 'string', 'max:500',
                'regex:~^(https?://|/|#)~i',
            ],

            'payment_methods' => ['required', 'array', 'max:8'],
            'payment_methods.*' => ['required', 'string', 'max:60'],
            'copyright_text' => ['nullable', 'string', 'max:200'],
            'bottom_right_text' => ['nullable', 'string', 'max:200'],
            'is_active' => ['required', 'boolean'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Footer updated successfully.',
            'data' => $this->service->update($data),
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Footer logo updated successfully.',
            'data' => $this->service->uploadLogo($data['logo']),
        ]);
    }

    public function removeLogo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Footer logo removed successfully.',
            'data' => $this->service->removeLogo(),
        ]);
    }
}
