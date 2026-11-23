<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();

            // Brand Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();

            // Status & Approval
            $table->string('status', 30)->default('active');
            $table->string('source', 30)->default('official');
            $table->string('approval_status', 30)->default('approved');

            // Organization
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('display_order')->default(0);

            // Search Engine Optimization
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();

            // Audit & Approval
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            // Timestamps & Soft Delete
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
            $table->index('status');
            $table->index('source');
            $table->index('approval_status');
            $table->index('is_featured');
            $table->index('display_order');
            $table->index(['status', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
