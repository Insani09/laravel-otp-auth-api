<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (class_exists(\Database\Seeders\IndoRegionSeeder::class)) {
            $this->call(IndoRegionSeeder::class);
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => 'AdminPass1234',
                'role' => 'admin',
                'negara' => 'Indonesia',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Pengguna Demo',
                'password' => 'UserPass12345',
                'role' => 'user',
                'negara' => 'Indonesia',
            ]
        );
    }
}
