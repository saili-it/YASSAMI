<?php

namespace App\Support;

final class Money
{
    /**
     * Formate un montant en MAD selon l'usage français : « 1 299 DH », « 89,50 DH ».
     * Doit rester identique à formatMAD() dans resources/js/cart.js.
     */
    public static function format(float|int|string|null $amount): string
    {
        $value = (float) $amount;
        $decimals = fmod($value, 1.0) === 0.0 ? 0 : 2;

        return number_format($value, $decimals, ',', "\u{202F}").' '.config('yassami.currency');
    }
}
