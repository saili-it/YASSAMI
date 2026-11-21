<?php

use App\Support\Money;

if (! function_exists('mad')) {
    /** Formate un montant en dirhams : mad(1299) → « 1 299 DH ». */
    function mad(float|int|string|null $amount): string
    {
        return Money::format($amount);
    }
}
