<?php

namespace App\Support;

/**
 * Frais de livraison : prix unique pour tout le Maroc, réglé dans /admin/parametres.
 *   null → non configuré : « À confirmer » avec le client (comportement historique)
 *   0    → livraison gratuite
 *   > 0  → prix fixe ajouté à chaque commande
 */
final class Delivery
{
    public static function fee(): ?float
    {
        $value = config('yassami.delivery_fee');

        return is_numeric($value) && (float) $value >= 0 ? round((float) $value, 2) : null;
    }

    /** Libellé affiché : « 35 DH », « Gratuite » ou « À confirmer ». */
    public static function label(float|int|string|null $fee): string
    {
        if ($fee === null || $fee === '') {
            return 'À confirmer';
        }

        return (float) $fee == 0 ? 'Gratuite' : Money::format($fee);
    }
}
