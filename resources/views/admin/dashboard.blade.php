<x-layouts.admin title="Tableau de bord">
    <x-admin.page-header title="Tableau de bord" :subtitle="'Bonjour '.auth()->user()->name.' — voici l’état de votre catalogue.'">
        <a href="{{ route('admin.categories.create') }}" class="btn btn-md btn-outline"><x-icon name="plus" class="size-4" /> Catégorie</a>
        <a href="{{ route('admin.products.create') }}" class="btn btn-md btn-primary"><x-icon name="plus" class="size-4" /> Nouveau produit</a>
    </x-admin.page-header>

    @if ($newOrders > 0)
        <a href="{{ route('admin.orders.index', ['statut' => 'nouvelle']) }}"
           class="mb-6 flex items-center justify-between gap-3 bg-accent px-4 py-3 text-white transition-colors hover:bg-accent-strong sm:px-5">
            <span class="flex items-center gap-2.5 text-sm font-semibold">
                <x-icon name="package" class="size-[1.125rem] shrink-0" />
                {{ $newOrders }} nouvelle{{ $newOrders > 1 ? 's' : '' }} commande{{ $newOrders > 1 ? 's' : '' }} à traiter
            </span>
            <span class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.1em]">Voir <x-icon name="arrow-right" class="size-4" /></span>
        </a>
    @endif

    {{-- Indicateurs --}}
    @php
        $cards = [
            ['label' => 'Produits', 'value' => $stats['products'], 'icon' => 'tag', 'href' => route('admin.products.index'), 'tone' => 'ink'],
            ['label' => 'Catégories', 'value' => $stats['categories'], 'icon' => 'layers', 'href' => route('admin.categories.index'), 'tone' => 'ink'],
            ['label' => 'Produits actifs', 'value' => $stats['active'], 'icon' => 'check', 'href' => route('admin.products.index', ['statut' => 'actifs']), 'tone' => 'success'],
            ['label' => 'En rupture de stock', 'value' => $stats['outOfStock'], 'icon' => 'alert', 'href' => route('admin.products.index', ['statut' => 'rupture']), 'tone' => $stats['outOfStock'] > 0 ? 'accent' : 'ink'],
        ];
    @endphp
    <ul class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        @foreach ($cards as $card)
            <li>
                <a href="{{ $card['href'] }}" class="group flex h-full flex-col justify-between gap-6 bg-white p-4 ring-1 ring-line transition hover:ring-ink sm:p-6">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-[0.1em] text-muted">{{ $card['label'] }}</span>
                        <x-icon :name="$card['icon']" @class([
                            'size-[1.125rem]',
                            'text-success' => $card['tone'] === 'success',
                            'text-accent' => $card['tone'] === 'accent',
                            'text-muted' => $card['tone'] === 'ink',
                        ]) />
                    </div>
                    <p @class(['display text-5xl tabular-nums sm:text-6xl', 'text-accent' => $card['tone'] === 'accent'])>{{ $card['value'] }}</p>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Dernières commandes --}}
    <section class="mt-8 bg-white ring-1 ring-line">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Dernières commandes</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-muted hover:text-ink">Toutes les commandes</a>
        </div>
        @if ($latestOrders->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-muted">Aucune commande pour l’instant. Elles apparaîtront ici dès qu’un client validera son panier.</p>
        @else
            <ul class="divide-y divide-line">
                @foreach ($latestOrders as $order)
                    <li>
                        <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center gap-4 px-5 py-3 transition-colors hover:bg-paper">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium"><span class="tabular-nums">{{ $order->reference }}</span> · {{ $order->customer_name }}</p>
                                <p class="truncate text-xs text-muted">{{ $order->created_at->diffForHumans() }} · {{ $order->customer_city }}</p>
                            </div>
                            <span class="hidden text-sm font-semibold tabular-nums sm:block">{{ mad($order->total) }}</span>
                            <x-admin.order-status :status="$order->status" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Ruptures --}}
        <section class="bg-white ring-1 ring-line">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Ruptures de stock</h2>
                <a href="{{ route('admin.products.index', ['statut' => 'rupture']) }}" class="text-xs font-semibold text-muted hover:text-ink">Tout voir</a>
            </div>
            @include('admin.partials.product-mini-list', ['items' => $outOfStock, 'empty' => 'Aucun produit en rupture. Bravo !'])
        </section>

        {{-- Stock faible --}}
        <section class="bg-white ring-1 ring-line">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Stock faible <span class="font-normal normal-case tracking-normal text-muted">(≤ {{ config('yassami.low_stock_threshold') }})</span></h2>
            </div>
            @include('admin.partials.product-mini-list', ['items' => $lowStock, 'empty' => 'Aucun produit en stock faible.'])
        </section>
    </div>

    {{-- Derniers produits --}}
    <section class="mt-6 bg-white ring-1 ring-line">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Derniers produits ajoutés</h2>
            <a href="{{ route('admin.products.index') }}" class="text-xs font-semibold text-muted hover:text-ink">Tous les produits</a>
        </div>
        @include('admin.partials.product-mini-list', ['items' => $latest, 'empty' => 'Aucun produit pour l’instant. Commencez par créer une catégorie, puis un produit.'])
    </section>
</x-layouts.admin>
