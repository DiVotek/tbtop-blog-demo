<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@blog.demo'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
            ],
        );

        $this->call(BlogSeeder::class);
    }
}
