<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
    }

    private function productPayload(Category $category, array $overrides = []): array
    {
        return $overrides + [
            'category_id' => $category->id,
            'name' => 'Short Flex',
            'sku' => 'shr-001',
            'description' => 'Short léger.',
            'price' => '199',
            'sale_price' => '149,50',
            'stock_quantity' => 12,
            'sizes' => ['S', 'M', 'M', ''],
            'colors' => [['name' => 'Noir', 'hex' => '#111111'], ['name' => '', 'hex' => '#ffffff']],
            'is_active' => '1',
            'is_featured' => '0',
        ];
    }

    // ------------------------------------------------------------ Pages

    public function test_admin_pages_render(): void
    {
        $category = Category::create(['name' => 'Shorts', 'slug' => 'shorts']);
        $product = Product::create($this->productPayload($category, ['slug' => 'short-flex', 'sku' => 'SHR-001', 'sale_price' => null, 'sizes' => ['M'], 'colors' => []]));

        foreach ([
            route('admin.dashboard'),
            route('admin.categories.index'),
            route('admin.categories.create'),
            route('admin.categories.edit', $category),
            route('admin.products.index'),
            route('admin.products.index', ['q' => 'flex', 'statut' => 'actifs', 'categorie' => $category->id]),
            route('admin.products.create'),
            route('admin.products.edit', $product),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->assertStringContainsString("/admin/produits/{$product->id}/modifier", route('admin.products.edit', $product));
    }

    // ------------------------------------------------------- Catégories

    public function test_category_crud_with_image(): void
    {
        $this->post(route('admin.categories.store'), [
            'name' => 'T-shirts & Tops',
            'description' => 'Hauts',
            'is_active' => '1',
            'sort_order' => 3,
            'image' => UploadedFile::fake()->image('cat.jpg', 2400, 1600),
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::firstOrFail();
        $this->assertSame('t-shirts-tops', $category->slug);
        Storage::disk('public')->assertExists($category->image);
        $this->assertStringEndsWith('.webp', $category->image);

        $old = $category->image;
        $this->put(route('admin.categories.update', $category), [
            'name' => 'Hauts',
            'slug' => 'hauts',
            'is_active' => '0',
            'sort_order' => 1,
            'remove_image' => '1',
        ])->assertRedirect(route('admin.categories.index'));

        $category->refresh();
        $this->assertSame('hauts', $category->slug);
        $this->assertFalse($category->is_active);
        $this->assertNull($category->image);
        Storage::disk('public')->assertMissing($old);

        $this->delete(route('admin.categories.destroy', $category))->assertRedirect();
        $this->assertModelMissing($category);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::create(['name' => 'Shorts', 'slug' => 'shorts']);
        Product::create($this->productPayload($category, ['slug' => 'short-flex', 'sku' => 'SHR-001', 'sale_price' => null, 'sizes' => [], 'colors' => []]));

        $this->delete(route('admin.categories.destroy', $category))->assertSessionHas('error');
        $this->assertModelExists($category);
    }

    public function test_duplicate_category_names_get_unique_slugs(): void
    {
        $this->post(route('admin.categories.store'), ['name' => 'Shorts']);
        $this->post(route('admin.categories.store'), ['name' => 'Shorts']);

        $this->assertSame(['shorts', 'shorts-2'], Category::orderBy('id')->pluck('slug')->all());
    }

    // --------------------------------------------------------- Produits

    public function test_product_can_be_created_with_variants_and_optimized_images(): void
    {
        $category = Category::create(['name' => 'Shorts', 'slug' => 'shorts']);

        $response = $this->post(route('admin.products.store'), $this->productPayload($category, [
            'images' => [
                UploadedFile::fake()->image('a.jpg', 4000, 5000),
                UploadedFile::fake()->image('b.png', 800, 1000),
            ],
        ]));

        $product = Product::firstOrFail();
        $response->assertRedirect(route('admin.products.edit', $product));

        $this->assertSame('short-flex', $product->slug);
        $this->assertSame('SHR-001', $product->sku);
        $this->assertSame(149.5, (float) $product->sale_price);
        $this->assertSame(['S', 'M'], $product->sizes);
        $this->assertSame([['name' => 'Noir', 'hex' => '#111111']], $product->colors);

        $images = $product->images;
        $this->assertCount(2, $images);
        $this->assertSame([0, 1], $images->pluck('sort_order')->all());

        // Redimensionnée à 1600 px max et convertie en WebP.
        $first = Storage::disk('public')->path($images[0]->path);
        [$w, $h, $type] = getimagesize($first);
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame(1600, max($w, $h));
    }

    public function test_product_validation_errors(): void
    {
        $category = Category::create(['name' => 'Shorts', 'slug' => 'shorts']);
        Product::create($this->productPayload($category, ['slug' => 'existing', 'sku' => 'TAKEN', 'sale_price' => null, 'sizes' => [], 'colors' => []]));

        $this->post(route('admin.products.store'), $this->productPayload($category, [
            'sku' => 'taken',
            'price' => '100',
            'sale_price' => '150',
            'colors' => [['name' => 'Noir', 'hex' => 'black']],
            'images' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
        ]))->assertSessionHasErrors(['sku', 'sale_price', 'colors.0.hex', 'images.0']);

        $this->assertSame(1, Product::count());
    }

    public function test_product_update_reorders_removes_and_adds_images(): void
    {
        $category = Category::create(['name' => 'Shorts', 'slug' => 'shorts']);
        $this->post(route('admin.products.store'), $this->productPayload($category, [
            'images' => [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
            ],
        ]));
        $product = Product::firstOrFail();
        [$one, $two, $three] = $product->images->all();

        $this->put(route('admin.products.update', $product), $this->productPayload($category, [
            'name' => 'Short Flex V2',
            'image_order' => [$three->id, $one->id],
            'remove_images' => [$two->id],
            'images' => [UploadedFile::fake()->image('4.jpg')],
        ]))->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $images = $product->images;

        $this->assertSame('short-flex-v2', $product->slug);
        $this->assertSame([$three->id, $one->id], $images->take(2)->pluck('id')->all());
        $this->assertCount(3, $images);
        $this->assertSame([0, 1, 2], $images->pluck('sort_order')->all());
        $this->assertModelMissing($two);
        Storage::disk('public')->assertMissing($two->path);
    }

    public function test_image_limit_is_enforced(): void
    {
        $category = Category::create(['name' => 'Shorts', 'slug' => 'shorts']);

        $this->post(route('admin.products.store'), $this->productPayload($category, [
            'images' => array_map(fn ($i) => UploadedFile::fake()->image("{$i}.jpg", 10, 10), range(1, 11)),
        ]))->assertSessionHasErrors('images');

        $this->assertSame(0, Product::count());
    }

    public function test_toggles_and_delete(): void
    {
        $category = Category::create(['name' => 'Shorts', 'slug' => 'shorts']);
        $this->post(route('admin.products.store'), $this->productPayload($category, ['images' => [UploadedFile::fake()->image('1.jpg')]]));
        $product = Product::firstOrFail();
        $path = $product->images->first()->path;

        $this->patch(route('admin.products.toggle', [$product, 'is_active']))->assertRedirect();
        $this->patch(route('admin.products.toggle', [$product, 'is_featured']))->assertRedirect();
        $product->refresh();
        $this->assertFalse($product->is_active);
        $this->assertTrue($product->is_featured);

        $this->patch("/admin/produits/{$product->id}/basculer/price")->assertNotFound();

        $this->delete(route('admin.products.destroy', $product))->assertRedirect(route('admin.products.index'));
        $this->assertModelMissing($product);
        $this->assertDatabaseCount('product_images', 0);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_product_without_price_cannot_be_activated(): void
    {
        $category = Category::create(['name' => 'Gants', 'slug' => 'gants']);

        // Formulaire : actif + 0 DH refusé, brouillon inactif à 0 DH accepté.
        $this->post(route('admin.products.store'), $this->productPayload($category, ['price' => '0', 'sale_price' => '']))
            ->assertSessionHasErrors('price');
        $this->post(route('admin.products.store'), $this->productPayload($category, ['price' => '0', 'sale_price' => '', 'is_active' => '0']))
            ->assertSessionHasNoErrors();

        // Bascule rapide : refusée tant que le prix est à 0.
        $draft = Product::sole();
        $this->patch(route('admin.products.toggle', [$draft, 'is_active']))->assertSessionHas('error');
        $this->assertFalse($draft->fresh()->is_active);

        $draft->update(['price' => 349]);
        $this->patch(route('admin.products.toggle', [$draft, 'is_active']))->assertSessionHas('status');
        $this->assertTrue($draft->fresh()->is_active);
    }
}
