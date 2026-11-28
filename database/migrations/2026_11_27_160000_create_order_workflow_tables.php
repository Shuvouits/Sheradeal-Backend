<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preorders', function (Blueprint $table) {
            $table->id();
            $table->string('preorder_number')->unique();
            $table->foreignId('order_id')->nullable()->index();
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->string('customer_email')->nullable();
            $table->text('delivery_address');
            $table->string('shipping_location', 30);
            $table->text('note')->nullable();
            $table->date('expected_date');
            $table->string('status', 30)->default('pending')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('preorder_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preorder_id')
                ->constrained('preorders')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete();
            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('reason', 100);
            $table->text('note')->nullable();
            $table->string('status', 30)->default('requested')->index();
            $table->string('refund_method', 20)->default('cash');
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('order_id')->nullable()->index();
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->string('customer_email')->nullable();
            $table->text('delivery_address');
            $table->string('shipping_location', 30);
            $table->text('note')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')
                ->constrained('order_quotes')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('abandoned_checkouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('checkout_token')->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 30);
            $table->string('customer_email')->nullable();
            $table->json('items');
            $table->string('status', 30)->default('open')->index();
            $table->timestamp('recovered_at')->nullable();
            $table->foreignId('order_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_checkouts');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('order_quotes');
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('preorder_items');
        Schema::dropIfExists('preorders');
    }
};
