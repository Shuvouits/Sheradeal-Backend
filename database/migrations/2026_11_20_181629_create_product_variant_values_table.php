<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('global_variant_id')
                ->constrained('global_variants')
                ->restrictOnDelete();

            $table->foreignId('global_variant_option_id')
                ->constrained('global_variant_options')
                ->restrictOnDelete();

            $table->timestamps();

            // Only one option from each variant group.
            $table->unique(
                ['product_variant_id', 'global_variant_id'],
                'pv_group_unique'
            );

            $table->unique(
                ['product_variant_id', 'global_variant_option_id'],
                'pv_option_unique'
            );

            $table->index('global_variant_option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
    }
};
