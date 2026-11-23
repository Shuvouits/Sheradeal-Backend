<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_variants', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('slug', 180)->unique();

            $table->string('visual_type', 30)->default('rectangle');

            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();

            $table->index('name');
            $table->index('display_order');
        });

        Schema::create('global_variant_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('global_variant_id')
                ->constrained('global_variants')
                ->cascadeOnDelete();

            $table->string('label', 150);
            $table->string('value', 180);

            $table->string('color_code', 7)->nullable();

            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();

            $table->unique([
                'global_variant_id',
                'value',
            ], 'variant_option_unique');

            $table->index([
                'global_variant_id',
                'display_order',
            ], 'variant_option_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_variant_options');
        Schema::dropIfExists('global_variants');
    }
};
