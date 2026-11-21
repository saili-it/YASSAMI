<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Shorts', 'slug' => 'shorts']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Short Test',
            'slug' => 'short-test',
            'sku' => 'TST-001',
            'price' => 200,
            'sale_price' => 150,
            'sizes' => ['S', 'M'],
            'colors' => [['name' => 'Noir', 'hex' => '#111111']],
            'stock_quantity' => 5,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'customer' => [
                'name' => 'Sara Alaoui',
                'phone' => '+212 6 12 34 56 78',
                'city' => 'Rabat',
                'address' => '12 rue des Orangers, Agdal',
            ],
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'size' => 'M', 'color' => 'Noir'],
            ],
        ], $overrides);
    }

    public function test_order_is_saved_with_server_side_prices_and_stock_is_reserved(): void
    {
        $payload = $this->payload();
        $payload['items'][0]['price'] = 1; // prix envoyé par le navigateur : ignoré

        $this->postJson(route('orders.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('reference', 'YS-000001')
            ->assertJsonPath('redirect', route('orders.confirmation'));

        $order = Order::with('items')->sole();
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame('Sara Alaoui', $order->customer_name);
        $this->assertSame('0612345678', $order->customer_phone);
        $this->assertSame(2, $order->items_count);
        $this->assertSame(300.0, (float) $order->subtotal); // prix remisé (150) appliqué
        $this->assertNull($order->delivery_fee);

        $item = $order->items->sole();
        $this->assertSame(['Short Test', 'TST-001', 'M', 'Noir', 2, 150.0, 300.0], [
            $item->product_name, $item->sku, $item->size, $item->color, $item->quantity, (float) $item->unit_price, (float) $item->line_total,
        ]);

        $this->assertSame(3, $this->product->fresh()->stock_quantity);
    }

    public function test_confirmation_page_shows_only_the_visitors_own_order(): void
    {
        $this->get(route('orders.confirmation'))->assertRedirect(route('shop.index'));

        $this->postJson(route('orders.store'), $this->payload())->assertCreated();

        $this->get(route('orders.confirmation'))->assertOk()
            ->assertSee('YS-000001')
            ->assertSee('Merci Sara')
            ->assertSee('06 12 34 56 78')
            ->assertSee('300 DH');
    }

    public function test_customer_fields_are_required_and_phone_is_validated(): void
    {
        $this->postJson(route('orders.store'), $this->payload(['customer' => ['name' => '', 'phone' => '12345']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer.name', 'customer.phone']);

        $this->assertSame(0, Order::count());
    }

    public function test_invalid_variant_is_rejected(): void
    {
        $this->postJson(route('orders.store'), $this->payload(['items' => [['size' => 'XXL']]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0']);
    }

    public function test_quantity_cannot_exceed_stock_across_lines(): void
    {
        $payload = $this->payload();
        $payload['items'][] = ['product_id' => $this->product->id, 'quantity' => 4, 'size' => 'S', 'color' => 'Noir'];

        $this->postJson(route('orders.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        $this->assertSame(5, $this->product->fresh()->stock_quantity);
    }

    public function test_inactive_products_cannot_be_ordered(): void
    {
        $this->product->update(['is_active' => false]);

        $this->postJson(route('orders.store'), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0']);
    }

    public function test_fixed_delivery_fee_is_added_and_frozen_on_the_order(): void
    {
        config(['yassami.delivery_fee' => '35']);

        $this->postJson(route('orders.store'), $this->payload())->assertCreated();

        $order = Order::sole();
        $this->assertSame(35.0, (float) $order->delivery_fee);
        $this->assertSame(335.0, $order->total); // 2 × 150 + 35

        $this->get(route('orders.confirmation'))->assertOk()
            ->assertSee('35 DH')
            ->assertSee('335 DH')
            ->assertDontSee('et les frais de livraison');

        // Changer le réglage ne modifie pas les commandes déjà passées.
        config(['yassami.delivery_fee' => '50']);
        $this->assertSame(35.0, (float) $order->fresh()->delivery_fee);
    }

    public function test_free_delivery_and_unset_delivery(): void
    {
        config(['yassami.delivery_fee' => '0']);
        $this->postJson(route('orders.store'), $this->payload())->assertCreated();
        $this->assertSame(0.0, (float) Order::latest('id')->first()->delivery_fee);
        $this->get(route('orders.confirmation'))->assertSee('Gratuite');

        config(['yassami.delivery_fee' => null]);
        $this->postJson(route('orders.store'), $this->payload(['items' => [['quantity' => 1]]]))->assertCreated();
        $this->assertNull(Order::latest('id')->first()->delivery_fee);
        $this->get(route('orders.confirmation'))->assertSee('À confirmer avec vous')->assertSee('et les frais de livraison');
    }

    public function test_ordering_works_without_any_whatsapp_number(): void
    {
        config(['yassami.whatsapp_number' => '']);

        $this->postJson(route('orders.store'), $this->payload())->assertCreated();
    }
}
