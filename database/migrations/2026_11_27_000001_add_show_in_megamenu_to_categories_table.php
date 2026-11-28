<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('categories', 'show_in_megamenu')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('show_in_megamenu')
                    ->default(true)
                    ->after('is_featured');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('categories', 'show_in_megamenu')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('show_in_megamenu');
            });
        }
    }
};
