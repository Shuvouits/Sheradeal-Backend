<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique('col_slug_unique');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();

            $table->enum('status', ['active', 'draft'])
                ->default('draft');

            $table->boolean('publish_online')->default(true);
            $table->boolean('publish_pos')->default(false);

            $table->enum('collection_type', ['manual', 'automated'])
                ->default('manual');

            $table->enum('sort_by', [
                'manual',
                'newest',
                'price_asc',
                'price_desc',
                'title_asc',
            ])->default('manual');

            $table->unsignedInteger('display_order')->default(0);

            $table->foreignId('rule_category_id')->nullable();
            $table->foreignId('rule_brand_id')->nullable();

            $table->decimal('rule_min_price', 12, 2)->nullable();
            $table->decimal('rule_max_price', 12, 2)->nullable();

            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();

            $table->timestamps();

            $table->foreign('rule_category_id', 'col_category_fk')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();

            $table->foreign('rule_brand_id', 'col_brand_fk')
                ->references('id')
                ->on('brands')
                ->nullOnDelete();

            $table->index(
                ['status', 'publish_online', 'display_order'],
                'col_public_order_idx'
            );
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collection_id');
            $table->foreignId('product_id');

            $table->unsignedInteger('display_order')->default(0);

            $table->foreign('collection_id', 'cp_collection_fk')
                ->references('id')
                ->on('collections')
                ->cascadeOnDelete();

            $table->foreign('product_id', 'cp_product_fk')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->unique(
                ['collection_id', 'product_id'],
                'cp_collection_product_unique'
            );

            $table->index(
                ['collection_id', 'display_order'],
                'cp_collection_order_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
    }
};
