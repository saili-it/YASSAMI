<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse
    {
        $data = $request->validated();
        $order = $orders->place($data['customer'], $data['items']);

        // La page de confirmation n'affiche que la commande de CE visiteur (jamais via un id dans l'URL).
        $request->session()->put('orders.last', $order->id);

        return response()->json([
            'reference' => $order->reference,
            'redirect' => route('orders.confirmation'),
        ], 201);
    }

    public function confirmation(): View|RedirectResponse
    {
        $order = Order::with('items')->find(session('orders.last'));

        if (! $order) {
            return redirect()->route('shop.index');
        }

        return view('pages.order-confirmation', ['order' => $order]);
    }
}
