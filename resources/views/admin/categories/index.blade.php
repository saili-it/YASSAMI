<x-layouts.admin title="Catégories">
    <x-admin.page-header title="Catégories" :subtitle="$categories->count().' catégorie(s) · triées par ordre d’affichage'">
        <a href="{{ route('admin.categories.create') }}" class="btn btn-md btn-primary"><x-icon name="plus" class="size-4" /> Nouvelle catégorie</a>
    </x-admin.page-header>

    @if ($categories->isEmpty())
        <div class="flex flex-col items-center border border-dashed border-line bg-white px-6 py-20 text-center">
            <x-icon name="layers" class="size-8 text-muted" />
            <p class="display mt-4 text-3xl">Aucune catégorie</p>
            <p class="mt-2 max-w-sm text-sm text-muted">Les catégories organisent votre boutique (T-shirts, Shorts…). Créez-en une pour pouvoir ajouter des produits.</p>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-md btn-primary mt-6"><x-icon name="plus" class="size-4" /> Créer une catégorie</a>
        </div>
    @else
        <ul class="divide-y divide-line bg-white ring-1 ring-line">
            @foreach ($categories as $category)
                <li class="flex items-center gap-4 px-4 py-3 sm:px-5">
                    <span class="hidden w-8 shrink-0 text-center text-xs font-semibold tabular-nums text-muted sm:block" title="Ordre d’affichage">{{ $category->sort_order }}</span>

                    <span class="relative flex size-14 shrink-0 items-center justify-center overflow-hidden bg-bone">
                        @if ($category->image_url)
                            <img src="{{ $category->image_url }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
                        @else
                            <x-icon name="image" class="size-4 text-muted/60" />
                        @endif
                    </span>

                    <a href="{{ route('admin.categories.edit', $category) }}" class="min-w-0 flex-1">
                        <p class="flex items-center gap-2 font-medium">
                            <span class="truncate">{{ $category->name }}</span>
                            @unless ($category->is_active)
                                <span class="shrink-0 bg-bone px-1.5 py-0.5 text-[0.625rem] font-bold uppercase tracking-[0.1em] text-muted">Masquée</span>
                            @endunless
                        </p>
                        <p class="mt-0.5 truncate text-xs text-muted">/boutique/{{ $category->slug }} · {{ $category->products_count }} produit(s)</p>
                    </a>

                    <div class="flex shrink-0 items-center">
                        <a href="{{ route('admin.products.index', ['categorie' => $category->id]) }}" class="hidden h-9 items-center px-3 text-xs font-semibold text-muted hover:text-ink sm:inline-flex">Voir les produits</a>
                        <a href="{{ route('admin.categories.edit', $category) }}" class="inline-flex size-9 items-center justify-center text-muted hover:bg-bone hover:text-ink" aria-label="Modifier {{ $category->name }}" title="Modifier">
                            <x-icon name="pencil" class="size-4" />
                        </a>
                        <x-admin.confirm-delete compact :action="route('admin.categories.destroy', $category)"
                            :title="'Supprimer « '.$category->name.' » ?'"
                            :message="$category->products_count > 0
                                ? 'Cette catégorie contient '.$category->products_count.' produit(s) : la suppression sera refusée. Déplacez ou supprimez d’abord ses produits, ou masquez simplement la catégorie.'
                                : 'Cette action est définitive.'" />
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.admin>
