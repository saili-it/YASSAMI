<?php

/*
|--------------------------------------------------------------------------
| YASSAMI — paramètres métier
|--------------------------------------------------------------------------
| Source UNIQUE des informations commerciales. Ne jamais lire ces valeurs
| via env() ailleurs dans le code : toujours config('yassami.xxx').
| Aucune valeur n'est inventée : un champ vide est simplement masqué.
*/

return [

    'brand' => 'YASSAMI',

    'tagline' => 'Sportswear premium pour le Maroc',

    'currency' => 'DH',

    // Format international sans « + » ni espaces, ex. 2126XXXXXXXX.
    'whatsapp_number' => preg_replace('/\D+/', '', (string) env('YASSAMI_WHATSAPP_NUMBER', '')),

    'contact' => [
        'phone' => env('YASSAMI_CONTACT_PHONE'),
        'email' => env('YASSAMI_CONTACT_EMAIL'),
        'address' => env('YASSAMI_CONTACT_ADDRESS'),
    ],

    // Frais de livraison, prix unique pour tout le Maroc (DH). Vide = « à confirmer », 0 = gratuite.
    // Valeur initiale seulement : se règle dans /admin/parametres (App\Support\Delivery).
    'delivery_fee' => env('YASSAMI_DELIVERY_FEE'),

    'social' => array_filter([
        'instagram' => env('YASSAMI_INSTAGRAM_URL'),
        'facebook' => env('YASSAMI_FACEBOOK_URL'),
        'tiktok' => env('YASSAMI_TIKTOK_URL'),
    ]),

    // Quantité maximale par ligne de commande.
    'max_quantity_per_line' => 10,

    // Seuil d'affichage « Plus que X en stock ».
    'low_stock_threshold' => 5,

    // Tailles proposées par défaut dans l'administration.
    'default_sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],

];
