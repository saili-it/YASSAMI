<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Support\Phone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Commandes passées sur la boutique.
 * Les prix sont TOUJOURS ceux de la base, jamais ceux envoyés par le navigateur.
 * Le stock est réservé à la commande et rendu si elle est annulée.
 */
class OrderService
{
    /**
     * @param  array{name:string, phone:string, city:string, address:string}  $customer
     * @param  array<int, array{product_id:int, quantity:int, size:?string, color:?string}>  $items
     */
    public function place(array $customer, array $items): Order
    {
        return DB::transaction(function () use ($customer, $items) {
            // Verrouille les produits : deux commandes simultanées ne peuvent pas vendre le même dernier article.
            $products = Product::query()
                ->whereIn('id', collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = $this->lines($items, $products);
            $this->reserveStock($lines, $products);

            $order = Order::create([
                'customer_name' => $customer['name'],
                'customer_phone' => str_replace(' ', '', Phone::format($customer['phone'])), // toujours 06XXXXXXXX
                'customer_city' => $customer['city'],
                'customer_address' => $customer['address'],
                'items_count' => $lines->sum('quantity'),
                'subtotal' => round($lines->sum('line_total'), 2),
            ]);
            $order->forceFill(['reference' => 'YS-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)])->save();
            $order->items()->createMany($lines->all());

            return $order;
        });
    }

    /**
     * Change le statut et ajuste le stock : annuler rend les articles,
     * réactiver une commande annulée les réserve à nouveau (si le stock le permet).
     */
    public function changeStatus(Order $order, OrderStatus $status): void
    {
        if ($order->status === $status) {
            return;
        }

        DB::transaction(function () use ($order, $status) {
            $wasHolding = $order->status->holdsStock();
            $willHold = $status->holdsStock();

            if ($wasHolding && ! $willHold) {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
                    }
                }
            } elseif (! $wasHolding && $willHold) {
                $lines = $order->items->whereNotNull('product_id');
                $products = Product::whereIn('id', $lines->pluck('product_id')->unique())->lockForUpdate()->get()->keyBy('id');
                $this->reserveStock($lines->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                ]), $products, 'status');
            }

            $order->update(['status' => $status]);
        });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function lines(array $items, Collection $products): Collection
    {
        return collect($items)->map(function (array $item) use ($products) {
            $product = $products->get((int) $item['product_id']);
            $quantity = (int) $item['quantity'];

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'size' => $item['size'] ?? null,
                'color' => $item['color'] ?? null,
                'unit_price' => $product->effective_price,
                'quantity' => $quantity,
                'line_total' => round($product->effective_price * $quantity, 2),
            ];
        })->values();
    }

    /** Décrémente le stock, ou lève une erreur de validation si un article n'est plus disponible. */
    private function reserveStock(Collection $lines, Collection $products, string $errorKey = 'items'): void
    {
        foreach ($lines->groupBy('product_id') as $productId => $group) {
            $product = $products->get($productId);
            $wanted = $group->sum('quantity');

            if (! $product || $product->stock_quantity < $wanted) {
                $name = $product->name ?? $group->first()['product_name'];
                $left = $product->stock_quantity ?? 0;

                throw ValidationException::withMessages([$errorKey => $left === 0
                    ? "« {$name} » est en rupture de stock."
                    : "Il ne reste que {$left} « {$name} » en stock."]);
            }

            $product->decrement('stock_quantity', $wanted);
        }
    }
}
