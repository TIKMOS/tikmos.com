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
        // Repositionnement : équipements informatiques & accessoires de luxe (homme).
        Product::query()->forceDelete();
        ProductCategory::query()->forceDelete();

        $cats = [];
        foreach (['Ordinateurs & périphériques', 'Audio & accessoires', 'Accessoires de luxe'] as $i => $name) {
            $cats[$name] = ProductCategory::create(['name' => $name, 'slug' => Str::slug($name), 'position' => $i, 'is_active' => true]);
        }

        // [name, brand, category, price (XOF), compare_at, desc]
        $products = [
            ['Ordinateur portable pro 14"', 'Tikmos Select', 'Ordinateurs & périphériques', 850000, 920000, 'Ultrabook performant pour les professionnels : léger, rapide, autonomie longue.'],
            ['Souris ergonomique sans fil', 'Tikmos Select', 'Ordinateurs & périphériques', 35000, null, 'Précision et confort au quotidien, connexion sans fil stable.'],
            ['Clavier mécanique premium', 'Atelier Lux', 'Ordinateurs & périphériques', 65000, 78000, 'Frappe précise et silencieuse, châssis aluminium.'],
            ['Casque audio haute-fidélité', 'Atelier Lux', 'Audio & accessoires', 120000, null, 'Son immersif, réduction de bruit, finition premium.'],
            ['Sacoche cuir pour ordinateur', 'Maison Tikmos', 'Accessoires de luxe', 95000, 110000, 'Cuir pleine fleur, protection élégante pour votre matériel.'],
            ['Montre connectée élégante', 'Maison Tikmos', 'Accessoires de luxe', 145000, null, 'Design raffiné, suivi d\'activité et notifications au poignet.'],
        ];

        foreach ($products as $i => [$name, $brand, $cat, $price, $compare, $desc]) {
            Product::create([
                'category_id' => $cats[$cat]->id,
                'name' => $name,
                'brand' => $brand,
                'slug' => Str::slug($name),
                'description' => '<p>'.$desc.'</p>',
                'sizes' => null,
                'price' => $price,
                'compare_at_price' => $compare,
                'stock' => $i === 5 ? 4 : null,
                'is_active' => true,
                'is_featured' => $i < 3,
                'position' => $i,
            ]);
        }
    }

    public function down(): void
    {
        // Données de démo — non réversible.
    }
};
