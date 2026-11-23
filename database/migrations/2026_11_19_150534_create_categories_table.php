<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Category Details
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Category Hierarchy
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->restrictOnDelete();

            // Images
            $table->string('cover_image')->nullable();
            $table->string('icon')->nullable();

            // Status
            $table->string('status', 20)->default('active');
            $table->boolean('is_featured')->default(false);

            // Organization
            $table->unsignedInteger('display_order')->default(0);
            $table->string('tax_class')->nullable();

            // SEO
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->json('tags')->nullable();

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
            $table->index('status');
            $table->index('is_featured');
            $table->index('display_order');
            $table->index(['parent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
