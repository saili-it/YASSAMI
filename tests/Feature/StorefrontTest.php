<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Leggings', 'slug' => 'leggings']);
    }

    private function product(string $name, float $price, array $extra = []): Product
    {
        return Product::create($extra + [
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => str($name)->slug(),
            'sku' => strtoupper(str($name)->slug()),
            'price' => $price,
            'stock_quantity' => 10,
        ]);
    }

    public function test_public_pages_render(): void
    {
        $product = $this->product('Legging Alpha', 300);

        $this->get('/')->assertOk()->assertSee('Legging Alpha');
        $this->get(route('shop.index'))->assertOk();
        $this->get(route('shop.category', $this->category))->assertOk()->assertSee('Legging Alpha');
        $this->get(route('products.show', $product))->assertOk()->assertSee('application/ld+json', false);
        $this->get(route('cart'))->assertOk();
    }

    public function test_floating_whatsapp_button(): void
    {
        $product = $this->product('Legging Alpha', 300);

        // Sans numéro : aucun bouton (jamais de numéro inventé).
        config(['yassami.whatsapp_number' => '']);
        $this->get('/')->assertOk()->assertDontSee('data-whatsapp-float', false);

        config(['yassami.whatsapp_number' => '212612345678']);

        foreach (['/', route('shop.index'), route('cart')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('data-whatsapp-float', false)
                ->assertSee('https://wa.me/212612345678?text='.rawurlencode('Bonjour YASSAMI, j’ai une question.'), false);
        }

        // Fiche produit : le message cite le produit, sa référence et son lien.
        $message = 'Bonjour, je suis intéressé(e) par « Legging Alpha » (réf. '.$product->sku.') : '.route('products.show', $product);
        $this->get(route('products.show', $product))->assertOk()
            ->assertSee('https://wa.me/212612345678?text='.rawurlencode($message), false);

        // Jamais dans l'administration.
        $this->get(route('admin.login'))->assertOk()->assertDontSee('data-whatsapp-float', false);
    }

    public function test_inactive_products_and_categories_are_hidden(): void
    {
        $hidden = $this->product('Legging Cachee', 300, ['is_active' => false]);
        $this->get(route('products.show', $hidden))->assertNotFound();
        $this->get(route('shop.index'))->assertDontSee('Legging Cachee');

        $visible = $this->product('Legging Visible', 300);
        $this->category->update(['is_active' => false]);
        $this->get(route('products.show', $visible))->assertNotFound();
        $this->get(route('shop.category', $this->category))->assertNotFound();
    }

    public function test_shop_sorts_by_effective_price_and_searches(): void
    {
        $this->product('Legging Cher', 500);
        $this->product('Legging Remise', 450, ['sale_price' => 100]);
        $this->product('Legging Moyen', 250);

        $this->get(route('shop.index', ['tri' => 'prix-asc']))
            ->assertSeeInOrder(['Legging Remise', 'Legging Moyen', 'Legging Cher']);

        $this->get(route('shop.index', ['q' => 'moyen']))
            ->assertSee('Legging Moyen')
            ->assertDontSee('Legging Cher');

        $this->get(route('shop.index', ['promo' => 1]))
            ->assertSee('Legging Remise')
            ->assertDontSee('Legging Moyen');
    }
}
