<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->restrictOnDelete();

            // Example: purchase, sale, return, adjustment
            $table->string('movement_type', 40);

            // Signed values allow increases and decreases.
            $table->bigInteger('quantity_change');
            $table->unsignedBigInteger('quantity_before');
            $table->unsignedBigInteger('quantity_after');

            // Source reference
            $table->string('reference_type', 60)->nullable();
            $table->string('reference_id', 100)->nullable();

            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['product_variant_id', 'created_at']);

            $table->index(
                ['reference_type', 'reference_id'],
                'inventory_reference_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
