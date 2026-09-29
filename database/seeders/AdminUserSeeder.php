<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@portfolio.test');
        $password = env('ADMIN_PASSWORD');

        if (blank($password)) {
            $password = Str::password(20);
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Portfolio Admin'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        if (filled(env('ADMIN_EMAIL'))) {
            $this->command?->info("Admin user: {$email}");
        }
    }
}
