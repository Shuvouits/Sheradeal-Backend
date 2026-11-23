<?php

namespace App\Services;

use App\Repositories\SiteFooterRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SiteFooterService
{
    public function __construct(
        private SiteFooterRepository $repository
    ) {}

    public function get(): array
    {
        $footer = $this->repository->get();
        $defaults = $this->defaults();

        if (!$footer) {
            return $defaults;
        }

        $saved = $footer->toArray();

        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $saved) || $saved[$key] === null) {
                $saved[$key] = $value;
            }
        }

        return $saved;
    }

    public function update(array $data): array
    {
        $this->repository->save($data);

        return $this->get();
    }

    public function uploadLogo(UploadedFile $file): array
    {
        $old = $this->repository->get()?->logo_path;
        $path = $file->store('site-footer', 'public');

        try {
            $this->repository->save(['logo_path' => $path]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }

        return $this->get();
    }

    public function removeLogo(): array
    {
        $old = $this->repository->get()?->logo_path;

        $this->repository->save(['logo_path' => null]);

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return $this->get();
    }

    private function defaults(): array
    {
        return [
            'id' => 1,
            'brand_name' => 'SheraDeal',
            'brand_tagline' => 'BANGLADESH',
            'logo_path' => null,
            'logo_url' => null,
            'description' => 'Your favorite products, great prices, and convenient shopping across Bangladesh.',
            'community_title' => 'JOIN OUR COMMUNITY',

            'trust_badges' => [
                [
                    'icon' => 'shield',
                    'title' => '100% Authentic',
                    'subtitle' => 'Quality products you can trust',
                ],
                [
                    'icon' => 'truck',
                    'title' => 'Delivery Across Bangladesh',
                    'subtitle' => 'Convenient nationwide delivery',
                ],
                [
                    'icon' => 'wallet',
                    'title' => 'Cash on Delivery',
                    'subtitle' => 'Flexible payment options',
                ],
                [
                    'icon' => 'refresh',
                    'title' => 'Easy Returns',
                    'subtitle' => 'See our return policy',
                ],
            ],

            'link_columns' => [
                [
                    'title' => 'SHOP BY CATEGORY',
                    'links' => [
                        ['label' => 'All Products', 'url' => '/products'],
                        ['label' => 'New Arrivals', 'url' => '/products'],
                        ['label' => 'Popular Products', 'url' => '/products'],
                    ],
                ],
                [
                    'title' => 'EXPLORE',
                    'links' => [
                        ['label' => 'Offers', 'url' => '/products'],
                        ['label' => 'Collections', 'url' => '/products'],
                        ['label' => 'Brands', 'url' => '/products'],
                    ],
                ],
                [
                    'title' => 'CUSTOMER CARE',
                    'links' => [
                        ['label' => 'Contact Us', 'url' => '/contact'],
                        ['label' => 'Store Locations', 'url' => '/#visit-outlets'],
                        ['label' => 'Shipping & Delivery', 'url' => '/shipping'],
                        ['label' => 'Returns & Refunds', 'url' => '/returns'],
                    ],
                ],
            ],

            'social_links' => [
                ['platform' => 'facebook', 'url' => ''],
                ['platform' => 'instagram', 'url' => ''],
                ['platform' => 'youtube', 'url' => ''],
                ['platform' => 'tiktok', 'url' => ''],
                ['platform' => 'whatsapp', 'url' => ''],
                ['platform' => 'messenger', 'url' => ''],
            ],

            'contact' => [
                'hotline' => '+880 1300-000000',
                'email' => '',
                'address' => 'Bangladesh',
                'store_text' => 'Visit a store near you',
                'store_url' => '/#visit-outlets',
            ],

            'payment_methods' => [
                'Cash on Delivery',
                'bKash',
            ],

            'copyright_text' => 'SheraDeal Bangladesh. All rights reserved.',
            'bottom_right_text' => '',
            'is_active' => true,
        ];
    }
}
