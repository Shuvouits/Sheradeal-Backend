<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->text('address');
            $table->string('phone', 40)->nullable();
            $table->string('map_query', 255)->nullable();
            $table->json('opening_hours')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(
                ['is_active', 'display_order'],
                'outlet_active_order_idx'
            );
        });

        Schema::create('store_outlet_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_outlet_id')
                ->constrained('store_outlets', 'id', 'outlet_image_fk')
                ->cascadeOnDelete();

            $table->string('image_path');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(
                ['store_outlet_id', 'display_order'],
                'outlet_image_order_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_outlet_images');
        Schema::dropIfExists('store_outlets');
    }
};
