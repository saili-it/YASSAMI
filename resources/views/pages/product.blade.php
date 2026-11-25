@php
    $images = $product->images;
    $payload = [
        'id' => $product->id,
        'slug' => $product->slug,
        'name' => $product->name,
        'price' => $product->effective_price,
        'image' => $product->primary_image_url,
        'stock' => $product->stock_quantity,
        'sizes' => $product->sizes ?? [],
        'colors' => $product->color_options,
    ];
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $product->sku,
        'description' => Str::limit(strip_tags((string) $product->description), 300),
        'image' => $images->pluck('url')->all(),
        'category' => $product->category->name,
        'brand' => ['@type' => 'Brand', 'name' => config('yassami.brand')],
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'MAD',
            'price' => number_format($product->effective_price, 2, '.', ''),
            'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => route('products.show', $product),
        ],
    ];
@endphp

<x-layouts.app :title="$product->name" :description="Str::limit(strip_tags((string) $product->description), 155)"
               :canonical="route('products.show', $product)" :image="$product->primary_image_url"
               :whatsapp-message="'Bonjour, je suis intéressé(e) par « '.$product->name.' » (réf. '.$product->sku.') : '.route('products.show', $product)">
    @push('head')
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush

    <div class="container-brand pt-6 sm:pt-10" x-data="productForm(@js($payload))">
        <nav aria-label="Fil d’Ariane" class="mb-6 flex items-center gap-1.5 overflow-hidden text-xs text-muted">
            <a href="{{ route('home') }}" class="shrink-0 hover:text-ink">Accueil</a>
            <x-icon name="chevron-right" class="size-3" />
            <a href="{{ route('shop.category', $product->category) }}" class="shrink-0 hover:text-ink">{{ $product->category->name }}</a>
            <x-icon name="chevron-right" class="size-3" />
            <span class="truncate text-ink">{{ $product->name }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-12 lg:gap-14">
            {{-- ------------------------------------------------ Galerie --}}
            <div class="lg:col-span-7">
                <div class="-mx-4 sm:mx-0 lg:sticky lg:top-24">
                    <div class="relative">
                        @if ($images->isEmpty())
                            <x-product-media :product="$product" eager />
                        @else
                            {{-- Bande défilante : glisser au doigt, trackpad, flèches ou miniatures (scroll-snap). --}}
                            <div x-ref="track" @scroll.debounce.60ms="syncImage($el)" data-gallery
                                 @keydown.arrow-right.prevent="goToImage(activeImage + 1)" @keydown.arrow-left.prevent="goToImage(activeImage - 1)"
                                 tabindex="0" role="region" aria-roledescription="carrousel" aria-label="Photos du produit"
                                 class="flex snap-x snap-mandatory overflow-x-auto overscroll-x-contain focus-visible:outline-offset-[-2px] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                @foreach ($images as $i => $image)
                                    <div class="w-full shrink-0 snap-center snap-always" role="group" aria-label="Photo {{ $i + 1 }} sur {{ $images->count() }}">
                                        <x-product-media :product="$product" :image="$image->url" :eager="$i === 0" />
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($product->has_discount)
                            <span class="pointer-events-none absolute left-4 top-4 bg-accent px-2.5 py-1 text-xs font-bold uppercase tracking-[0.12em] text-white">−{{ $product->discount_percent }}%</span>
                        @endif

                        @if ($images->count() > 1)
                            {{-- Compteur --}}
                            <span class="pointer-events-none absolute right-4 top-4 bg-ink/70 px-2 py-1 text-xs font-semibold tabular-nums text-paper">
                                <span x-text="activeImage + 1">1</span>&nbsp;/&nbsp;{{ $images->count() }}
                            </span>

                            {{-- Flèches (écrans avec souris) --}}
                            <button type="button" x-cloak x-show="activeImage > 0" x-transition.opacity @click="goToImage(activeImage - 1)"
                                    class="absolute left-3 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center bg-paper/90 text-ink shadow-md transition hover:bg-paper sm:flex"
                                    aria-label="Photo précédente">
                                <x-icon name="chevron-left" class="size-5" />
                            </button>
                            <button type="button" x-show="activeImage < {{ $images->count() - 1 }}" x-transition.opacity @click="goToImage(activeImage + 1)"
                                    class="absolute right-3 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center bg-paper/90 text-ink shadow-md transition hover:bg-paper sm:flex"
                                    aria-label="Photo suivante">
                                <x-icon name="chevron-right" class="size-5" />
                            </button>

                            {{-- Points (mobile) --}}
                            <div class="absolute inset-x-0 bottom-4 flex justify-center gap-1.5 sm:hidden">
                                @foreach ($images as $i => $image)
                                    <button type="button" class="h-1 w-6 transition-colors" :class="activeImage === {{ $i }} ? 'bg-ink' : 'bg-ink/25'"
                                            @click="goToImage({{ $i }})" aria-label="Photo {{ $i + 1 }}"></button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($images->count() > 1)
                        <div class="mt-3 hidden grid-cols-5 gap-3 sm:grid">
                            @foreach ($images as $i => $image)
                                <button type="button" class="relative aspect-[4/5] overflow-hidden bg-bone ring-offset-2 ring-offset-paper transition"
                                        :class="activeImage === {{ $i }} ? 'ring-2 ring-ink' : 'opacity-70 hover:opacity-100'"
                                        @click="goToImage({{ $i }})" aria-label="Afficher la photo {{ $i + 1 }}">
                                    <img src="{{ $image->url }}" alt="" loading="lazy" class="size-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- -------------------------------------------- Informations --}}
            <div class="lg:col-span-5 lg:pt-2">
                <p class="eyebrow">{{ $product->category->name }}</p>
                <h1 class="display mt-2 text-[clamp(2.5rem,7vw,4rem)]">{{ $product->name }}</h1>
                <x-price :product="$product" size="lg" class="mt-4" />

                <p class="mt-3 flex items-center gap-2 text-sm font-medium">
                    @if (! $product->in_stock)
                        <span class="size-2 rounded-full bg-muted"></span><span class="text-muted">Rupture de stock</span>
                    @elseif ($product->is_low_stock)
                        <span class="size-2 rounded-full bg-warning"></span><span class="text-warning">Plus que {{ $product->stock_quantity }} en stock</span>
                    @else
                        <span class="size-2 rounded-full bg-success"></span><span class="text-success">En stock</span>
                    @endif
                </p>

                <div class="mt-8 space-y-7 border-t border-line pt-7" data-variants>
                    {{-- Couleurs --}}
                    @if (count($payload['colors']))
                        <fieldset>
                            <legend class="field-label">Couleur : <span class="font-normal normal-case tracking-normal text-muted" x-text="color ?? 'à choisir'"></span></legend>
                            <div class="mt-3 flex flex-wrap gap-2.5">
                                @foreach ($payload['colors'] as $c)
                                    <button type="button" role="radio" :aria-checked="color === @js($c['name'])" @click="color = @js($c['name']); error = null"
                                            class="relative flex size-10 items-center justify-center rounded-full ring-offset-2 ring-offset-paper transition"
                                            :class="color === @js($c['name']) ? 'ring-2 ring-ink' : 'ring-1 ring-line hover:ring-ink/50'"
                                            title="{{ $c['name'] }}">
                                        <span class="size-8 rounded-full border border-ink/10" style="background-color: {{ $c['hex'] }}"></span>
                                        <span class="sr-only">{{ $c['name'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    {{-- Tailles --}}
                    @if (count($payload['sizes']))
                        <fieldset>
                            <legend class="field-label">Taille : <span class="font-normal normal-case tracking-normal text-muted" x-text="size ?? 'à choisir'"></span></legend>
                            <div class="mt-3 grid grid-cols-5 gap-2 sm:grid-cols-6">
                                @foreach ($payload['sizes'] as $s)
                                    <button type="button" role="radio" :aria-checked="size === @js($s)" @click="size = @js($s); error = null"
                                            class="h-11 border text-sm font-semibold transition-colors {{ mb_strlen($s) > 4 ? 'col-span-2' : '' }}"
                                            :class="size === @js($s) ? 'border-ink bg-ink text-paper' : 'border-line bg-white hover:border-ink'">{{ $s }}</button>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    {{-- Quantité --}}
                    @if ($product->in_stock)
                        <div>
                            <span class="field-label" id="qty-label">Quantité</span>
                            <div class="mt-3 inline-flex h-11 items-center border border-line bg-white" role="group" aria-labelledby="qty-label">
                                <button type="button" class="flex size-11 items-center justify-center disabled:opacity-30" :disabled="quantity <= 1" @click="quantity--" aria-label="Diminuer">
                                    <x-icon name="minus" class="size-4" />
                                </button>
                                <span class="w-10 text-center font-semibold tabular-nums" x-text="quantity" aria-live="polite"></span>
                                <button type="button" class="flex size-11 items-center justify-center disabled:opacity-30" :disabled="quantity >= maxQuantity" @click="quantity++" aria-label="Augmenter">
                                    <x-icon name="plus" class="size-4" />
                                </button>
                            </div>
                        </div>
                    @endif

                    <p x-cloak x-show="error" x-text="error" role="alert" class="flex items-center gap-2 text-sm font-medium text-accent"></p>

                    @unless ($product->in_stock)
                        <button type="button" class="btn btn-primary btn-lg w-full" disabled>Rupture de stock</button>
                    @endunless
                </div>

                @if ($product->in_stock)
                    {{-- -------------------------------- Commande directe (ce produit seulement) --}}
                    <section id="commander" class="mt-8 scroll-mt-28" aria-labelledby="direct-order-title"
                             x-data="checkoutForm(@js(route('orders.store')), { direct: true })">
                        <div class="bg-white p-5 ring-1 ring-line sm:p-6">
                            <h2 id="direct-order-title" class="display text-[1.75rem]">Commander maintenant</h2>
                            <p class="mt-1 text-sm text-muted">Remplissez vos coordonnées : nous vous contactons pour confirmer avant l’expédition.</p>

                            <form class="mt-5 space-y-4" @submit.prevent="submit()" novalidate data-direct-order>
                                @include('pages.partials.checkout-fields')

                                <dl class="space-y-2 border-t border-line pt-4 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-muted">
                                            Article<span x-show="quantity > 1"> × <span x-text="quantity"></span></span>
                                            <span class="block text-xs" x-show="size || color" x-text="[size && `Taille ${size}`, color].filter(Boolean).join(' · ')"></span>
                                        </dt>
                                        <dd class="whitespace-nowrap tabular-nums" x-text="$store.cart.format(lineTotal)"></dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-muted">Livraison <span class="text-xs">(tout le Maroc)</span></dt>
                                        <dd class="tabular-nums" :class="!$store.cart.hasDeliveryFee && 'text-muted'"
                                            x-text="$store.cart.hasDeliveryFee ? $store.cart.deliveryLabel : 'À confirmer avec vous'"></dd>
                                    </div>
                                    <div class="flex items-baseline justify-between border-t border-line pt-3">
                                        <dt class="font-semibold" x-text="$store.cart.hasDeliveryFee ? 'Total' : 'Sous-total'"></dt>
                                        <dd class="text-xl font-semibold tabular-nums" x-text="$store.cart.format(lineTotal + ($store.cart.deliveryFee ?? 0))"></dd>
                                    </div>
                                </dl>

                                <div x-cloak x-show="globalErrors.length" role="alert" class="flex gap-3 bg-accent/10 p-4 text-sm text-accent-strong">
                                    <x-icon name="alert" class="mt-0.5 size-4" />
                                    <ul class="space-y-1"><template x-for="msg in globalErrors"><li x-text="msg"></li></template></ul>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="submitting">
                                    <x-icon name="check" class="size-5" x-show="!submitting" />
                                    <svg x-show="submitting" x-cloak class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" stroke-dasharray="42 100" /></svg>
                                    <span x-text="submitting ? 'Envoi en cours…' : 'Passer la commande'">Passer la commande</span>
                                </button>
                                <p class="text-center text-xs leading-relaxed text-muted">
                                    Aucun paiement en ligne. Notre équipe vous contacte pour confirmer la commande<span x-show="!$store.cart.hasDeliveryFee"> et les frais de livraison</span>.
                                </p>
                            </form>
                        </div>

                        <button type="button" class="btn btn-outline btn-md mt-3 w-full" @click="addToCart()">
                            <x-icon name="bag" class="size-4" /> Ou ajouter au panier
                        </button>
                    </section>
                @endif

                @if ($product->description)
                    <div class="mt-10 border-t border-line pt-7">
                        <h2 class="field-label">Description</h2>
                        <div class="mt-3 whitespace-pre-line text-[0.9375rem] leading-relaxed text-ink/80">{{ $product->description }}</div>
                    </div>
                @endif

                <dl class="mt-7 grid grid-cols-2 gap-4 border-t border-line pt-7 text-sm">
                    <div><dt class="text-xs uppercase tracking-[0.12em] text-muted">Référence</dt><dd class="mt-1 font-medium">{{ $product->sku }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-[0.12em] text-muted">Catégorie</dt><dd class="mt-1 font-medium">{{ $product->category->name }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="container-brand pt-20 sm:pt-28">
            <x-section-heading eyebrow="Dans la même catégorie" title="Vous aimerez aussi" :href="route('shop.category', $product->category)" />
            <div class="scroll-rail -mx-4 px-4 sm:mx-0 sm:grid sm:grid-cols-3 sm:overflow-visible sm:px-0 lg:grid-cols-4">
                @foreach ($related as $item)
                    <x-product-card :product="$item" class="w-[68%] shrink-0 sm:w-auto" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
