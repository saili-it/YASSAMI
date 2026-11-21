<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $status = OrderStatus::tryFrom((string) $request->query('statut'));
        $search = trim((string) $request->query('q'));

        $orders = Order::query()
            ->when($status, fn ($q) => $q->status($status))
            ->when($search !== '', fn ($q) => $q->search($search))
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'search' => $search,
            'counts' => Order::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load('items.product.images');

        return view('admin.orders.show', ['order' => $order]);
    }

    public function update(OrderRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();
        $status = OrderStatus::from($data['status']);

        try {
            // Tout ou rien : statut (et stock), frais et note sont enregistrés ensemble.
            DB::transaction(function () use ($order, $status, $data) {
                $this->orders->changeStatus($order->load('items'), $status);
                $order->update([
                    'delivery_fee' => $data['delivery_fee'],
                    'admin_note' => $data['admin_note'],
                ]);
            });
        } catch (ValidationException $e) {
            return back()->withInput()->with('error', 'Impossible de réactiver la commande : '.$e->validator->errors()->first());
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('status', "Commande {$order->reference} mise à jour ({$status->label()}).");
    }
}
