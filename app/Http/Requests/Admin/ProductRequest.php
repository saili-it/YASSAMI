<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public const MAX_IMAGES = 10;

    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        // Tailles : valeurs uniques, sans vide, ordre conservé.
        $sizes = collect((array) $this->input('sizes', []))
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Couleurs : on ignore les lignes sans nom.
        $colors = collect((array) $this->input('colors', []))
            ->map(fn ($c) => ['name' => trim((string) ($c['name'] ?? '')), 'hex' => strtoupper(trim((string) ($c['hex'] ?? '')))])
            ->filter(fn ($c) => $c['name'] !== '')
            ->values()
            ->all();

        $decimal = fn ($v) => $v === null || $v === '' ? null : str_replace([' ', ','], ['', '.'], (string) $v);

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => $this->filled('slug') ? str($this->input('slug'))->slug()->toString() : null,
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'price' => $decimal($this->input('price')),
            'sale_price' => $decimal($this->input('sale_price')),
            'sizes' => $sizes,
            'colors' => $colors,
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/', Rule::unique('products', 'sku')->ignore($product)],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:999999'],
            'sizes' => ['array', 'max:20'],
            'sizes.*' => ['string', 'max:20'],
            'colors' => ['array', 'max:20'],
            'colors.*.name' => ['required', 'string', 'max:40'],
            'colors.*.hex' => ['required', 'regex:/^#[0-9A-F]{6}$/'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],

            'images' => ['array', 'max:'.self::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'image_order' => ['array'],
            'image_order.*' => ['integer'],
            'remove_images' => ['array'],
            'remove_images.*' => ['integer'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Product|null $product */
            $product = $this->route('product');
            $kept = $product
                ? $product->images()->whereNotIn('id', $this->input('remove_images', []))->count()
                : 0;
            $total = $kept + count($this->file('images', []));

            if ($total > self::MAX_IMAGES) {
                $validator->errors()->add('images', 'Un produit peut avoir au maximum '.self::MAX_IMAGES." photos ({$total} demandées).");
            }

            // Un produit visible à 0 DH serait commandable gratuitement.
            if ($this->boolean('is_active') && is_numeric($this->input('price')) && (float) $this->input('price') <= 0) {
                $validator->errors()->add('price', 'Renseignez un prix avant d’activer le produit.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'sku.regex' => 'La référence ne peut contenir que des lettres, chiffres, points, tirets et underscores.',
            'sale_price.lt' => 'Le prix remisé doit être inférieur au prix normal.',
            'colors.*.name.required' => 'Chaque couleur doit avoir un nom.',
            'colors.*.hex.regex' => 'Code couleur invalide (format #RRGGBB).',
            'images.*.image' => 'Chaque fichier doit être une image.',
            'images.*.mimes' => 'Formats acceptés : JPG, PNG, WebP.',
            'images.*.max' => 'Chaque photo doit faire moins de 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'catégorie',
            'name' => 'nom',
            'slug' => 'adresse (slug)',
            'sku' => 'référence (SKU)',
            'description' => 'description',
            'price' => 'prix',
            'sale_price' => 'prix remisé',
            'stock_quantity' => 'stock',
            'sizes' => 'tailles',
            'colors' => 'couleurs',
            'images' => 'photos',
        ];
    }

    /** Données de la table products uniquement (sans les champs d'images). */
    public function productData(): array
    {
        return $this->safe()->except(['images', 'image_order', 'remove_images']);
    }
}
