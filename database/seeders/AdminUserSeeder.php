<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('SEED_ADMIN_NAME', 'SheraDeal Admin');
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        if (!$email || !$password) {
            throw new RuntimeException(
                'SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD must be configured.'
            );
        }

        if (strlen($password) < 12) {
            throw new RuntimeException(
                'Admin password must contain at least 12 characters.'
            );
        }

        $user = User::firstOrNew([
            'email' => $email,
        ]);

        if ($user->exists && $user->role !== 'admin') {
            throw new RuntimeException(
                'An existing non-admin account uses this email.'
            );
        }

        $user->name = $name;
        $user->role = 'admin';
        $user->email_verified_at = now();

        if (!$user->exists) {
            $user->password = Hash::make($password);
        }

        $user->save();

        $this->command?->info('Admin account seeded successfully.');
    }
}
