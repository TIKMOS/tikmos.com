<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Remplace le catalogue démo Sezy (compléments) par des chaussures de luxe.
        Product::query()->forceDelete();
        ProductCategory::query()->forceDelete();

        $cats = [];
        foreach (['Hommes', 'Femmes', 'Éditions limitées'] as $i => $name) {
            $cats[$name] = ProductCategory::create(['name' => $name, 'slug' => Str::slug($name), 'position' => $i, 'is_active' => true]);
        }

        // [name, brand, category, price (XOF), compare_at, sizes, desc]
        $products = [
            ['Derby cuir patiné', 'Maison Tikmos', 'Hommes', 285000, 320000, [40, 41, 42, 43, 44], 'Derby en cuir pleine fleur patiné à la main. Élégance intemporelle.'],
            ['Sneaker cuir blanc', 'Maison Tikmos', 'Hommes', 195000, null, [40, 41, 42, 43, 44, 45], 'Sneaker minimaliste en cuir de veau, semelle confort.'],
            ['Mocassin velours', 'Atelier Lux', 'Hommes', 240000, null, [40, 41, 42, 43], 'Mocassin en velours, finition soignée pour les grandes occasions.'],
            ['Escarpin satin', 'Atelier Lux', 'Femmes', 210000, 250000, [36, 37, 38, 39, 40], 'Escarpin en satin, talon élégant, confort maîtrisé.'],
            ['Ballerine cuir nappa', 'Maison Tikmos', 'Femmes', 165000, null, [36, 37, 38, 39], 'Ballerine souple en cuir nappa, chic au quotidien.'],
            ['Édition limitée or', 'Maison Tikmos', 'Éditions limitées', 420000, null, [39, 40, 41, 42], 'Série limitée à finitions dorées. Pièce d\'exception, numérotée.'],
        ];

        foreach ($products as $i => [$name, $brand, $cat, $price, $compare, $sizes, $desc]) {
            Product::create([
                'category_id' => $cats[$cat]->id,
                'name' => $name,
                'brand' => $brand,
                'slug' => Str::slug($name),
                'description' => '<p>'.$desc.'</p>',
                'sizes' => $sizes,
                'price' => $price,
                'compare_at_price' => $compare,
                'stock' => $i === 5 ? 3 : null,
                'is_active' => true,
                'is_featured' => $i < 3,
                'position' => $i,
            ]);
        }
    }

    public function down(): void
    {
        // Reciblage irréversible (données de démo).
    }
};
