<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_footers', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name')->default('SheraDeal');
            $table->string('brand_tagline')->default('BANGLADESH');
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->string('community_title')->nullable();

            $table->json('trust_badges')->nullable();
            $table->json('link_columns')->nullable();
            $table->json('social_links')->nullable();
            $table->json('contact')->nullable();
            $table->json('payment_methods')->nullable();

            $table->string('copyright_text')->nullable();
            $table->string('bottom_right_text')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_footers');
    }
};

