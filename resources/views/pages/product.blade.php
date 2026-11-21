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
        'cartUrl' => route('cart'),
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
                        @forelse ($images as $i => $image)
                            <div x-show="activeImage === {{ $i }}" @if ($i > 0) x-cloak @endif>
                                <x-product-media :product="$product" :image="$image->url" :eager="$i === 0" />
                            </div>
                        @empty
                            <x-product-media :product="$product" eager />
                        @endforelse

                        @if ($product->has_discount)
                            <span class="absolute left-4 top-4 bg-accent px-2.5 py-1 text-xs font-bold uppercase tracking-[0.12em] text-white">−{{ $product->discount_percent }}%</span>
                        @endif

                        @if ($images->count() > 1)
                            <div class="absolute inset-x-0 bottom-4 flex justify-center gap-1.5 sm:hidden">
                                @foreach ($images as $i => $image)
                                    <button type="button" class="h-1 w-6 transition-colors" :class="activeImage === {{ $i }} ? 'bg-ink' : 'bg-ink/25'"
                                            @click="activeImage = {{ $i }}" aria-label="Image {{ $i + 1 }}"></button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($images->count() > 1)
                        <div class="mt-3 hidden grid-cols-5 gap-3 sm:grid">
                            @foreach ($images as $i => $image)
                                <button type="button" class="relative aspect-[4/5] overflow-hidden bg-bone ring-offset-2 ring-offset-paper transition"
                                        :class="activeImage === {{ $i }} ? 'ring-2 ring-ink' : 'opacity-70 hover:opacity-100'"
                                        @click="activeImage = {{ $i }}" aria-label="Afficher l’image {{ $i + 1 }}">
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

                <div class="mt-8 space-y-7 border-t border-line pt-7">
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

                    <div class="flex flex-col gap-3">
                        @if ($product->in_stock)
                            <button type="button" class="btn btn-primary btn-lg w-full" @click="addToCart()">
                                <x-icon name="bag" class="size-4" /> Ajouter au panier
                            </button>
                            <button type="button" class="btn btn-outline btn-lg w-full" @click="orderNow()">
                                Commander maintenant <x-icon name="arrow-right" class="size-4" />
                            </button>
                        @else
                            <button type="button" class="btn btn-primary btn-lg w-full" disabled>Rupture de stock</button>
                        @endif
                    </div>

                    @php $deliveryFee = \App\Support\Delivery::fee(); @endphp
                    @if ($deliveryFee !== null)
                        <p class="flex items-center gap-2.5 text-sm">
                            <x-icon name="truck" class="size-4 shrink-0" />
                            @if ($deliveryFee == 0)
                                <span><strong class="font-semibold">Livraison gratuite</strong> partout au Maroc</span>
                            @else
                                <span>Livraison partout au Maroc : <strong class="font-semibold tabular-nums">{{ mad($deliveryFee) }}</strong></span>
                            @endif
                        </p>
                    @endif
                </div>

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
