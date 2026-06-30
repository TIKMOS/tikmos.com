<?php

use App\Enums\Role as RoleEnum;
use App\Models\Courier;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $ddl = Courier::create([
            'name' => 'DDL Express',
            'slug' => 'ddl-express',
            'contact_phone' => '+229 01 55 00 00 00',
            'contact_email' => 'contact@ddl.example',
            'zones' => ['Cotonou', 'Calavi'],
            'is_active' => true,
            'commission_type' => 'per_delivery',
            'commission_value' => 1000,
        ]);

        Role::findOrCreate(RoleEnum::Livreur->value, 'web');

        $livreur = User::firstOrCreate(
            ['email' => 'livreur@demo.test'],
            [
                'name' => 'Koffi Livreur',
                'password' => Hash::make('password'),
                'status' => 'active',
                'courier_id' => $ddl->id,
            ]
        );
        $livreur->assignRole(RoleEnum::Livreur->value);
    }

    public function down(): void
    {
        User::where('email', 'livreur@demo.test')->forceDelete();
        Courier::where('slug', 'ddl-express')->delete();
    }
};
