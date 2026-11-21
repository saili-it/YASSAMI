{{-- Bandeau d'annonce : information factuelle sur le mode de commande --}}
<div class="bg-ink text-paper">
    <p class="container-brand flex h-9 items-center justify-center gap-2 text-[0.6875rem] font-medium uppercase tracking-[0.14em] sm:tracking-[0.16em]">
        <x-icon name="bag" class="size-3.5" />
        <span class="sm:hidden">Commande simple, sans compte</span>
        <span class="hidden sm:inline">Commandez en quelques clics, sans créer de compte</span>
    </p>
</div>

<header class="sticky top-0 z-40 border-b border-line bg-paper/95 backdrop-blur supports-[backdrop-filter]:bg-paper/85"
        x-data="{ searchOpen: false }" @keydown.escape.window="searchOpen = false">
    <div class="container-brand flex h-16 items-center gap-4 lg:h-[4.5rem]">
        {{-- Mobile : menu --}}
        <button type="button" class="-ml-2 inline-flex size-10 items-center justify-center lg:hidden"
                @click="menuOpen = true" aria-label="Ouvrir le menu" :aria-expanded="menuOpen">
            <x-icon name="menu" class="size-6" />
        </button>

        <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ config('yassami.brand') }} — Accueil">
            <x-logo class="h-11 lg:h-[3.25rem]" />
        </a>

        {{-- Desktop : navigation --}}
        <nav class="ml-8 hidden h-full items-stretch gap-7 lg:flex" aria-label="Navigation principale">
            @php
                $links = [
                    ['label' => 'Boutique', 'href' => route('shop.index'), 'active' => request()->routeIs('shop.index') && ! request('promo')],
                ];
                foreach ($navCategories->take(4) as $cat) {
                    $links[] = ['label' => $cat->name, 'href' => route('shop.category', $cat), 'active' => request()->is('boutique/'.$cat->slug)];
                }
            @endphp
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" @class([
                    'relative flex items-center text-[0.8125rem] font-semibold uppercase tracking-[0.08em] transition-colors hover:text-ink',
                    'text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-ink' => $link['active'],
                    'text-ink/70' => ! $link['active'],
                ])>{{ $link['label'] }}</a>
            @endforeach
            <a href="{{ route('shop.index', ['promo' => 1]) }}"
               class="flex items-center text-[0.8125rem] font-semibold uppercase tracking-[0.08em] text-accent hover:text-accent-strong">Promotions</a>
        </nav>

        <div class="ml-auto flex items-center gap-1">
            <button type="button" class="inline-flex size-10 items-center justify-center" @click="searchOpen = !searchOpen; $nextTick(() => searchOpen && $refs.search.focus())"
                    aria-label="Rechercher" :aria-expanded="searchOpen">
                <x-icon name="search" class="size-[1.375rem]" />
            </button>

            <button type="button" class="relative -mr-2 inline-flex size-10 items-center justify-center" @click="$store.cart.drawerOpen = true"
                    aria-label="Ouvrir le panier">
                <x-icon name="bag" class="size-[1.375rem]" />
                <span x-cloak x-show="$store.cart.count > 0" x-text="$store.cart.count"
                      class="absolute right-0.5 top-0.5 flex h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-accent px-1 text-[0.625rem] font-bold text-white"></span>
                <span class="sr-only" x-text="`${$store.cart.count} article(s)`"></span>
            </button>
        </div>
    </div>

    {{-- Barre de recherche --}}
    <div x-cloak x-show="searchOpen" x-transition.origin.top class="border-t border-line bg-paper" @click.outside="searchOpen = false">
        <form action="{{ route('shop.index') }}" method="get" role="search" class="container-brand flex items-center gap-3 py-3">
            <x-icon name="search" class="size-5 text-muted" />
            <label for="header-search" class="sr-only">Rechercher un produit</label>
            <input id="header-search" x-ref="search" type="search" name="q" value="{{ request('q') }}" maxlength="80"
                   placeholder="Rechercher un produit, une référence…"
                   class="h-11 flex-1 bg-transparent text-base placeholder:text-muted focus:outline-none">
            <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
        </form>
    </div>
</header>
