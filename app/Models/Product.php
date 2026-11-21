<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id', 'name', 'slug', 'sku', 'description', 'price', 'sale_price',
    'sizes', 'colors', 'stock_quantity', 'is_featured', 'is_active',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'sizes' => 'array',
            'colors' => 'array',
            'stock_quantity' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------------------------------------------------------------- Relations

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    // ------------------------------------------------------------------- Scopes

    /** Produits visibles en boutique : actifs ET dans une catégorie active. */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function scopeOnSale(Builder $query): void
    {
        $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price');
    }

    /** Tri par prix effectif (prix remisé s'il existe). */
    public function scopeOrderByEffectivePrice(Builder $query, string $direction = 'asc'): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $query->orderByRaw("COALESCE(sale_price, price) {$direction}");
    }

    // ---------------------------------------------------------------- Accessors

    protected function hasDiscount(): Attribute
    {
        return Attribute::get(fn () => $this->sale_price !== null && (float) $this->sale_price < (float) $this->price);
    }

    /** Prix réellement payé (en MAD). */
    protected function effectivePrice(): Attribute
    {
        return Attribute::get(fn () => (float) ($this->has_discount ? $this->sale_price : $this->price));
    }

    protected function discountPercent(): Attribute
    {
        return Attribute::get(fn () => $this->has_discount
            ? (int) round((1 - (float) $this->sale_price / (float) $this->price) * 100)
            : 0);
    }

    protected function inStock(): Attribute
    {
        return Attribute::get(fn () => $this->stock_quantity > 0);
    }

    protected function isLowStock(): Attribute
    {
        return Attribute::get(fn () => $this->in_stock
            && $this->stock_quantity <= config('yassami.low_stock_threshold'));
    }

    protected function primaryImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->images->first()?->url);
    }

    /** Couleurs normalisées : [['name' => 'Noir', 'hex' => '#111111'], …] */
    protected function colorOptions(): Attribute
    {
        return Attribute::get(fn () => collect($this->colors ?? [])
            ->filter(fn ($c) => is_array($c) && ! empty($c['name']))
            ->map(fn ($c) => ['name' => (string) $c['name'], 'hex' => $c['hex'] ?? '#888888'])
            ->values()
            ->all());
    }
}
