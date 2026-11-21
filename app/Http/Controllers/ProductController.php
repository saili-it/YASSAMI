<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        $product->load(['images', 'category']);

        abort_unless($product->is_active && $product->category?->is_active, 404);

        return view('pages.product', [
            'product' => $product,
            'related' => Product::visible()
                ->with(['images', 'category'])
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->latest()
                ->take(4)
                ->get(),
        ]);
    }
}
