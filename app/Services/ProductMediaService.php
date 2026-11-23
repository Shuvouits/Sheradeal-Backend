<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductMedia;
use App\Repositories\ProductMediaRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductMediaService
{
    public function __construct(
        protected ProductMediaRepository $mediaRepository
    ) {}

    public function upload(int $productId, UploadedFile $file): ProductMedia
    {
        $product = Product::findOrFail($productId);

        if ($this->mediaRepository->countByProduct($productId) >= 250) {
            throw ValidationException::withMessages([
                'image' => ['Maximum 250 media files are allowed.'],
            ]);
        }

        $extension = strtolower(
            $file->guessExtension() ?: 'jpg'
        );

        $filename = Str::uuid() . '.' . $extension;

        $path = $file->storeAs(
            "products/{$product->id}",
            $filename,
            'public'
        );

        if (!$path) {
            throw new \RuntimeException('Image upload failed.');
        }

        try {
            $media = DB::transaction(function () use (
                $productId,
                $file,
                $path
            ) {
                Product::whereKey($productId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $nextOrder = (int) ProductMedia::query()
                    ->where('product_id', $productId)
                    ->max('display_order') + 1;

                $hasCover = ProductMedia::query()
                    ->where('product_id', $productId)
                    ->where('is_cover', true)
                    ->exists();

                return $this->mediaRepository->create([
                    'product_id' => $productId,
                    'media_type' => 'image',
                    'disk' => 'public',
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'is_cover' => !$hasCover,
                    'display_order' => $nextOrder,
                ]);
            });
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($path);
            throw $error;
        }

        return $media;
    }

    public function setCover(
        int $productId,
        int $mediaId
    ): ProductMedia {
        return DB::transaction(function () use ($productId, $mediaId) {
            Product::whereKey($productId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->mediaRepository->setCover(
                $productId,
                $mediaId
            );

            return $this->mediaRepository->findForProduct(
                $productId,
                $mediaId
            );
        });
    }

    public function delete(int $productId, int $mediaId): void
    {
        $filePath = DB::transaction(function () use (
            $productId,
            $mediaId
        ) {
            Product::whereKey($productId)
                ->lockForUpdate()
                ->firstOrFail();

            $media = $this->mediaRepository->findForProduct(
                $productId,
                $mediaId
            );

            $path = $media->file_path;
            $disk = $media->disk;

            $wasCover = $media->is_cover;

            $this->mediaRepository->delete($media);

            if ($wasCover) {
                $next = ProductMedia::query()
                    ->where('product_id', $productId)
                    ->where('media_type', 'image')
                    ->orderBy('display_order')
                    ->first();

                if ($next) {
                    $this->mediaRepository->setCover(
                        $productId,
                        $next->id
                    );
                }
            }

            return [
                'path' => $path,
                'disk' => $disk,
            ];
        });

        if ($filePath['path']) {
            Storage::disk($filePath['disk'] ?: 'public')
                ->delete($filePath['path']);
        }
    }



public function list(int $productId)
{
    Product::findOrFail($productId);

    return $this->mediaRepository
        ->getByProduct($productId)
        ->map(function ($media) {
            $path = $media->file_path;

            if ($path) {
                $disk = $media->disk ?: 'public';

                $storageUrl = Storage::disk($disk)->url($path);

                $media->url = str_starts_with(
                    $storageUrl,
                    'http://'
                ) || str_starts_with(
                    $storageUrl,
                    'https://'
                )
                    ? $storageUrl
                    : rtrim(config('app.url'), '/') .
                        '/' .
                        ltrim($storageUrl, '/');
            } else {
                $media->url = $media->external_url;
            }

            return $media;
        });
}


}
