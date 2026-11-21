@props(['title' => 'Administration'])

@php
    $nav = [
        ['label' => 'Tableau de bord', 'icon' => 'grid', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Commandes', 'icon' => 'package', 'route' => 'admin.orders.index', 'active' => 'admin.orders.*', 'badge' => $newOrdersCount ?? 0],
        ['label' => 'Produits', 'icon' => 'tag', 'route' => 'admin.products.index', 'active' => 'admin.products.*'],
        ['label' => 'Catégories', 'icon' => 'layers', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*'],
        ['label' => 'Paramètres', 'icon' => 'settings', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · Admin {{ config('yassami.brand') }}</title>
    @include('layouts.partials.favicons')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-svh bg-paper" x-data="{ navOpen: false }" :class="{ 'overflow-hidden': navOpen }">
    {{-- ------------------------------------------------ Barre latérale --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-ink text-paper transition-transform duration-300 ease-(--ease-brand) lg:translate-x-0"
           :class="{ 'translate-x-0': navOpen }" aria-label="Navigation de l’administration">
        <div class="flex h-16 items-center justify-between px-5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <x-logo tone="light" class="h-10 self-center" />
                <span class="mt-0.5 bg-paper/10 px-1.5 py-0.5 text-[0.5625rem] font-bold uppercase tracking-[0.18em] text-paper/70">Admin</span>
            </a>
            <button type="button" class="-mr-2 inline-flex size-10 items-center justify-center lg:hidden" @click="navOpen = false" aria-label="Fermer le menu">
                <x-icon name="x" class="size-5" />
            </button>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-4">
            @foreach ($nav as $item)
                @php $active = request()->routeIs($item['active']); @endphp
                <a href="{{ route($item['route']) }}" @class([
                    'flex h-11 items-center gap-3 px-3 text-sm font-medium transition-colors',
                    'bg-paper text-ink' => $active,
                    'text-paper/70 hover:bg-paper/10 hover:text-paper' => ! $active,
                ]) @if ($active) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" class="size-[1.125rem]" /> {{ $item['label'] }}
                    @if (! empty($item['badge']))
                        <span class="ml-auto min-w-6 bg-accent px-1.5 text-center text-xs font-bold tabular-nums text-white"
                              title="{{ $item['badge'] }} nouvelle(s) commande(s)">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach

            <div class="my-4 border-t border-paper/10"></div>

            <a href="{{ route('home') }}" target="_blank" rel="noopener"
               class="flex h-11 items-center gap-3 px-3 text-sm font-medium text-paper/70 transition-colors hover:bg-paper/10 hover:text-paper">
                <x-icon name="external" class="size-[1.125rem]" /> Voir la boutique
            </a>
        </nav>

        <div class="border-t border-paper/10 p-3">
            <div class="flex items-center gap-3 px-3 py-2">
                <span class="flex size-9 shrink-0 items-center justify-center bg-accent font-display text-lg font-extrabold uppercase italic">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-paper/50">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="flex h-10 w-full items-center gap-3 px-3 text-sm text-paper/70 transition-colors hover:bg-paper/10 hover:text-paper">
                    <x-icon name="log-out" class="size-[1.125rem]" /> Se déconnecter
                </button>
            </form>
        </div>
    </aside>

    <div x-cloak x-show="navOpen" x-transition.opacity class="fixed inset-0 z-40 bg-ink/50 lg:hidden" @click="navOpen = false"></div>

    {{-- ------------------------------------------------------- Contenu --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-line bg-paper/95 px-4 backdrop-blur lg:hidden">
            <button type="button" class="-ml-2 inline-flex size-10 items-center justify-center" @click="navOpen = true" aria-label="Ouvrir le menu">
                <x-icon name="menu" class="size-6" />
            </button>
            <x-logo class="h-9" />
            @if (($newOrdersCount ?? 0) > 0)
                <a href="{{ route('admin.orders.index', ['statut' => 'nouvelle']) }}" class="ml-auto inline-flex h-8 items-center gap-1.5 bg-accent px-2.5 text-xs font-bold text-white">
                    <x-icon name="package" class="size-3.5" /> {{ $newOrdersCount }} nouvelle{{ $newOrdersCount > 1 ? 's' : '' }}
                </a>
            @endif
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10 lg:px-10">
            <noscript>
                <p class="mb-6 border border-accent/30 bg-accent/10 px-4 py-3 text-sm font-medium text-accent-strong">
                    JavaScript est désactivé : l’administration en a besoin (photos, tailles, couleurs). Activez-le avant d’enregistrer un produit.
                </p>
            </noscript>
            <x-admin.flash />
            {{ $slot }}
        </main>
    </div>
</body>
</html>
