<?php

namespace App\Providers;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Services\SiteSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Une seule instance par requête : les paramètres admin ne sont lus qu'une fois.
        $this->app->scoped(SiteSettings::class);
    }

    public function boot(): void
    {
        // Détecte les requêtes N+1 et les attributs inexistants en développement.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Catégories de navigation partagées par l'en-tête, le menu mobile et le pied de page.
        View::composer('components.layouts.app', function ($view) {
            $view->with('navCategories', once(fn () => Category::active()->ordered()->get(['id', 'name', 'slug'])));
        });

        // Nombre de commandes à traiter, affiché en pastille dans le menu de l'administration.
        View::composer('components.layouts.admin', function ($view) {
            $view->with('newOrdersCount', once(fn () => Order::status(OrderStatus::New)->count()));
        });
    }
}
