<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('badge_text')->nullable();
            $table->string('heading');
            $table->text('description')->nullable();

            $table->string('desktop_image')->nullable();
            $table->string('mobile_image')->nullable();

            $table->string('background_color', 7)->default('#05714B');
            $table->string('text_color', 7)->default('#FFFFFF');

            $table->string('primary_button_text')->nullable();
            $table->string('primary_button_link', 2048)->nullable();

            $table->string('secondary_button_text')->nullable();
            $table->string('secondary_button_link', 2048)->nullable();

            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
