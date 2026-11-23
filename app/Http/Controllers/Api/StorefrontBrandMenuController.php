<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StorefrontBrandMenuController extends Controller
{
    public function index(): JsonResponse
    {
        $query = DB::table('brands');

        if (Schema::hasColumn('brands', 'status')) {
            $query->where('status', 'active');
        } elseif (Schema::hasColumn('brands', 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn('brands', 'publish_online')) {
            $query->where('publish_online', true);
        }

        $nameColumn = Schema::hasColumn('brands', 'name')
            ? 'name'
            : 'title';

        $brands = $query
            ->orderBy($nameColumn)
            ->get()
            ->map(function ($brand) use ($nameColumn) {
                $name = trim((string) ($brand->{$nameColumn} ?? ''));

                return [
                    'id' => $brand->id,
                    'name' => $name,
                    'slug' => !empty($brand->slug)
                        ? $brand->slug
                        : Str::slug($name),
                ];
            })
            ->filter(fn ($brand) => $brand['name'] !== '')
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Brands retrieved successfully.',
            'data' => [
                'brands' => $brands,
                'total' => $brands->count(),
            ],
        ]);
    }
}
