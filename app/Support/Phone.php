<?php

namespace App\Support;

/** Numéros de téléphone marocains. */
final class Phone
{
    /**
     * Format international sans « + » (celui de wa.me) :
     * « 06 12 34 56 78 », « +212 6 12 34 56 78 » ou « 00212612345678 » → « 212612345678 ».
     * Renvoie null si la saisie est vide.
     */
    public static function toInternational(mixed $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '212'.substr($digits, 1);
        }

        return $digits;
    }

    /** Affichage lisible : 0612345678 / +212612345678 → « 06 12 34 56 78 ». */
    public static function format(?string $raw): string
    {
        $international = self::toInternational($raw);

        if ($international && preg_match('/^212(\d{9})$/', $international, $m)) {
            return trim(chunk_split('0'.$m[1], 2, ' '));
        }

        return (string) $raw;
    }
}
