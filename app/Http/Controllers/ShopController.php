<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public const SORTS = [
        'nouveautes' => 'Nouveautés',
        'prix-asc' => 'Prix croissant',
        'prix-desc' => 'Prix décroissant',
    ];

    public function index(Request $request, ?Category $category = null): View
    {
        abort_if($category && ! $category->is_active, 404);

        $search = trim((string) $request->query('q', ''));
        $sort = array_key_exists($request->query('tri'), self::SORTS) ? $request->query('tri') : 'nouveautes';
        $onlyPromo = $request->boolean('promo');

        $products = Product::visible()
            ->with(['images', 'category'])
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($onlyPromo, fn ($q) => $q->onSale())
            ->when($search !== '', function ($q) use ($search) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_substr($search, 0, 80)).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->when($sort === 'prix-asc', fn ($q) => $q->orderByEffectivePrice('asc'))
            ->when($sort === 'prix-desc', fn ($q) => $q->orderByEffectivePrice('desc'))
            ->when($sort === 'nouveautes', fn ($q) => $q->latest())
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('pages.shop', [
            'products' => $products,
            'category' => $category,
            'categories' => Category::active()->ordered()->get(),
            'search' => $search,
            'sort' => $sort,
            'sorts' => self::SORTS,
            'onlyPromo' => $onlyPromo,
        ]);
    }
}
