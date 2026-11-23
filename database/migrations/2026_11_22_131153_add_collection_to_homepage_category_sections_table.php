<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_category_sections', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->change();

            $table->foreignId('collection_id')
                ->nullable()
                ->after('category_id');

            $table->foreign('collection_id', 'hcs_collection_fk')
                ->references('id')
                ->on('collections')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('homepage_category_sections', function (Blueprint $table) {
            $table->dropForeign('hcs_collection_fk');
            $table->dropColumn('collection_id');
        });
    }
};
