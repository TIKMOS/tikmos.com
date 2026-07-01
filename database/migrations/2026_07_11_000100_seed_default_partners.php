<?php

declare(strict_types=1);

use App\Models\Partner;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Remplace les partenaires de démo Sezy par les partenaires Tikmos par défaut.
        Partner::query()->delete();

        $partners = [
            ['name' => 'Alinkka', 'url' => 'https://alinkka.net'],
            ['name' => 'LegTik Se', 'url' => null],
        ];

        foreach ($partners as $i => $data) {
            Partner::create([
                'name' => $data['name'],
                'url' => $data['url'],
                'position' => $i,
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        Partner::whereIn('name', ['Alinkka', 'LegTik Se'])->delete();
    }
};
