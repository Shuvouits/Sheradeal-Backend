<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExportController extends Controller
{
    private const HEADERS = [
        'Attribute Set',
        'Barcode',
        'Item Name',
        'Type',
        'Category',
        'Category Path',
        'Brand',
        'Manufacturer',
        'Distributed By',
        'Country Of Manufacturer',
        'price',
        'Special Price',
        'Special Price From',
        'Special Price To',
        'Stock',
        'link_url',
        'img_url',
        'Status',
        'Available Stock',
    ];

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:all,active,draft,archived'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $filename = 'sheradeal-products-' .
            now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(
            function () use ($filters) {
                $output = fopen('php://output', 'w');

                if (!$output) {
                    return;
                }

                // UTF-8 BOM improves Excel compatibility.
                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, self::HEADERS);

                $query = Product::query()
                    ->with([
                        'category',
                        'brand',
                        'media',
                    ])
                    ->orderBy('id');

                if (
                    !empty($filters['status']) &&
                    $filters['status'] !== 'all'
                ) {
                    $query->where(
                        'status',
                        $filters['status']
                    );
                }

                if (!empty($filters['search'])) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
                }

                $query->chunkById(300, function ($products) use ($output) {
                    foreach ($products as $product) {
                        $attributes = $product->additional_attributes;

                        if (!is_array($attributes)) {
                            $attributes = json_decode(
                                $attributes ?: '{}',
                                true
                            ) ?: [];
                        }

                        $media = $product->media
                            ->firstWhere('is_cover', true)
                            ?? $product->media->first();

                        $imageUrl = '';

                        if ($media) {
                            if ($media->external_url) {
                                $imageUrl = $media->external_url;
                            } elseif ($media->file_path) {
                                $path = ltrim(
                                    $media->file_path,
                                    '/'
                                );

                                $imageUrl = rtrim(
                                    config('app.url'),
                                    '/'
                                ) . '/storage/' . $path;
                            }
                        }

                        $row = [
                            $attributes['attribute_set'] ?? '',
                            $product->barcode ?? '',
                            $product->title ?? '',
                            ($product->product_type === 'digital')
                                ? 'virtual'
                                : 'simple',
                            $product->category?->name ?? '',
                            $attributes['category_path'] ?? '',
                            $product->brand?->name ?? '',
                            $product->manufacturer ?? '',
                            $product->distributed_by ?? '',
                            $product->country_of_origin ?? '',
                            $product->price ?? 0,
                            $product->special_price ?? '',
                            $this->dateString(
                                $product->special_price_from
                            ),
                            $this->dateString(
                                $product->special_price_to
                            ),
                            $product->quantity ?? 0,
                            $product->external_url ?? '',
                            $imageUrl,
                            $product->status === 'active'
                                ? 'Enabled'
                                : 'Disabled',
                            $attributes['available_stock_original']
                                ?? $product->quantity
                                ?? 0,
                        ];

                        fputcsv(
                            $output,
                            array_map(
                                fn ($value) => $this->safeCell($value),
                                $row
                            )
                        );
                    }

                    flush();
                });

                fclose($output);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store',
            ]
        );
    }

    public function sample(): StreamedResponse
    {
        return response()->streamDownload(
            function () {
                $output = fopen('php://output', 'w');

                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, self::HEADERS);

                fputcsv($output, [
                    'Electronics',
                    'SHD-SAMPLE-001',
                    'Sample Smartphone',
                    'simple',
                    'Smartphones',
                    'Electronics > Smartphones',
                    'Sample Brand',
                    'Sample Manufacturer',
                    '',
                    'Bangladesh',
                    '15000',
                    '14000',
                    '',
                    '',
                    '10',
                    '',
                    '',
                    'Enabled',
                    '10',
                ]);

                fclose($output);
            },
            'sheradeal-product-import-sample.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store',
            ]
        );
    }

    private function dateString($value): string
    {
        if (!$value) {
            return '';
        }

        return (string) $value;
    }

    private function safeCell($value): string
    {
        $value = (string) ($value ?? '');

        // Protect spreadsheet applications from CSV formula injection.
        if (
            preg_match('/^[\s]*[=+\-@]/u', $value) ||
            preg_match('/^[\t\r\n]/', $value)
        ) {
            return "'" . $value;
        }

        return $value;
    }
}
