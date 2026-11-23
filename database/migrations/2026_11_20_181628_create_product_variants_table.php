<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Variant identity
            $table->string('title', 255);
            $table->string('sku', 150)->nullable()->unique();
            $table->string('barcode', 150)->nullable()->unique();

            // Generated from sorted global option IDs.
            $table->string('combination_key', 500);

            // Pricing
            $table->decimal('price', 18, 2)->nullable();
            $table->decimal('compare_at_price', 18, 2)->nullable();
            $table->decimal('cost_per_item', 18, 2)->nullable();

            // Stock
            $table->unsignedBigInteger('quantity')->default(0);
            $table->boolean('track_quantity')->default(true);
            $table->boolean('continue_selling_out_of_stock')
                ->default(false);

            // Display
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('display_order')->default(0);

            // Optional variant image
            $table->foreignId('product_media_id')
                ->nullable()
                ->constrained('product_media')
                ->nullOnDelete();

            $table->decimal('weight', 12, 3)->nullable();
            $table->string('weight_unit', 10)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['product_id', 'combination_key'],
                'product_variant_combination_unique'
            );

            $table->index(['product_id', 'status']);
            $table->index(['product_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
