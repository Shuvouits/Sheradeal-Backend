<?php

namespace App\Services;

use App\Models\Brand;
use App\Repositories\BrandRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BrandService
{
    public function __construct(
        protected BrandRepository $brands
    ) {}

    public function list(array $filters)
    {
        return $this->brands->paginate($filters);
    }

    public function statistics(): array
    {
        return $this->brands->statistics();
    }

    public function find(int $id): Brand
    {
        return $this->brands->find($id);
    }

    public function create(array $data, int $adminId): Brand
    {
        $data['slug'] = $this->generateSlug(
            $data['slug'] ?? $data['name']
        );

        $data['created_by'] = $adminId;

        $data['status'] = $data['status'] ?? 'active';
        $data['source'] = $data['source'] ?? 'official';
        $data['approval_status'] = $data['approval_status'] ?? 'approved';

        if ($data['approval_status'] === 'approved') {
            $data['approved_by'] = $adminId;
            $data['approved_at'] = now();
        }

        return $this->brands->create($data);
    }

    public function update(
        int $id,
        array $data,
        int $adminId
    ): Brand {
        $brand = $this->brands->find($id);

        if (array_key_exists('slug', $data)) {
            $data['slug'] = $this->generateSlug(
                $data['slug'] ?: ($data['name'] ?? $brand->name),
                $brand->id
            );
        }

        if (isset($data['approval_status'])) {
            if ($data['approval_status'] === 'approved') {
                $data['approved_by'] = $adminId;
                $data['approved_at'] = now();
            } else {
                $data['approved_by'] = null;
                $data['approved_at'] = null;
            }
        }

        return $this->brands->update($brand, $data);
    }

    public function toggleFeatured(int $id): Brand
    {
        $brand = $this->brands->find($id);

        return $this->brands->update($brand, [
            'is_featured' => !$brand->is_featured,
        ]);
    }

    public function archive(int $id): Brand
    {
        $brand = $this->brands->find($id);

        return $this->brands->update($brand, [
            'status' => 'archived',
            'is_featured' => false,
        ]);
    }

    public function restoreArchive(int $id): Brand
    {
        $brand = $this->brands->find($id);

        if ($brand->status !== 'archived') {
            throw ValidationException::withMessages([
                'status' => ['This brand is not archived.'],
            ]);
        }

        return $this->brands->update($brand, [
            'status' => 'inactive',
        ]);
    }

    public function delete(int $id): void
    {
        $brand = $this->brands->find($id);

        $this->brands->delete($brand);
    }

    public function restoreDeleted(int $id): Brand
    {
        $brand = $this->brands->findTrashed($id);

        return $this->brands->restore($brand);
    }


public function uploadLogo(
    int $id,
    UploadedFile $file
): Brand {
    $brand = $this->brands->find($id);

    $oldLogo = $brand->logo;

    $path = $file->store('brands', 'public');

    if (!$path) {
        throw new \RuntimeException('Logo upload failed.');
    }

    try {
        $updated = $this->brands->update($brand, [
            'logo' => $path,
        ]);
    } catch (\Throwable $exception) {
        Storage::disk('public')->delete($path);
        throw $exception;
    }

    if ($oldLogo && $oldLogo !== $path) {
        Storage::disk('public')->delete($oldLogo);
    }

    return $updated;
}


    protected function generateSlug(
        string $value,
        ?int $excludeId = null
    ): string {
        $base = Str::slug($value);

        if ($base === '') {
            throw ValidationException::withMessages([
                'slug' => ['Please provide a valid brand name or slug.'],
            ]);
        }

        $slug = $base;
        $counter = 2;

        while ($this->brands->slugExists($slug, $excludeId)) {
            $suffix = '-' . $counter;
            $slug = Str::limit($base, 255 - strlen($suffix), '')
                . $suffix;
            $counter++;
        }

        return $slug;
    }
}
