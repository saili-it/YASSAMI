@php
    use App\Enums\OrderStatus;
    $all = $counts->sum();
    $tabs = [['value' => null, 'label' => 'Toutes', 'count' => $all]];
    foreach (OrderStatus::cases() as $case) {
        $tabs[] = ['value' => $case, 'label' => $case->pluralLabel(), 'count' => (int) ($counts[$case->value] ?? 0)];
    }
@endphp

<x-layouts.admin title="Commandes">
    <x-admin.page-header title="Commandes" :subtitle="$all.' commande(s) au total'" />

    {{-- Filtres par statut --}}
    <nav class="scroll-rail -mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" aria-label="Filtrer par statut">
        @foreach ($tabs as $tab)
            @php $active = $status === $tab['value']; @endphp
            <a href="{{ route('admin.orders.index', array_filter(['statut' => $tab['value']?->value, 'q' => $search])) }}" @class([
                'inline-flex h-10 shrink-0 items-center gap-2 border px-3.5 text-sm font-medium transition-colors',
                'border-ink bg-ink text-paper' => $active,
                'border-line bg-white text-ink/80 hover:border-ink' => ! $active,
            ]) @if ($active) aria-current="page" @endif>
                {{ $tab['label'] }}
                <span @class([
                    'min-w-6 px-1.5 text-center text-xs font-semibold tabular-nums',
                    'bg-paper/15' => $active,
                    'bg-accent text-white' => ! $active && $tab['value'] === OrderStatus::New && $tab['count'] > 0,
                    'bg-bone text-muted' => ! $active && ! ($tab['value'] === OrderStatus::New && $tab['count'] > 0),
                ])>{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="mb-5 flex gap-2">
        @if ($status)<input type="hidden" name="statut" value="{{ $status->value }}">@endif
        <div class="relative flex-1">
            <label for="q" class="sr-only">Rechercher une commande</label>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input id="q" type="search" name="q" value="{{ $search }}" placeholder="N° de commande, nom ou téléphone…" class="field h-11 pl-9 text-sm">
        </div>
        <button type="submit" class="btn btn-md btn-outline h-11">Rechercher</button>
    </form>

    @if ($orders->isEmpty())
        <div class="flex flex-col items-center border border-dashed border-line bg-white px-6 py-20 text-center">
            <x-icon name="package" class="size-8 text-muted" />
            <p class="display mt-4 text-3xl">{{ $search || $status ? 'Aucun résultat' : 'Aucune commande' }}</p>
            <p class="mt-2 max-w-sm text-sm text-muted">
                {{ $search || $status ? 'Modifiez la recherche ou choisissez un autre statut.' : 'Les commandes passées sur la boutique apparaîtront ici.' }}
            </p>
            @if ($search || $status)
                <a href="{{ route('admin.orders.index') }}" class="btn btn-md btn-outline mt-6">Voir toutes les commandes</a>
            @endif
        </div>
    @else
        <div class="bg-white ring-1 ring-line">
            <div class="hidden grid-cols-[8rem_minmax(0,1fr)_9rem_7rem_7rem] items-center gap-4 border-b border-line px-5 py-3 text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-muted lg:grid">
                <span>Commande</span><span>Client</span><span>Date</span><span class="text-right">Total</span><span class="text-right">Statut</span>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($orders as $order)
                    <li>
                        <a href="{{ route('admin.orders.show', $order) }}"
                           class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 px-4 py-3.5 transition-colors hover:bg-paper sm:px-5 lg:grid-cols-[8rem_minmax(0,1fr)_9rem_7rem_7rem]">
                            <span class="font-semibold tabular-nums">{{ $order->reference }}</span>
                            <x-admin.order-status :status="$order->status" class="justify-self-end lg:order-last" />
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{{ $order->customer_name }}</span>
                                <span class="block truncate text-xs text-muted">{{ $order->customer_city }} · {{ $order->phone_display }}</span>
                            </span>
                            <span class="justify-self-end text-right font-semibold tabular-nums lg:hidden">{{ mad($order->total) }}</span>
                            <span class="col-span-2 text-xs text-muted lg:col-span-1 lg:text-sm">
                                {{ $order->created_at->translatedFormat('d M Y · H:i') }} · {{ $order->items_count }} article(s)
                            </span>
                            <span class="hidden text-right font-semibold tabular-nums lg:block">{{ mad($order->total) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($orders->hasPages())
            <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Pagination">
                <span class="text-muted">Page {{ $orders->currentPage() }} sur {{ $orders->lastPage() }}</span>
                <div class="flex gap-2">
                    @if ($orders->previousPageUrl())
                        <a href="{{ $orders->previousPageUrl() }}" class="btn btn-sm btn-outline"><x-icon name="chevron-left" class="size-4" /> Précédente</a>
                    @endif
                    @if ($orders->nextPageUrl())
                        <a href="{{ $orders->nextPageUrl() }}" class="btn btn-sm btn-outline">Suivante <x-icon name="chevron-right" class="size-4" /></a>
                    @endif
                </div>
            </nav>
        @endif
    @endif
</x-layouts.admin>
