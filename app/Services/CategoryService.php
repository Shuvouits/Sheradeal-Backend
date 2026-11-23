<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categories
    ) {}



public function list(array $filters)
{
    $result = $this->categories->paginate($filters);

    $categories = $result->getCollection();

    $categoryIds = $categories
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    $counts = $this->productAssignmentCounts($categoryIds);

    $categories->each(function ($category) use ($counts) {
        $count = (int) ($counts[$category->id] ?? 0);

        $category->setAttribute('products_count', $count);
        $category->setAttribute('product_count', $count);
        $category->setAttribute('assigned_products_count', $count);
    });

    return $result;
}




public function statistics(): array
{
    $statistics = $this->categories->statistics();

    $counts = $this->productAssignmentCounts();

    $assignedProducts = array_sum($counts);

    $statistics['assigned_products'] = $assignedProducts;
    $statistics['assigned_products_count'] = $assignedProducts;

    return $statistics;
}




    public function tree()
    {
        return $this->categories->tree();
    }

    public function find(int $id): Category
    {
        return $this->categories->find($id);
    }

    public function create(array $data, int $adminId): Category
    {
        return DB::transaction(function () use ($data, $adminId) {
            $this->validateHierarchy(
                null,
                $data['parent_id'] ?? null
            );

            $data['slug'] = $this->generateSlug(
                $data['slug'] ?? $data['name']
            );

            $data['status'] = $data['status'] ?? 'active';
            $data['is_featured'] = $data['is_featured'] ?? false;
            $data['display_order'] = $data['display_order'] ?? 0;
            $data['created_by'] = $adminId;
            $data['updated_by'] = $adminId;

            return $this->categories->create($data);
        });
    }

    public function update(
        int $id,
        array $data,
        int $adminId
    ): Category {
        return DB::transaction(function () use ($id, $data, $adminId) {
            $category = $this->categories->find($id);

            if (array_key_exists('parent_id', $data)) {
                $this->validateHierarchy(
                    $category,
                    $data['parent_id']
                );
            }

            if (array_key_exists('slug', $data)) {
                $data['slug'] = $this->generateSlug(
                    $data['slug'] ?: ($data['name'] ?? $category->name),
                    $category->id
                );
            }

            $data['updated_by'] = $adminId;

            return $this->categories->update($category, $data);
        });
    }

    public function toggleFeatured(
        int $id,
        int $adminId
    ): Category {
        $category = $this->categories->find($id);

        return $this->categories->update($category, [
            'is_featured' => !$category->is_featured,
            'updated_by' => $adminId,
        ]);
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $category = $this->categories->find($id);

            if ($this->categories->hasChildren($id)) {
                throw ValidationException::withMessages([
                    'category' => [
                        'Move or delete child categories first.',
                    ],
                ]);
            }

            $this->ensureNoAssignedProducts($id);

            $this->categories->delete($category);
        });
    }

    public function restore(int $id): Category
    {
        return DB::transaction(function () use ($id) {
            $category = $this->categories->findTrashed($id);

            if ($category->parent_id !== null) {
                $parent = Category::find($category->parent_id);

                if (!$parent) {
                    throw ValidationException::withMessages([
                        'parent_id' => [
                            'Restore the parent category first.',
                        ],
                    ]);
                }

                $this->validateHierarchy(
                    $category,
                    $category->parent_id
                );
            }

            return $this->categories->restore($category);
        });
    }

    public function uploadMedia(
        int $id,
        UploadedFile $file,
        string $type,
        int $adminId
    ): Category {
        if (!in_array($type, ['cover_image', 'icon'], true)) {
            throw ValidationException::withMessages([
                'type' => ['Invalid image type.'],
            ]);
        }

        $category = $this->categories->find($id);

        $oldPath = $category->{$type};
        $folder = $type === 'icon'
            ? 'categories/icons'
            : 'categories/covers';

        $path = $file->store($folder, 'public');

        if (!$path) {
            throw new \RuntimeException('Image upload failed.');
        }

        try {
            $updated = $this->categories->update($category, [
                $type => $path,
                'updated_by' => $adminId,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $updated;
    }

    private function generateSlug(
        string $value,
        ?int $excludeId = null
    ): string {
        $base = Str::slug($value);

        if (!$base) {
            throw ValidationException::withMessages([
                'slug' => ['A valid category name or slug is required.'],
            ]);
        }

        $base = substr($base, 0, 240);
        $slug = $base;
        $counter = 2;

        while ($this->categories->slugExists($slug, $excludeId)) {
            $suffix = '-' . $counter;
            $slug = substr($base, 0, 255 - strlen($suffix)) . $suffix;
            $counter++;
        }

        return $slug;
    }

    private function validateHierarchy(
        ?Category $category,
        ?int $parentId
    ): void {
        $depth = 1;
        $currentId = $parentId;
        $visited = [];

        while ($currentId !== null) {
            if (isset($visited[$currentId])) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Circular category hierarchy detected.'],
                ]);
            }

            if ($category && $currentId === $category->id) {
                throw ValidationException::withMessages([
                    'parent_id' => [
                        'A category cannot be its own parent or descendant.',
                    ],
                ]);
            }

            $visited[$currentId] = true;

            $parent = Category::find($currentId);

            if (!$parent) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Parent category not found.'],
                ]);
            }

            $depth++;

            if ($depth > 3) {
                throw ValidationException::withMessages([
                    'parent_id' => [
                        'A maximum of three category levels is allowed.',
                    ],
                ]);
            }

            $currentId = $parent->parent_id;
        }

        if ($category) {
            $descendantDepth = $this->descendantDepth(
                $category->id,
                []
            );

            if ($depth + $descendantDepth - 1 > 3) {
                throw ValidationException::withMessages([
                    'parent_id' => [
                        'Moving this category would exceed three levels.',
                    ],
                ]);
            }
        }
    }

    private function descendantDepth(
        int $categoryId,
        array $visited
    ): int {
        if (isset($visited[$categoryId])) {
            throw ValidationException::withMessages([
                'parent_id' => ['Circular category hierarchy detected.'],
            ]);
        }

        $visited[$categoryId] = true;

        $children = Category::where('parent_id', $categoryId)
            ->pluck('id');

        $maxDepth = 1;

        foreach ($children as $childId) {
            $maxDepth = max(
                $maxDepth,
                1 + $this->descendantDepth(
                    (int) $childId,
                    $visited
                )
            );
        }

        return $maxDepth;
    }




