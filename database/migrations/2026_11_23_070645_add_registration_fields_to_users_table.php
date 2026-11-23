<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)
                ->nullable()
                ->unique('users_phone_unique');

            $table->string('username', 30)
                ->nullable()
                ->unique('users_username_unique');

            $table->text('delivery_address')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_unique');
            $table->dropUnique('users_username_unique');

            $table->dropColumn([
                'phone',
                'username',
                'delivery_address',
            ]);
        });
    }
};
