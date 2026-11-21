@php
    $editing = $product->exists;
    $formData = [
        'sizes' => array_values((array) old('sizes', $product->sizes ?? [])),
        'colors' => array_values((array) old('colors', $product->color_options)),
        'images' => $product->images->map(fn ($i) => ['id' => $i->id, 'url' => $i->url])->values()->all(),
        'maxImages' => \App\Http\Requests\Admin\ProductRequest::MAX_IMAGES,
    ];
    $presets = [
        ['name' => 'Noir', 'hex' => '#111111'], ['name' => 'Blanc', 'hex' => '#F4F4F2'], ['name' => 'Gris', 'hex' => '#9A9A9A'],
        ['name' => 'Marine', 'hex' => '#1C2541'], ['name' => 'Rouge', 'hex' => '#D7261E'], ['name' => 'Sable', 'hex' => '#CDBA9A'],
        ['name' => 'Olive', 'hex' => '#5B6142'], ['name' => 'Bleu', 'hex' => '#2C5DA8'], ['name' => 'Rose', 'hex' => '#E8A0B4'],
    ];
    $sizeOptions = array_values(array_unique([...$defaultSizes, 'Unique']));
@endphp

<x-layouts.admin :title="$editing ? 'Modifier '.$product->name : 'Nouveau produit'">
    <x-admin.page-header :title="$editing ? $product->name : 'Nouveau produit'" :back="route('admin.products.index')"
                         :subtitle="$editing ? 'Réf. '.$product->sku.' · modifié '.$product->updated_at->diffForHumans() : 'Les champs marqués * sont obligatoires.'">
        @if ($editing && $product->is_active && $product->category?->is_active)
            <a href="{{ route('products.show', $product) }}" target="_blank" rel="noopener" class="btn btn-md btn-outline">
                <x-icon name="external" class="size-4" /> Voir sur la boutique
            </a>
        @endif
    </x-admin.page-header>

    @if ($categories->isEmpty())
        <div class="mb-6 flex items-start gap-3 border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning">
            <x-icon name="alert" class="mt-0.5 size-4" />
            <p>Aucune catégorie n’existe encore. <a href="{{ route('admin.categories.create') }}" class="font-semibold underline">Créez-en une</a> avant d’ajouter un produit.</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 flex items-start gap-3 border border-accent/30 bg-accent/10 px-4 py-3 text-sm text-accent-strong" role="alert">
            <x-icon name="alert" class="mt-0.5 size-4" />
            <p>Le produit n’a pas été enregistré : corrigez les champs signalés ci-dessous.@if (count($errors->get('images.*')) || $errors->has('images')) Les photos sélectionnées doivent être choisies à nouveau.@endif</p>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" x-data="adminProductForm(@js($formData))"
          action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
          class="grid gap-6 lg:grid-cols-3" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            {{-- ------------------------------------------- Informations --}}
            <section class="space-y-5 bg-white p-5 ring-1 ring-line sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Informations</h2>

                <div>
                    <label for="name" class="field-label">Nom du produit <span class="text-accent">*</span></label>
                    <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required maxlength="180"
                           placeholder="Ex. Legging Sculpt Taille Haute" @class(['field', 'field-invalid' => $errors->has('name')])>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="sku" class="field-label">Référence (SKU) <span class="text-accent">*</span></label>
                        <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}" required maxlength="64"
                               placeholder="Ex. LEG-SCU-001" class="field uppercase {{ $errors->has('sku') ? 'field-invalid' : '' }}">
                        @error('sku')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="slug" class="field-label">Adresse de la page</label>
                        <input id="slug" name="slug" type="text" value="{{ old('slug', $product->slug) }}" maxlength="200"
                               placeholder="générée depuis le nom" @class(['field', 'field-invalid' => $errors->has('slug')])>
                        @error('slug')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="field-label">Description</label>
                    <textarea id="description" name="description" rows="6" maxlength="5000"
                              placeholder="Matière, coupe, usage, conseils d’entretien…"
                              @class(['field', 'field-invalid' => $errors->has('description')])>{{ old('description', $product->description) }}</textarea>
                    @error('description')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- ------------------------------------------------- Photos --}}
            <section class="bg-white p-5 ring-1 ring-line sm:p-6">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Photos</h2>
                    <span class="text-xs tabular-nums text-muted" x-text="`${totalImages} / ${maxImages}`"></span>
                </div>
                <p class="mt-1 text-xs text-muted">La première photo est l’image principale. Format portrait 4:5 conseillé · JPG, PNG ou WebP · 8 Mo max. Les photos sont automatiquement optimisées.</p>

                <ul class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                    {{-- Photos existantes --}}
                    <template x-for="(img, index) in images" :key="img.id">
                        <li class="group relative" x-show="!img.removed">
                            <div class="relative aspect-[4/5] overflow-hidden bg-bone">
                                <img :src="img.url" alt="" class="absolute inset-0 size-full object-cover">
                                <span x-show="keptImages[0] && keptImages[0].id === img.id"
                                      class="absolute left-1.5 top-1.5 bg-ink px-1.5 py-0.5 text-[0.5625rem] font-bold uppercase tracking-[0.1em] text-paper">Principale</span>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between">
                                <div class="flex">
                                    <button type="button" class="inline-flex size-8 items-center justify-center text-muted hover:text-ink disabled:opacity-25" :disabled="index === 0"
                                            @click="move(index, -1)" aria-label="Déplacer vers la gauche"><x-icon name="chevron-left" class="size-4" /></button>
                                    <button type="button" class="inline-flex size-8 items-center justify-center text-muted hover:text-ink disabled:opacity-25" :disabled="index === images.length - 1"
                                            @click="move(index, 1)" aria-label="Déplacer vers la droite"><x-icon name="chevron-right" class="size-4" /></button>
                                </div>
                                <button type="button" class="inline-flex size-8 items-center justify-center text-muted hover:text-accent" @click="img.removed = true" aria-label="Supprimer la photo">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </div>
                            <input type="hidden" name="image_order[]" :value="img.id" :disabled="img.removed">
                        </li>
                    </template>

                    {{-- Nouvelles photos (pas encore envoyées) --}}
                    <template x-for="(upload, index) in uploads" :key="upload.url">
                        <li class="relative">
                            <div class="relative aspect-[4/5] overflow-hidden bg-bone ring-2 ring-whatsapp/60">
                                <img :src="upload.url" alt="" class="absolute inset-0 size-full object-cover">
                                <span class="absolute left-1.5 top-1.5 bg-whatsapp px-1.5 py-0.5 text-[0.5625rem] font-bold uppercase tracking-[0.1em] text-white">Nouvelle</span>
                            </div>
                            <div class="mt-1.5 flex justify-end">
                                <button type="button" class="inline-flex size-8 items-center justify-center text-muted hover:text-accent" @click="removeUpload(index)" aria-label="Retirer la photo">
                                    <x-icon name="x" class="size-4" />
                                </button>
                            </div>
                        </li>
                    </template>

                    {{-- Zone d'ajout --}}
                    <li x-show="totalImages < maxImages">
                        <label class="flex aspect-[4/5] cursor-pointer flex-col items-center justify-center gap-2 border-2 border-dashed p-2 text-center text-xs font-semibold text-muted transition-colors"
                               :class="dragging ? 'border-ink bg-bone text-ink' : 'border-line hover:border-ink hover:text-ink'"
                               @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                               @drop.prevent="dragging = false; addFiles([...$event.dataTransfer.files])">
                            <x-icon name="upload" class="size-6" />
                            Ajouter des photos
                            <input type="file" name="images[]" x-ref="files" multiple accept="image/jpeg,image/png,image/webp" class="sr-only"
                                   @change="addFiles([...$event.target.files])">
                        </label>
                    </li>
                </ul>

                <template x-for="img in images.filter(i => i.removed)" :key="'rm' + img.id">
                    <input type="hidden" name="remove_images[]" :value="img.id">
                </template>

                <p x-show="images.some(i => i.removed)" x-cloak class="mt-3 flex items-center gap-2 text-xs text-muted">
                    <x-icon name="alert" class="size-3.5" />
                    <span x-text="`${images.filter(i => i.removed).length} photo(s) seront supprimées à l’enregistrement.`"></span>
                    <button type="button" class="font-semibold text-ink underline" @click="images.forEach(i => i.removed = false)">Annuler</button>
                </p>
                <p x-show="imageError" x-text="imageError" x-cloak class="field-error"></p>
                @error('images')<p class="field-error">{{ $message }}</p>@enderror
                @foreach ($errors->get('images.*') as $messages)
                    <p class="field-error">{{ $messages[0] }}</p>
                @endforeach
            </section>

            {{-- ---------------------------------------------- Variantes --}}
            <section class="space-y-6 bg-white p-5 ring-1 ring-line sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Tailles & couleurs</h2>

                <div>
                    <p class="field-label">Tailles disponibles</p>
                    <p class="mb-3 text-xs text-muted">Le client devra choisir une de ces tailles. Laissez vide si le produit n’a pas de taille.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($sizeOptions as $size)
                            <button type="button" @click="toggleSize(@js($size))" :aria-pressed="sizes.includes(@js($size))"
                                    class="h-10 min-w-12 border px-3 text-sm font-semibold transition-colors"
                                    :class="sizes.includes(@js($size)) ? 'border-ink bg-ink text-paper' : 'border-line hover:border-ink'">{{ $size }}</button>
                        @endforeach
                        <template x-for="size in sizes.filter(s => !@js($sizeOptions).includes(s))" :key="size">
                            <button type="button" @click="toggleSize(size)" class="inline-flex h-10 items-center gap-1.5 border border-ink bg-ink px-3 text-sm font-semibold text-paper">
                                <span x-text="size"></span><x-icon name="x" class="size-3.5" />
                            </button>
                        </template>
                    </div>
                    <div class="mt-3 flex max-w-xs gap-2">
                        <label for="custom-size" class="sr-only">Autre taille</label>
                        <input id="custom-size" type="text" x-model="customSize" @keydown.enter.prevent="addCustomSize()" maxlength="20"
                               placeholder="Autre taille (ex. 38, 40-42)" class="field h-10 text-sm">
                        <button type="button" class="btn btn-sm btn-outline h-10" @click="addCustomSize()">Ajouter</button>
                    </div>
                    <template x-for="size in sizes" :key="'s' + size"><input type="hidden" name="sizes[]" :value="size"></template>
                    @error('sizes')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="border-t border-line pt-6">
                    <p class="field-label">Couleurs disponibles</p>
                    <p class="mb-3 text-xs text-muted">Nom affiché au client + teinte de la pastille. Laissez vide si le produit n’existe qu’en une couleur.</p>

                    <ul class="space-y-2">
                        <template x-for="(color, index) in colors" :key="index">
                            <li class="flex items-center gap-2">
                                <label class="relative size-12 shrink-0 cursor-pointer overflow-hidden rounded-(--radius-field) border border-line" :style="`background-color: ${color.hex}`">
                                    <span class="sr-only">Teinte</span>
                                    <input type="color" x-model="color.hex" class="absolute inset-0 size-full cursor-pointer opacity-0">
                                </label>
                                <input type="text" x-model="color.name" :name="`colors[${index}][name]`" maxlength="40" placeholder="Nom (ex. Noir)" class="field h-12 flex-1" aria-label="Nom de la couleur">
                                <input type="hidden" :name="`colors[${index}][hex]`" :value="color.hex">
                                <button type="button" class="inline-flex size-12 shrink-0 items-center justify-center text-muted hover:text-accent" @click="removeColor(index)" aria-label="Retirer la couleur">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </li>
                        </template>
                    </ul>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline" @click="addColor()"><x-icon name="plus" class="size-3.5" /> Ajouter une couleur</button>
                        <span class="text-xs text-muted">ou rapidement :</span>
                        @foreach ($presets as $preset)
                            <button type="button" @click="addColor(@js($preset))" x-show="!colors.some(c => c.name === @js($preset['name']))"
                                    class="inline-flex h-8 items-center gap-1.5 border border-line px-2 text-xs font-medium hover:border-ink">
                                <span class="size-3 rounded-full border border-ink/10" style="background-color: {{ $preset['hex'] }}"></span>{{ $preset['name'] }}
                            </button>
                        @endforeach
                    </div>
                    @foreach ($errors->get('colors.*') as $messages)
                        <p class="field-error">{{ $messages[0] }}</p>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ----------------------------------------------- Colonne droite --}}
        <aside class="space-y-6">
            <section class="space-y-5 bg-white p-5 ring-1 ring-line sm:p-6">
                <x-admin.toggle name="is_active" label="Actif" :checked="old('is_active', $product->is_active)"
                                hint="Visible et commandable sur la boutique." />
                <div class="border-t border-line pt-5">
                    <x-admin.toggle name="is_featured" label="En vedette" :checked="old('is_featured', $product->is_featured)"
                                    hint="Affiché dans « Meilleures ventes » sur l’accueil." />
                </div>
            </section>

            <section class="space-y-5 bg-white p-5 ring-1 ring-line sm:p-6">
                <div>
                    <label for="category_id" class="field-label">Catégorie <span class="text-accent">*</span></label>
                    <select id="category_id" name="category_id" required @class(['field', 'field-invalid' => $errors->has('category_id')])>
                        <option value="">Choisir…</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((int) old('category_id', $product->category_id) === $cat->id)>
                                {{ $cat->name }}{{ $cat->is_active ? '' : ' (masquée)' }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="space-y-5 bg-white p-5 ring-1 ring-line sm:p-6"
                     x-data="{ price: @js((string) old('price', $product->price ?? '')), sale: @js((string) old('sale_price', $product->sale_price ?? '')) }">
                <div>
                    <label for="price" class="field-label">Prix (DH) <span class="text-accent">*</span></label>
                    <input id="price" name="price" type="number" step="0.01" min="0" inputmode="decimal" x-model="price" value="{{ old('price', $product->price) }}" required
                           placeholder="Ex. 299" @class(['field tabular-nums', 'field-invalid' => $errors->has('price')])>
                    @error('price')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sale_price" class="field-label">Prix remisé (DH)</label>
                    <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" inputmode="decimal" x-model="sale" value="{{ old('sale_price', $product->sale_price) }}"
                           placeholder="Optionnel" @class(['field tabular-nums', 'field-invalid' => $errors->has('sale_price')])>
                    <p class="mt-1.5 text-xs" x-cloak
                       x-show="sale !== '' && Number(price) > 0"
                       :class="Number(sale) < Number(price) ? 'text-success' : 'text-accent'"
                       x-text="Number(sale) < Number(price) ? `Remise affichée : −${Math.round((1 - Number(sale) / Number(price)) * 100)} %` : 'Doit être inférieur au prix normal.'"></p>
                    <p class="mt-1.5 text-xs text-muted" x-show="sale === ''">Remplissez pour afficher une promotion.</p>
                    @error('sale_price')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="border-t border-line pt-5">
                    <label for="stock_quantity" class="field-label">Stock <span class="text-accent">*</span></label>
                    <input id="stock_quantity" name="stock_quantity" type="number" min="0" step="1" inputmode="numeric" required
                           value="{{ old('stock_quantity', $product->stock_quantity) }}" @class(['field tabular-nums', 'field-invalid' => $errors->has('stock_quantity')])>
                    <p class="mt-1.5 text-xs text-muted">À 0, le produit s’affiche « Rupture » et ne peut plus être commandé.</p>
                    @error('stock_quantity')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </section>

            <div class="sticky bottom-0 z-10 -mx-4 flex flex-col gap-2 border-t border-line bg-paper/95 p-4 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-0 lg:border-0 lg:bg-transparent lg:p-0">
                <button type="submit" class="btn btn-lg btn-primary w-full" @click="syncInput()">
                    <x-icon name="check" class="size-4" /> {{ $editing ? 'Enregistrer' : 'Créer le produit' }}
                </button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-ghost hidden w-full lg:inline-flex">Annuler</a>
            </div>
        </aside>
    </form>

    @if ($editing)
        <div class="mt-10 flex flex-col gap-3 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-muted">Pour masquer le produit temporairement, désactivez-le plutôt que de le supprimer.</p>
            <x-admin.confirm-delete :action="route('admin.products.destroy', $product)"
                                    :title="'Supprimer « '.$product->name.' » ?'"
                                    message="Le produit et toutes ses photos seront supprimés définitivement." />
        </div>
    @endif
</x-layouts.admin>
