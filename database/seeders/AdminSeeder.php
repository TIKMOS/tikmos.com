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
        // Administrateur principal (personnalisable via .env) + admins secondaires.
        $admins = [
            [
                'email' => env('TIKMOS_ADMIN_EMAIL', 'moise@tikmos.com'),
                'name' => 'Moïse TIKO',
                'phone' => '+2290197440615',
                'password' => env('TIKMOS_ADMIN_PASSWORD', 'password'),
            ],
            [
                'email' => 'pesqui.me@gmail.com',
                'name' => 'Pesquidox',
                'phone' => null,
                'password' => env('TIKMOS_ADMIN_PASSWORD', 'password'),
            ],
            [
                'email' => 'tikmos2003@yahoo.fr',
                'name' => 'Tikmos Admin',
                'phone' => null,
                'password' => env('TIKMOS_ADMIN_PASSWORD', 'password'),
            ],
        ];

        foreach ($admins as $data) {
            $admin = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make($data['password']),
                    'status' => 'active',
                    'country' => 'BJ',
                    'email_verified_at' => now(),
                ],
            );

            $admin->syncRoles([Role::SuperAdmin->value]);
        }
    }
}
