<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'status', 'customer_name', 'customer_phone', 'customer_city', 'customer_address',
    'items_count', 'subtotal', 'delivery_fee', 'admin_note',
])]
class Order extends Model
{
    protected $attributes = [
        'status' => 'nouvelle',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'items_count' => 'integer',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    public function scopeStatus(Builder $query, OrderStatus $status): void
    {
        $query->where('status', $status->value);
    }

    /** Recherche par référence, nom ou téléphone (espaces ignorés pour le téléphone). */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $q) use ($term) {
            $q->where('reference', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%");

            // Saisie qui ressemble à un téléphone (pas de lettres) : « 06 12 34 », « +212 6 12… ».
            // Les numéros sont stockés au format 06XXXXXXXX.
            if (preg_match('/^[\d\s+().-]{4,}$/', $term)) {
                $digits = ltrim(preg_replace('/^(00)?212/', '', preg_replace('/\D+/', '', $term)), '0');

                if (strlen($digits) >= 3) {
                    $q->orWhere('customer_phone', 'like', "%{$digits}%");
                }
            }
        });
    }

    /** Total = articles + livraison (si déjà convenue). */
    protected function total(): Attribute
    {
        return Attribute::get(fn () => round((float) $this->subtotal + (float) ($this->delivery_fee ?? 0), 2));
    }

    protected function phoneDisplay(): Attribute
    {
        return Attribute::get(fn () => Phone::format($this->customer_phone));
    }

    /** Lien wa.me pour contacter le client (null si numéro non convertible). */
    protected function whatsappUrl(): Attribute
    {
        return Attribute::get(function () {
            $number = Phone::toInternational($this->customer_phone);

            return $number ? 'https://wa.me/'.$number : null;
        });
    }
}
