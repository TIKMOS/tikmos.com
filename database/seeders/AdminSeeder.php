<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('SEZY_ADMIN_EMAIL', 'boris@sezy.africa')],
            [
                'name' => 'Boris Vital',
                'phone' => '+2290166386417',
                'password' => Hash::make(env('SEZY_ADMIN_PASSWORD', 'password')),
                'status' => 'active',
                'city' => 'Cotonou',
                'country' => 'BJ',
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([Role::SuperAdmin->value]);
    }
}
