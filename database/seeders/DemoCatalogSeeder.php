<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * CATALOGUE DE DÉMONSTRATION — données fictives pour le développement.
 * Noms, prix et stocks NE SONT PAS des informations commerciales réelles.
 * À ne jamais exécuter en production.
 *
 * Idempotent : updateOrCreate sur les slugs.
 */
class DemoCatalogSeeder extends Seeder
{
    private const BLACK = ['name' => 'Noir', 'hex' => '#111111'];

    private const WHITE = ['name' => 'Blanc', 'hex' => '#F4F4F2'];

    private const GREY = ['name' => 'Gris chiné', 'hex' => '#9A9A9A'];

    private const NAVY = ['name' => 'Marine', 'hex' => '#1C2541'];

    private const SAND = ['name' => 'Sable', 'hex' => '#CDBA9A'];

    private const RED = ['name' => 'Rouge', 'hex' => '#D7261E'];

    private const OLIVE = ['name' => 'Olive', 'hex' => '#5B6142'];

    public function run(): void
    {
        $categories = [
            ['name' => 'T-shirts & Tops', 'description' => 'Hauts techniques respirants pour l’entraînement et le quotidien.'],
            ['name' => 'Shorts', 'description' => 'Liberté de mouvement, séchage rapide.'],
            ['name' => 'Leggings', 'description' => 'Maintien et confort, de la salle au running.'],
            ['name' => 'Sweats & Vestes', 'description' => 'Couches chaudes pour l’échauffement et la récupération.'],
            ['name' => 'Brassières', 'description' => 'Maintien adapté à chaque intensité.'],
            ['name' => 'Accessoires', 'description' => 'Sacs, gourdes, casquettes et équipements.'],
        ];

        $cat = [];
        foreach ($categories as $i => $data) {
            $cat[$data['name']] = Category::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                $data + ['is_active' => true, 'sort_order' => $i + 1],
            );
        }

        $apparel = ['XS', 'S', 'M', 'L', 'XL'];

        // [nom, catégorie, prix, prix remisé, tailles, couleurs, stock, vedette]
        $products = [
            ['T-shirt Training Core', 'T-shirts & Tops', 199, null, $apparel, [self::BLACK, self::WHITE, self::NAVY], 40, true],
            ['T-shirt Oversize Atlas', 'T-shirts & Tops', 249, 199, ['S', 'M', 'L', 'XL'], [self::SAND, self::BLACK], 25, true],
            ['Débardeur Performance', 'T-shirts & Tops', 169, null, $apparel, [self::GREY, self::BLACK], 3, false],
            ['Top Manches Longues Run', 'T-shirts & Tops', 279, null, $apparel, [self::RED, self::BLACK], 18, false],
            ['Short Running 2-en-1', 'Shorts', 229, null, ['S', 'M', 'L', 'XL'], [self::BLACK, self::NAVY], 30, true],
            ['Short Training Flex', 'Shorts', 189, 149, $apparel, [self::OLIVE, self::BLACK], 22, false],
            ['Legging Sculpt Taille Haute', 'Leggings', 349, null, ['XS', 'S', 'M', 'L'], [self::BLACK, self::OLIVE], 35, true],
            ['Legging 7/8 Studio', 'Leggings', 299, 239, ['XS', 'S', 'M', 'L'], [self::NAVY, self::SAND], 12, false],
            ['Sweat à Capuche Essential', 'Sweats & Vestes', 449, null, $apparel, [self::GREY, self::BLACK, self::SAND], 20, true],
            ['Veste Zippée Warm-Up', 'Sweats & Vestes', 499, 399, ['S', 'M', 'L', 'XL'], [self::BLACK, self::NAVY], 0, false],
            ['Jogging Fuselé Tech', 'Sweats & Vestes', 379, null, $apparel, [self::BLACK, self::GREY], 28, false],
            ['Brassière Maintien Fort', 'Brassières', 259, null, ['XS', 'S', 'M', 'L'], [self::BLACK, self::RED], 16, true],
            ['Brassière Seamless Flow', 'Brassières', 219, 179, ['XS', 'S', 'M', 'L'], [self::SAND, self::OLIVE], 9, false],
            ['Sac de Sport Duffel 40L', 'Accessoires', 399, null, ['Unique'], [self::BLACK], 14, true],
            ['Gourde Isotherme 750 ml', 'Accessoires', 149, null, ['Unique'], [self::BLACK, self::WHITE], 50, false],
            ['Casquette Running Light', 'Accessoires', 129, 99, ['Unique'], [self::BLACK, self::WHITE, self::RED], 4, false],
        ];

        foreach ($products as $i => [$name, $category, $price, $sale, $sizes, $colors, $stock, $featured]) {
            $slug = Str::slug($name);

            Product::updateOrCreate(['slug' => $slug], [
                'category_id' => $cat[$category]->id,
                'name' => $name,
                'sku' => 'DEMO-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'description' => "Produit de démonstration. {$name} : coupe pensée pour le mouvement, matière respirante et finitions soignées. Remplacez ce texte depuis l’administration.",
                'price' => $price,
                'sale_price' => $sale,
                'sizes' => $sizes,
                'colors' => $colors,
                'stock_quantity' => $stock,
                'is_featured' => $featured,
                'is_active' => true,
            ])->forceFill([
                // Étale les dates pour que « Nouveautés » ait un ordre stable.
                'created_at' => now()->subDays(count($products) - $i),
            ])->save();
        }
    }
}
