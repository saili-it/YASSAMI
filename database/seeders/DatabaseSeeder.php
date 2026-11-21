<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Aucun compte administrateur n'est créé ici (pas de mot de passe par défaut).
     * Le catalogue de démonstration n'est chargé qu'en environnement local.
     */
    public function run(): void
    {
        // Jamais de produits fictifs mélangés au vrai catalogue (importé le 2026-11-18).
        if (app()->environment('local') && Product::count() === 0) {
            $this->call(DemoCatalogSeeder::class);
        } elseif (app()->environment('local')) {
            $this->command?->warn('Catalogue réel présent : catalogue de démonstration ignoré.');
        }
    }
}
