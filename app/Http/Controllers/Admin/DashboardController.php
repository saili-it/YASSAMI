<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $threshold = config('yassami.low_stock_threshold');

        return view('admin.dashboard', [
            'stats' => [
                'products' => Product::count(),
                'categories' => Category::count(),
                'active' => Product::where('is_active', true)->count(),
                'outOfStock' => Product::where('stock_quantity', 0)->count(),
            ],
            'outOfStock' => Product::with(['images', 'category'])->where('stock_quantity', 0)->latest('updated_at')->take(6)->get(),
            'lowStock' => Product::with(['images', 'category'])
                ->whereBetween('stock_quantity', [1, $threshold])
                ->orderBy('stock_quantity')
                ->take(6)
                ->get(),
            'latest' => Product::with(['images', 'category'])->latest()->take(5)->get(),
            'newOrders' => Order::status(OrderStatus::New)->count(),
            'latestOrders' => Order::latest()->latest('id')->take(6)->get(),
        ]);
    }
}
