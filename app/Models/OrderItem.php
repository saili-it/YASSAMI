<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ligne de commande : nom, référence et prix sont figés au moment de l'achat. */
#[Fillable(['product_id', 'product_name', 'sku', 'size', 'color', 'unit_price', 'quantity', 'line_total'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Peut être null si le produit a été supprimé depuis. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
