<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {

            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Media Information
            |--------------------------------------------------------------------------
            */

            $table->string('media_type', 30)
                ->default('image');

            $table->string('disk', 50)
                ->default('public');

            $table->string('file_path', 2048)
                ->nullable();

            $table->string('external_url', 2048)
                ->nullable();

            $table->string('thumbnail_path', 2048)
                ->nullable();

            $table->string('original_filename', 255)
                ->nullable();

            $table->string('mime_type', 100)
                ->nullable();

            $table->unsignedBigInteger('file_size')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Image SEO
            |--------------------------------------------------------------------------
            */

            $table->string('alt_text', 255)
                ->nullable();

            $table->string('title', 255)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Media Display
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_cover')
                ->default(false);

            $table->unsignedInteger('display_order')
                ->default(0);

            $table->unsignedInteger('width')
                ->nullable();

            $table->unsignedInteger('height')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'product_id',
                'display_order',
            ]);

            $table->index([
                'product_id',
                'is_cover',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
