@php
    $heading = $category?->name ?? ($onlyPromo ? 'Promotions' : 'Boutique');
    $intro = $category?->description ?? ($onlyPromo ? 'Nos pièces à prix réduit, dans la limite des stocks disponibles.' : 'Toute la collection : vêtements et accessoires de sport.');
    // Conserve la recherche et le tri lors d'un changement de catégorie.
    $keep = array_filter(['q' => $search ?: null, 'tri' => $sort !== 'nouveautes' ? $sort : null, 'promo' => $onlyPromo ? 1 : null]);
@endphp

<x-layouts.app :title="$search ? 'Recherche « '.$search.' »' : $heading" :description="$intro"
               :canonical="$category ? route('shop.category', $category) : route('shop.index')">

    <section class="container-brand pb-6 pt-8 sm:pt-12">
        <nav aria-label="Fil d’Ariane" class="mb-4 flex items-center gap-1.5 text-xs text-muted">
            <a href="{{ route('home') }}" class="hover:text-ink">Accueil</a>
            <x-icon name="chevron-right" class="size-3" />
            @if ($category)
                <a href="{{ route('shop.index') }}" class="hover:text-ink">Boutique</a>
                <x-icon name="chevron-right" class="size-3" />
                <span class="text-ink">{{ $category->name }}</span>
            @else
                <span class="text-ink">Boutique</span>
            @endif
        </nav>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="display text-[clamp(2.75rem,9vw,5.5rem)]">{{ $heading }}</h1>
                <p class="mt-2 max-w-xl text-sm text-muted sm:text-base">{{ $intro }}</p>
            </div>
            <p class="text-sm text-muted tabular-nums">{{ $products->total() }} {{ Str::plural('produit', $products->total()) }}</p>
        </div>
    </section>

    {{-- Barre d'outils : catégories / recherche / tri --}}
    <div class="sticky top-16 z-30 border-y border-line bg-paper/95 backdrop-blur lg:top-[4.5rem]">
        <div class="container-brand flex flex-col gap-3 py-3 lg:flex-row lg:items-center lg:justify-between">
            <nav aria-label="Catégories" class="scroll-rail -mx-4 gap-2 px-4 pb-0 sm:mx-0 sm:px-0">
                <a href="{{ route('shop.index', $keep) }}" @class([
                    'inline-flex h-9 shrink-0 items-center border px-4 text-xs font-semibold uppercase tracking-[0.08em] transition-colors',
                    'border-ink bg-ink text-paper' => ! $category,
                    'border-line hover:border-ink' => $category,
                ])>Tout</a>
                @foreach ($categories as $cat)
                    <a href="{{ route('shop.category', [$cat] + $keep) }}" @class([
                        'inline-flex h-9 shrink-0 items-center border px-4 text-xs font-semibold uppercase tracking-[0.08em] transition-colors',
                        'border-ink bg-ink text-paper' => $category?->is($cat),
                        'border-line hover:border-ink' => ! $category?->is($cat),
                    ])>{{ $cat->name }}</a>
                @endforeach
            </nav>

            <form method="get" class="flex gap-2" x-data>
                @if ($onlyPromo) <input type="hidden" name="promo" value="1"> @endif
                <div class="relative flex-1 lg:w-64 lg:flex-none">
                    <label for="shop-search" class="sr-only">Rechercher</label>
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                    <input id="shop-search" type="search" name="q" value="{{ $search }}" maxlength="80" placeholder="Rechercher…" class="field h-10 pl-9 text-sm">
                </div>
                <label for="shop-sort" class="sr-only">Trier par</label>
                <select id="shop-sort" name="tri" class="field h-10 w-auto pr-8 text-sm" @change="$el.form.submit()">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <noscript><button class="btn btn-primary btn-sm h-10">OK</button></noscript>
            </form>
        </div>
    </div>

    <section class="container-brand pt-8">
        @if ($search || $onlyPromo)
            <div class="mb-6 flex flex-wrap items-center gap-2 text-sm">
                @if ($search)
                    <span class="text-muted">Résultats pour</span>
                    <a href="{{ request()->fullUrlWithoutQuery(['q', 'page']) }}" class="inline-flex items-center gap-1.5 bg-bone px-3 py-1 font-medium hover:bg-line">
                        « {{ $search }} » <x-icon name="x" class="size-3.5" /><span class="sr-only">Effacer la recherche</span>
                    </a>
                @endif
                @if ($onlyPromo)
                    <a href="{{ request()->fullUrlWithoutQuery(['promo', 'page']) }}" class="inline-flex items-center gap-1.5 bg-accent/10 px-3 py-1 font-medium text-accent hover:bg-accent/15">
                        Promotions <x-icon name="x" class="size-3.5" /><span class="sr-only">Retirer le filtre</span>
                    </a>
                @endif
            </div>
        @endif

        @if ($products->isEmpty())
            <div class="flex flex-col items-center border border-dashed border-line px-6 py-20 text-center">
                <x-icon name="search" class="size-8 text-muted" />
                <p class="display mt-4 text-3xl">Aucun produit trouvé</p>
                <p class="mt-2 max-w-sm text-sm text-muted">Essayez un autre mot-clé ou parcourez toutes nos catégories.</p>
                <a href="{{ route('shop.index') }}" class="btn btn-primary btn-md mt-6">Voir tous les produits</a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-x-3 gap-y-8 sm:grid-cols-3 sm:gap-x-5 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" :eager="$loop->index < 4" />
                @endforeach
            </div>

            @if ($products->hasPages())
                <nav class="mt-14 flex items-center justify-center gap-1" aria-label="Pagination">
                    @if ($products->onFirstPage())
                        <span class="flex size-10 items-center justify-center text-muted/40"><x-icon name="chevron-left" class="size-4" /></span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" rel="prev" class="flex size-10 items-center justify-center hover:bg-bone" aria-label="Page précédente"><x-icon name="chevron-left" class="size-4" /></a>
                    @endif

                    @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                        <a href="{{ $url }}" @class([
                            'flex size-10 items-center justify-center text-sm font-semibold tabular-nums',
                            'bg-ink text-paper' => $page === $products->currentPage(),
                            'hover:bg-bone' => $page !== $products->currentPage(),
                        ]) @if ($page === $products->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                    @endforeach

                    @if ($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" rel="next" class="flex size-10 items-center justify-center hover:bg-bone" aria-label="Page suivante"><x-icon name="chevron-right" class="size-4" /></a>
                    @else
                        <span class="flex size-10 items-center justify-center text-muted/40"><x-icon name="chevron-right" class="size-4" /></span>
                    @endif
                </nav>
            @endif
        @endif
    </section>
</x-layouts.app>
