<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic Product Information
            |--------------------------------------------------------------------------
            */

            $table->string('title', 255);

            $table->string('slug', 255)->unique();

            $table->string('product_type', 100)
                ->default('physical');

            $table->text('overview')->nullable();

            $table->longText('description')->nullable();

            $table->longText('specifications')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Product Organization
            |--------------------------------------------------------------------------
            */

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();

            $table->string('vendor_name', 150)->nullable();

            $table->string('manufacturer', 150)->nullable();

            $table->string('distributed_by', 150)->nullable();

            $table->json('tags')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Product Status
            |--------------------------------------------------------------------------
            */

            $table->string('status', 20)
                ->default('draft')
                ->index();

            $table->boolean('is_featured')->default(false);

            $table->boolean('publish_online')->default(true);

            $table->boolean('publish_pos')->default(false);

            $table->timestamp('published_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Returns and COD
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_final_sale')->default(false);

            $table->unsignedSmallInteger('return_window_days')
                ->nullable();

            $table->boolean('cod_advance_required')
                ->default(false);

            $table->decimal('cod_advance_amount', 18, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            $table->decimal('price', 18, 2)->default(0);

            $table->decimal('compare_at_price', 18, 2)
                ->nullable();

            $table->decimal('cost_per_item', 18, 2)
                ->nullable();

            $table->boolean('charge_tax')->default(true);

            $table->decimal('unit_price', 18, 4)
                ->nullable();

            $table->string('unit_price_measurement', 50)
                ->nullable();

            $table->decimal('special_price', 18, 2)
                ->nullable();

            $table->timestamp('special_price_from')
                ->nullable();

            $table->timestamp('special_price_to')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */

            $table->string('sku', 150)
                ->nullable()
                ->unique();

            $table->string('barcode', 150)
                ->nullable()
                ->unique();

            $table->string('barcode_standard', 40)
                ->nullable();

            $table->string('identifier_source', 50)
                ->nullable();

            $table->unsignedBigInteger('quantity')->default(0);

            $table->boolean('track_quantity')->default(true);

            $table->boolean('continue_selling_out_of_stock')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Quantity Rules
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('min_order_quantity')
                ->default(1);

            $table->unsignedSmallInteger('quantity_multiple')
                ->default(1);

            $table->unsignedSmallInteger('max_order_quantity')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Shipping Information
            |--------------------------------------------------------------------------
            */

            $table->boolean('requires_shipping')
                ->default(true);

            $table->decimal('weight', 12, 3)
                ->default(0);

            $table->string('weight_unit', 10)
                ->default('kg');

            $table->decimal('length', 12, 2)
                ->default(0);

            $table->decimal('width', 12, 2)
                ->default(0);

            $table->decimal('height', 12, 2)
                ->default(0);

            $table->string('dimension_unit', 10)
                ->default('cm');

            $table->string('country_of_origin', 100)
                ->nullable();

            $table->string('hs_code', 30)
                ->nullable();

            $table->text('customs_description')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Search Engine Optimization
            |--------------------------------------------------------------------------
            */

            $table->string('meta_title', 70)
                ->nullable();

            $table->string('meta_description', 160)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Import and External Data
            |--------------------------------------------------------------------------
            */

            $table->string('external_url', 2048)
                ->nullable();

            $table->string('import_source', 100)
                ->nullable();

            $table->string('external_id', 150)
                ->nullable();

            $table->json('additional_attributes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Product Settings
            |--------------------------------------------------------------------------
            */

            $table->boolean('has_variants')->default(false);

            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('title');

            $table->index(['status', 'publish_online']);

            $table->index(['category_id', 'status']);

            $table->index(['brand_id', 'status']);

            $table->index(['is_featured', 'status']);

            $table->index(['created_at', 'id']);

            $table->index(['import_source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
