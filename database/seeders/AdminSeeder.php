<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /** Выполнить сидирование базы данных.
     *
     */
    public function run(): void
    {
        if (User::where('role', UserRole::Admin->value)->exists()) {
            $this->command->info('Admin user already exists, skipping.');

            return;
        }

        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => UserRole::Admin->value,
        ]);

        $this->command->info('Admin user created: admin@example.com / password');
    }
}
