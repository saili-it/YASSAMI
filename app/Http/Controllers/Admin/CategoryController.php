<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ImageStorage;
use App\Support\Slug;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly ImageStorage $images) {}

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::ordered()->withCount('products')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category([
                'is_active' => true,
                'sort_order' => (int) Category::max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['slug'] = Slug::unique(Category::class, $data['slug'] ?: $data['name']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->images->store($request->file('image'), 'categories');
        }

        $category = Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('status', "Catégorie « {$category->name} » créée.");
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['slug'] = Slug::unique(Category::class, $data['slug'] ?: $data['name'], $category->id);

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $this->images->delete($category->image);
            $data['image'] = $request->hasFile('image')
                ? $this->images->store($request->file('image'), 'categories')
                : null;
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('status', "Catégorie « {$category->name} » mise à jour.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $count = $category->products()->count();

        if ($count > 0) {
            return back()->with('error', "Impossible de supprimer « {$category->name} » : elle contient {$count} produit(s). Déplacez-les ou supprimez-les d’abord, ou désactivez simplement la catégorie.");
        }

        $this->images->delete($category->image);
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('status', "Catégorie « {$category->name} » supprimée.");
    }
}
