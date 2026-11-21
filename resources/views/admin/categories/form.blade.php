@php $editing = $category->exists; @endphp

<x-layouts.admin :title="$editing ? 'Modifier '.$category->name : 'Nouvelle catégorie'">
    <x-admin.page-header :title="$editing ? $category->name : 'Nouvelle catégorie'" :back="route('admin.categories.index')"
                         :subtitle="$editing ? 'Modifier la catégorie' : 'Elle apparaîtra dans le menu et sur la page d’accueil.'">
        @if ($editing && $category->is_active)
            <a href="{{ route('shop.category', $category) }}" target="_blank" rel="noopener" class="btn btn-md btn-outline">
                <x-icon name="external" class="size-4" /> Voir sur la boutique
            </a>
        @endif
    </x-admin.page-header>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          class="grid gap-6 lg:grid-cols-3" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <section class="space-y-5 bg-white p-5 ring-1 ring-line sm:p-6">
                <div>
                    <label for="name" class="field-label">Nom <span class="text-accent">*</span></label>
                    <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required maxlength="120"
                           placeholder="Ex. T-shirts & Tops" @class(['field', 'field-invalid' => $errors->has('name')])>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="slug" class="field-label">Adresse de la page</label>
                    <div class="flex items-stretch">
                        <span class="flex items-center border border-r-0 border-line bg-bone px-3 text-sm text-muted">/boutique/</span>
                        <input id="slug" name="slug" type="text" value="{{ old('slug', $category->slug) }}" maxlength="140"
                               placeholder="générée automatiquement" @class(['field rounded-l-none', 'field-invalid' => $errors->has('slug')])>
                    </div>
                    <p class="mt-1.5 text-xs text-muted">Laissez vide pour la générer à partir du nom.</p>
                    @error('slug')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="description" class="field-label">Description</label>
                    <textarea id="description" name="description" rows="3" maxlength="1000" placeholder="Une phrase courte affichée en haut de la page catégorie."
                              @class(['field', 'field-invalid' => $errors->has('description')])>{{ old('description', $category->description) }}</textarea>
                    @error('description')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- Image --}}
            <section class="bg-white p-5 ring-1 ring-line sm:p-6" x-data="{ preview: null, remove: false }">
                <h2 class="field-label">Image</h2>
                <p class="text-xs text-muted">Utilisée sur la page d’accueil. Format paysage conseillé · JPG, PNG ou WebP · 5 Mo max.</p>

                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="relative flex aspect-[16/10] w-full shrink-0 items-center justify-center overflow-hidden bg-bone sm:w-56">
                        <template x-if="preview"><img :src="preview" alt="" class="absolute inset-0 size-full object-cover"></template>
                        @if ($category->image_url)
                            <img x-show="!preview && !remove" src="{{ $category->image_url }}" alt="" class="absolute inset-0 size-full object-cover">
                        @endif
                        <x-icon name="image" class="size-6 text-muted/50" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="btn btn-md btn-outline cursor-pointer">
                            <x-icon name="upload" class="size-4" /> Choisir une image
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="sr-only"
                                   @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : null; remove = false">
                        </label>
                        @if ($category->image)
                            <label class="flex items-center gap-2 text-sm text-muted">
                                <input type="checkbox" name="remove_image" value="1" x-model="remove" class="size-4 accent-ink"> Retirer l’image actuelle
                            </label>
                        @endif
                    </div>
                </div>
                @error('image')<p class="field-error">{{ $message }}</p>@enderror
            </section>
        </div>

        <aside class="space-y-6">
            <section class="space-y-5 bg-white p-5 ring-1 ring-line sm:p-6">
                <x-admin.toggle name="is_active" label="Visible sur la boutique" :checked="old('is_active', $category->is_active)"
                                hint="Masquée, la catégorie et tous ses produits disparaissent de la boutique." />
                <div class="border-t border-line pt-5">
                    <label for="sort_order" class="field-label">Ordre d’affichage</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" max="9999" inputmode="numeric"
                           value="{{ old('sort_order', $category->sort_order) }}" @class(['field', 'field-invalid' => $errors->has('sort_order')])>
                    <p class="mt-1.5 text-xs text-muted">Les plus petits nombres apparaissent en premier.</p>
                    @error('sort_order')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </section>

            <div class="flex flex-col gap-3">
                <button type="submit" class="btn btn-lg btn-primary w-full">
                    <x-icon name="check" class="size-4" /> {{ $editing ? 'Enregistrer' : 'Créer la catégorie' }}
                </button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-md btn-ghost w-full">Annuler</a>
            </div>
        </aside>
    </form>

    @if ($editing)
        <div class="mt-10 flex flex-col gap-3 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-muted">Supprimer cette catégorie (uniquement si elle ne contient aucun produit).</p>
            <x-admin.confirm-delete :action="route('admin.categories.destroy', $category)"
                                    :title="'Supprimer « '.$category->name.' » ?'" message="Cette action est définitive." />
        </div>
    @endif
</x-layouts.admin>
