<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Shorts', 'slug' => 'shorts']);
        $this->product = Product::create([
            'category_id' => $category->id, 'name' => 'Short Flex', 'slug' => 'short-flex', 'sku' => 'SHR-001',
            'price' => 199, 'stock_quantity' => 5, 'sizes' => ['M'], 'colors' => [],
        ]);
    }

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $this->actingAs($admin);
    }

    private function placeOrder(int $quantity = 2, string $name = 'Sara Alaoui', string $phone = '0612345678'): Order
    {
        return app(OrderService::class)->place(
            ['name' => $name, 'phone' => $phone, 'city' => 'Rabat', 'address' => '12 rue des Orangers'],
            [['product_id' => $this->product->id, 'quantity' => $quantity, 'size' => 'M', 'color' => null]],
        );
    }

    public function test_guests_cannot_see_orders(): void
    {
        $order = $this->placeOrder();

        $this->get(route('admin.orders.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.orders.show', $order))->assertRedirect(route('admin.login'));
        $this->put(route('admin.orders.update', $order), ['status' => 'annulee'])->assertRedirect(route('admin.login'));
        $this->assertSame(OrderStatus::New, $order->fresh()->status);
    }

    public function test_list_filters_and_search(): void
    {
        $first = $this->placeOrder(1, 'Sara Alaoui', '0612345678');
        $second = $this->placeOrder(1, 'Youssef Bennani', '0700112233');
        $second->update(['status' => OrderStatus::Confirmed]);

        $this->actingAsAdmin()->get(route('admin.orders.index'))->assertOk()
            ->assertSee($first->reference)->assertSee($second->reference);

        $this->get(route('admin.orders.index', ['statut' => 'confirmee']))->assertOk()
            ->assertSee('Youssef Bennani')->assertDontSee('Sara Alaoui');

        $this->get(route('admin.orders.index', ['q' => '06 12 34']))->assertOk()
            ->assertSee('Sara Alaoui')->assertDontSee('Youssef Bennani');

        $this->get(route('admin.orders.index', ['q' => $second->reference]))->assertOk()
            ->assertSee('Youssef Bennani')->assertDontSee('Sara Alaoui');

        // Pastille « nouvelles commandes » et carte du tableau de bord.
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('1 nouvelle commande à traiter');
    }

    public function test_order_detail_page(): void
    {
        $order = $this->placeOrder();

        $this->actingAsAdmin()->get(route('admin.orders.show', $order))->assertOk()
            ->assertSee($order->reference)
            ->assertSee('Short Flex')
            ->assertSee('06 12 34 56 78')
            ->assertSee('https://wa.me/212612345678', false)
            ->assertSee('À confirmer');
    }

    public function test_status_delivery_fee_and_note_are_saved(): void
    {
        $order = $this->placeOrder();

        $this->actingAsAdmin()->put(route('admin.orders.update', $order), [
            'status' => 'confirmee',
            'delivery_fee' => '35,50',
            'admin_note' => 'Livraison jeudi',
        ])->assertRedirect(route('admin.orders.show', $order))->assertSessionHas('status');

        $order->refresh();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame(35.5, (float) $order->delivery_fee);
        $this->assertSame('Livraison jeudi', $order->admin_note);
        $this->assertSame(433.5, $order->total); // 2 × 199 + 35,50
        $this->assertSame(3, $this->product->fresh()->stock_quantity);
    }

    public function test_cancelling_restores_stock_and_reactivating_reserves_it_again(): void
    {
        $order = $this->placeOrder(2);
        $this->assertSame(3, $this->product->fresh()->stock_quantity);

        $this->actingAsAdmin()->put(route('admin.orders.update', $order), ['status' => 'annulee']);
        $this->assertSame(5, $this->product->fresh()->stock_quantity);

        // Annuler deux fois ne rend pas le stock deux fois.
        $this->put(route('admin.orders.update', $order), ['status' => 'annulee']);
        $this->assertSame(5, $this->product->fresh()->stock_quantity);

        $this->put(route('admin.orders.update', $order), ['status' => 'nouvelle']);
        $this->assertSame(3, $this->product->fresh()->stock_quantity);
        $this->assertSame(OrderStatus::New, $order->fresh()->status);
    }

    public function test_reactivation_is_refused_when_stock_is_gone(): void
    {
        $order = $this->placeOrder(2);
        $this->actingAsAdmin()->put(route('admin.orders.update', $order), ['status' => 'annulee']);
        $this->product->update(['stock_quantity' => 1]);

        $this->put(route('admin.orders.update', $order), ['status' => 'confirmee'])
            ->assertSessionHas('error');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(1, $this->product->fresh()->stock_quantity);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $order = $this->placeOrder();

        $this->actingAsAdmin()->put(route('admin.orders.update', $order), ['status' => 'inconnu', 'delivery_fee' => '-5'])
            ->assertSessionHasErrors(['status', 'delivery_fee']);
    }

    public function test_deleting_a_product_keeps_its_orders(): void
    {
        $order = $this->placeOrder();
        $this->product->delete();

        $this->actingAsAdmin()->get(route('admin.orders.show', $order))->assertOk()->assertSee('Short Flex');
        $this->assertNull($order->items()->first()->product_id);
    }
}
