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
            ['email' => env('TIKMOS_ADMIN_EMAIL', 'moise@tikmos.com')],
            [
                'name' => 'Moïse TIKO',
                'phone' => '+2290197440615',
                'password' => Hash::make(env('TIKMOS_ADMIN_PASSWORD', 'password')),
                'status' => 'active',
                'country' => 'BJ',
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([Role::SuperAdmin->value]);
    }
}
