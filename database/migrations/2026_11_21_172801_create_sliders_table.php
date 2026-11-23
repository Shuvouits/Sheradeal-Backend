<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

public function up(): void
{
    Schema::create('sliders', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->string('subtitle')->nullable();
        $table->text('description')->nullable();
        $table->string('desktop_image');
        $table->string('mobile_image')->nullable();
        $table->string('button_text')->nullable();
        $table->string('button_link', 2048)->nullable();
        $table->enum('text_position', ['left', 'center', 'right'])->default('left');
        $table->string('text_color', 7)->default('#FFFFFF');
        $table->unsignedTinyInteger('overlay_opacity')->default(0);
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->dateTime('starts_at')->nullable();
        $table->dateTime('ends_at')->nullable();
        $table->timestamps();

        $table->index(['is_active', 'sort_order']);
    });
}

public function down(): void
{
    Schema::dropIfExists('sliders');
}


  
};
