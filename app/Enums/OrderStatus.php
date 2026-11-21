<?php

namespace App\Enums;

/** Cycle de vie d'une commande. Les valeurs (stockées en base) restent en français, sans accents. */
enum OrderStatus: string
{
    case New = 'nouvelle';
    case Confirmed = 'confirmee';
    case Shipped = 'expediee';
    case Delivered = 'livree';
    case Cancelled = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::Confirmed => 'Confirmée',
            self::Shipped => 'Expédiée',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
        };
    }

    /** Libellé au pluriel pour les filtres de la liste. */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::New => 'Nouvelles',
            self::Confirmed => 'Confirmées',
            self::Shipped => 'Expédiées',
            self::Delivered => 'Livrées',
            self::Cancelled => 'Annulées',
        };
    }

    /** Classes du badge (jetons de couleur du thème uniquement). */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'bg-accent text-white',
            self::Confirmed => 'bg-ink text-paper',
            self::Shipped => 'bg-warning/15 text-warning',
            self::Delivered => 'bg-success/15 text-success',
            self::Cancelled => 'bg-bone text-muted line-through',
        };
    }

    /** Une commande annulée a rendu son stock. */
    public function holdsStock(): bool
    {
        return $this !== self::Cancelled;
    }
}
