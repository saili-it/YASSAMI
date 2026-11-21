@props([
    'title' => null,
    'description' => config('yassami.brand').' — vêtements et accessoires de sport premium. Commandez en quelques clics, partout au Maroc.',
    'canonical' => null,
    'image' => null,
    // Message pré-rempli du bouton WhatsApp flottant (null = message générique).
    'whatsappMessage' => null,
])

@php
    $brand = config('yassami.brand');
    $fullTitle = $title ? "{$title} | {$brand}" : "{$brand} — Sportswear premium au Maroc";
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0e0e10">
    {{-- Frais de livraison pour l'affichage du panier (vide = à confirmer). Le serveur fait foi à la commande. --}}
    <meta name="yassami-delivery-fee" content="{{ \App\Support\Delivery::fee() }}">

    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:locale" content="fr_MA">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    {{-- Image de partage : photo du produit si disponible, sinon visuel de marque --}}
    <meta property="og:image" content="{{ $image ?? asset('images/brand/og-image.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">

    @include('layouts.partials.favicons')

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-svh flex-col" x-data="{ menuOpen: false }" :class="{ 'overflow-hidden': menuOpen || $store.cart.drawerOpen }">
    <a href="#contenu" class="sr-only z-[100] bg-ink px-4 py-3 text-paper focus:not-sr-only focus:fixed focus:left-2 focus:top-2">Aller au contenu</a>

    @include('layouts.partials.header')

    <main id="contenu" class="flex-1">
        {{ $slot }}
    </main>

    @include('layouts.partials.footer')
    @include('layouts.partials.mobile-menu')
    @include('layouts.partials.cart-drawer')

    <x-whatsapp-float :message="$whatsappMessage" />

    {{-- Notification d'ajout au panier --}}
    <div x-cloak x-show="$store.cart.toast" x-transition.opacity.duration.200ms role="status" aria-live="polite"
         class="fixed inset-x-4 bottom-4 z-[70] mx-auto flex max-w-sm items-center gap-3 bg-ink px-4 py-3 text-sm text-paper shadow-2xl sm:bottom-6">
        <x-icon name="check" class="size-4 text-whatsapp" />
        <span x-text="$store.cart.toast"></span>
    </div>

    @stack('scripts')
</body>
</html>
