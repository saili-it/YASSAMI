<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $base = fn () => Product::visible()->with(['images', 'category']);

        return view('pages.home', [
            'categories' => Category::active()->ordered()
                ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
                ->get(),
            'newArrivals' => $base()->latest()->take(8)->get(),
            // Pas encore de suivi des ventes : « Meilleures ventes » = produits mis en vedette par l'admin.
            'bestSellers' => $base()->featured()->latest()->take(8)->get(),
        ]);
    }
}
