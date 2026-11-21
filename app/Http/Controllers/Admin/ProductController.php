<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageService;
use App\Support\Slug;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public const STATUSES = [
        'actifs' => 'Actifs',
        'inactifs' => 'Inactifs',
        'rupture' => 'En rupture',
        'vedette' => 'En vedette',
        'promo' => 'En promotion',
    ];

    public function __construct(private readonly ProductImageService $images) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->integer('categorie') ?: null;
        $status = array_key_exists((string) $request->query('statut'), self::STATUSES) ? $request->query('statut') : null;

        $products = Product::query()
            ->with(['category', 'images'])
            ->when($search !== '', function ($q) use ($search) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($status === 'actifs', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactifs', fn ($q) => $q->where('is_active', false))
            ->when($status === 'rupture', fn ($q) => $q->where('stock_quantity', 0))
            ->when($status === 'vedette', fn ($q) => $q->where('is_featured', true))
            ->when($status === 'promo', fn ($q) => $q->onSale())
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::ordered()->get(['id', 'name']),
            'search' => $search,
            'categoryId' => $categoryId,
            'status' => $status,
            'statuses' => self::STATUSES,
        ]);
    }

    public function create(Request $request): View
    {
        $product = new Product([
            'is_active' => true,
            'is_featured' => false,
            'stock_quantity' => 0,
            'category_id' => $request->integer('categorie') ?: null,
            'sizes' => [],
            'colors' => [],
        ]);
        $product->setRelation('images', collect());

        return $this->form($product);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->productData();
        $data['slug'] = Slug::unique(Product::class, $data['slug'] ?: $data['name']);

        $product = Product::create($data);
        $this->images->sync($product, $request->file('images', []));

        return redirect()->route('admin.products.edit', $product)
            ->with('status', "Produit « {$product->name} » créé.");
    }

    public function edit(Product $product): View
    {
        $product->load(['images', 'category']);

        return $this->form($product);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->productData();
        $data['slug'] = Slug::unique(Product::class, $data['slug'] ?: $data['name'], $product->id);

        $product->update($data);
        $this->images->sync(
            $product,
            $request->file('images', []),
            $request->input('image_order', []),
            $request->input('remove_images', []),
        );

        return redirect()->route('admin.products.edit', $product)
            ->with('status', "Produit « {$product->name} » enregistré.");
    }

    /** Bascule rapide actif / vedette depuis la liste. */
    public function toggle(Product $product, string $field): RedirectResponse
    {
        abort_unless(in_array($field, ['is_active', 'is_featured'], true), 404);

        if ($field === 'is_active' && ! $product->is_active && (float) $product->price <= 0) {
            return back()->with('error', "« {$product->name} » n’a pas encore de prix : ouvrez-le, renseignez le prix et le stock, puis activez-le.");
        }

        $product->update([$field => ! $product->{$field}]);

        $label = $field === 'is_active'
            ? ($product->is_active ? 'activé' : 'désactivé')
            : ($product->is_featured ? 'mis en vedette' : 'retiré de la vedette');

        return back()->with('status', "« {$product->name} » {$label}.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->images->deleteAll($product);
        $product->delete(); // les lignes product_images partent en cascade

        return redirect()->route('admin.products.index')
            ->with('status', "Produit « {$product->name} » supprimé.");
    }

    private function form(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::ordered()->get(['id', 'name', 'is_active']),
            'defaultSizes' => config('yassami.default_sizes'),
        ]);
    }
}
