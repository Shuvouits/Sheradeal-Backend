<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_category_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->string('title');
            $table->enum('selection_mode', ['automatic', 'manual'])
                ->default('automatic');

            $table->unsignedTinyInteger('product_limit')->default(10);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'display_order']);
        });

        Schema::create('homepage_category_section_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('section_id')
                ->constrained('homepage_category_sections')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->unsignedInteger('display_order')->default(0);

            $table->unique(['section_id', 'product_id']);

            $table->index(['section_id', 'display_order'], 'hcsp_section_order_idx');
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_category_section_products');
        Schema::dropIfExists('homepage_category_sections');
    }
};
