<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Settings\ShopSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $cats = [];
        foreach (['Compléments', 'Bien-être', 'Accessoires'] as $i => $name) {
            $cats[$name] = ProductCategory::create(['name' => $name, 'slug' => Str::slug($name), 'position' => $i, 'is_active' => true]);
        }

        $products = [
            ['Whey Protéine Isolate 1 kg', 'Compléments', 24000, 28000, '<p>Protéine de haute qualité pour la récupération musculaire. Goût vanille.</p>'],
            ['Créatine Monohydrate 300 g', 'Compléments', 12000, null, '<p>Créatine pure micronisée. Pour la force et l\'endurance.</p>'],
            ['Multivitamines 90 caps', 'Bien-être', 9000, 11000, '<p>Complexe complet de vitamines et minéraux pour l\'énergie au quotidien.</p>'],
            ['Oméga-3 120 caps', 'Bien-être', 10000, null, '<p>Acides gras essentiels EPA/DHA pour le cœur et le cerveau.</p>'],
            ['Shaker SEZY 700 ml', 'Accessoires', 4000, null, '<p>Shaker sans BPA avec boule mélangeuse. Logo SEZY.</p>'],
            ['Ceinture de musculation', 'Accessoires', 15000, 18000, '<p>Ceinture en cuir renforcé pour vos charges lourdes.</p>'],
        ];

        foreach ($products as $i => [$name, $cat, $price, $compare, $desc]) {
            Product::create([
                'category_id' => $cats[$cat]->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $desc,
                'price' => $price,
                'compare_at_price' => $compare,
                'stock' => $i === 5 ? 0 : null,
                'is_active' => true,
                'is_featured' => $i < 2,
                'position' => $i,
            ]);
        }

        // Active la boutique.
        $shop = app(ShopSettings::class);
        $shop->enabled = true;
        $shop->save();
    }

    public function down(): void
    {
        Product::query()->forceDelete();
        ProductCategory::query()->delete();
        $shop = app(ShopSettings::class);
        $shop->enabled = false;
        $shop->save();
    }
};