private function ensureNoAssignedProducts(int $categoryId): void
{
    $counts = $this->productAssignmentCounts([$categoryId]);

    $activeProducts = (int) ($counts[$categoryId] ?? 0);

    if ($activeProducts > 0) {
        throw ValidationException::withMessages([
            'category' => [
                "This category has {$activeProducts} active product(s). Delete or move them before deleting this category.",
            ],
        ]);
    }
}




private function productAssignmentCounts(?array $categoryIds = null): array
{
    if (!Schema::hasTable('products')) {
        return [];
    }

    if ($categoryIds !== null && empty($categoryIds)) {
        return [];
    }

    $hasDeletedAt = Schema::hasColumn(
        'products',
        'deleted_at'
    );

    $queries = [];

    if (Schema::hasColumn('products', 'category_id')) {
        $direct = DB::table('products')
            ->select('category_id')
            ->selectRaw('id AS product_id')
            ->whereNotNull('category_id');

        if ($hasDeletedAt) {
            $direct->whereNull('deleted_at');
        }

        if ($categoryIds !== null) {
            $direct->whereIn('category_id', $categoryIds);
        }

        $queries[] = $direct;
    }

    if (
        Schema::hasTable('category_product') &&
        Schema::hasColumn('category_product', 'category_id') &&
        Schema::hasColumn('category_product', 'product_id')
    ) {
        $pivot = DB::table('category_product AS cp')
            ->join('products AS p', 'p.id', '=', 'cp.product_id')
            ->select('cp.category_id')
            ->selectRaw('p.id AS product_id');

        if ($hasDeletedAt) {
            $pivot->whereNull('p.deleted_at');
        }

        if ($categoryIds !== null) {
            $pivot->whereIn('cp.category_id', $categoryIds);
        }

        $queries[] = $pivot;
    }

    if (empty($queries)) {
        return [];
    }

    $assignmentQuery = array_shift($queries);

    foreach ($queries as $query) {
        $assignmentQuery->union($query);
    }

    return DB::query()
        ->fromSub($assignmentQuery, 'assignments')
        ->select('category_id')
        ->selectRaw('COUNT(DISTINCT product_id) AS total')
        ->groupBy('category_id')
        ->pluck('total', 'category_id')
        ->map(fn ($count) => (int) $count)
        ->all();
}




}
