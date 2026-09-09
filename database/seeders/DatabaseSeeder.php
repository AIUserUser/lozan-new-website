<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('lozan.admin_email');
        $password = config('lozan.admin_password') ?: 'lozan-admin-change-me';

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Lozan Admin',
                'password' => $password,
                'is_admin' => true,
            ]
        );
    }
}
