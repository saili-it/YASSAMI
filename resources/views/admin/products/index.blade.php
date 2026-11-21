<x-layouts.admin title="Produits">
    <x-admin.page-header title="Produits" :subtitle="$products->total().' produit(s)'">
        <a href="{{ route('admin.products.create', array_filter(['categorie' => $categoryId])) }}" class="btn btn-md btn-primary">
            <x-icon name="plus" class="size-4" /> Nouveau produit
        </a>
    </x-admin.page-header>

    {{-- Filtres --}}
    <form method="GET" class="mb-5 grid gap-2 sm:grid-cols-[1fr_auto_auto_auto]" x-data>
        <div class="relative">
            <label for="q" class="sr-only">Rechercher</label>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Nom ou référence…" class="field h-11 pl-9 text-sm">
        </div>
        <label for="categorie" class="sr-only">Catégorie</label>
        <select id="categorie" name="categorie" class="field h-11 text-sm sm:w-48" @change="$el.form.submit()">
            <option value="">Toutes les catégories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected($categoryId === $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <label for="statut" class="sr-only">Statut</label>
        <select id="statut" name="statut" class="field h-11 text-sm sm:w-40" @change="$el.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-md btn-outline h-11">Filtrer</button>
    </form>

    @if ($search || $categoryId || $status)
        <p class="mb-4 text-sm">
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1 font-semibold text-muted hover:text-ink">
                <x-icon name="x" class="size-3.5" /> Effacer les filtres
            </a>
        </p>
    @endif

    @if ($products->isEmpty())
        <div class="flex flex-col items-center border border-dashed border-line bg-white px-6 py-20 text-center">
            <x-icon name="tag" class="size-8 text-muted" />
            <p class="display mt-4 text-3xl">{{ $search || $categoryId || $status ? 'Aucun résultat' : 'Aucun produit' }}</p>
            <p class="mt-2 max-w-sm text-sm text-muted">
                {{ $search || $categoryId || $status ? 'Modifiez ou effacez les filtres.' : ($categories->isEmpty() ? 'Créez d’abord une catégorie, puis ajoutez vos produits.' : 'Ajoutez votre premier produit.') }}
            </p>
            @if ($categories->isEmpty())
                <a href="{{ route('admin.categories.create') }}" class="btn btn-md btn-primary mt-6"><x-icon name="plus" class="size-4" /> Créer une catégorie</a>
            @elseif (! ($search || $categoryId || $status))
                <a href="{{ route('admin.products.create') }}" class="btn btn-md btn-primary mt-6"><x-icon name="plus" class="size-4" /> Nouveau produit</a>
            @endif
        </div>
    @else
        <div class="bg-white ring-1 ring-line">
            {{-- En-têtes (desktop) --}}
            <div class="hidden grid-cols-[minmax(0,1fr)_8rem_7.5rem_7rem_6.5rem] items-center gap-4 border-b border-line px-5 py-3 text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-muted lg:grid">
                <span>Produit</span><span>Prix</span><span>Stock</span><span class="text-center">Actif / Vedette</span><span></span>
            </div>

            <ul class="divide-y divide-line">
                @foreach ($products as $product)
                    <li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-3 px-4 py-3 sm:px-5 lg:grid-cols-[minmax(0,1fr)_8rem_7.5rem_7rem_6.5rem]">
                        <a href="{{ route('admin.products.edit', $product) }}" class="flex min-w-0 items-center gap-4">
                            @include('admin.partials.thumb', ['product' => $product, 'size' => 'size-14'])
                            <div class="min-w-0">
                                <p class="flex items-center gap-2 font-medium">
                                    <span class="truncate">{{ $product->name }}</span>
                                    @unless ($product->is_active)
                                        <span class="shrink-0 bg-bone px-1.5 py-0.5 text-[0.625rem] font-bold uppercase tracking-[0.1em] text-muted">Inactif</span>
                                    @endunless
                                </p>
                                <p class="mt-0.5 truncate text-xs text-muted">{{ $product->sku }} · {{ $product->category->name }}</p>
                                <div class="mt-1 flex items-center gap-2 lg:hidden">
                                    <x-price :product="$product" size="sm" />
                                </div>
                            </div>
                        </a>

                        <x-price :product="$product" size="sm" class="hidden lg:flex" />

                        <div class="hidden lg:block">@include('admin.partials.stock-badge', ['product' => $product])</div>

                        {{-- Bascules rapides --}}
                        <div class="col-span-2 flex items-center gap-2 lg:col-span-1 lg:justify-center">
                            <span class="lg:hidden">@include('admin.partials.stock-badge', ['product' => $product])</span>
                            <span class="flex-1 lg:hidden"></span>
                            @foreach (['is_active' => ['check', 'Actif', 'Activer', 'Désactiver'], 'is_featured' => ['star', 'Vedette', 'Mettre en vedette', 'Retirer de la vedette']] as $field => [$icon, $short, $on, $off])
                                <form method="POST" action="{{ route('admin.products.toggle', [$product, $field]) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" @class([
                                        'inline-flex size-9 items-center justify-center border transition-colors',
                                        'border-ink bg-ink text-paper hover:bg-ink-soft' => $product->{$field},
                                        'border-line text-muted hover:border-ink hover:text-ink' => ! $product->{$field},
                                    ]) title="{{ $product->{$field} ? $off : $on }}" aria-label="{{ ($product->{$field} ? $off : $on).' : '.$product->name }}" aria-pressed="{{ $product->{$field} ? 'true' : 'false' }}">
                                        <x-icon :name="$icon" class="size-4" />
                                    </button>
                                </form>
                            @endforeach
                        </div>

                        <div class="hidden items-center justify-end lg:flex">
                            <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex size-9 items-center justify-center text-muted hover:bg-bone hover:text-ink" title="Modifier" aria-label="Modifier {{ $product->name }}">
                                <x-icon name="pencil" class="size-4" />
                            </a>
                            <x-admin.confirm-delete compact :action="route('admin.products.destroy', $product)"
                                :title="'Supprimer « '.$product->name.' » ?'" message="Le produit et toutes ses photos seront supprimés définitivement. Pour le cacher temporairement, désactivez-le plutôt." />
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($products->hasPages())
            <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Pagination">
                <span class="text-muted">Page {{ $products->currentPage() }} sur {{ $products->lastPage() }}</span>
                <div class="flex gap-2">
                    @if ($products->previousPageUrl())
                        <a href="{{ $products->previousPageUrl() }}" class="btn btn-sm btn-outline"><x-icon name="chevron-left" class="size-4" /> Précédente</a>
                    @endif
                    @if ($products->nextPageUrl())
                        <a href="{{ $products->nextPageUrl() }}" class="btn btn-sm btn-outline">Suivante <x-icon name="chevron-right" class="size-4" /></a>
                    @endif
                </div>
            </nav>
        @endif
    @endif
</x-layouts.admin>
