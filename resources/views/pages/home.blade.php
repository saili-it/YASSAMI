<x-layouts.app>
    {{-- ================================================================ HERO --}}
    <section class="relative overflow-hidden bg-ink text-paper">
        {{--
            Photo : Anastase Maragos — Unsplash (unsplash.com/photos/9dzWZQWZMdE), licence Unsplash
            (usage commercial libre, sans attribution obligatoire). À remplacer par un visuel de campagne YASSAMI.
        --}}
        <div class="absolute inset-0 lg:left-auto lg:w-[62%]" aria-hidden="true">
            <picture class="block size-full">
                <source media="(min-width: 1024px)" srcset="{{ asset('images/hero/hero-training.webp') }}" type="image/webp">
                <source srcset="{{ asset('images/hero/hero-training-mobile.webp') }}" type="image/webp">
                <img src="{{ asset('images/hero/hero-training.jpg') }}" alt="" width="2400" height="1600"
                     fetchpriority="high" decoding="async"
                     class="size-full object-cover object-[50%_60%] opacity-60 lg:opacity-100">
            </picture>
            {{-- Fondus vers le noir : lisibilité du texte + raccord avec la section --}}
            <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/40 to-ink/70 lg:bg-gradient-to-r lg:from-ink lg:via-ink/30 lg:to-transparent"></div>
            <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-ink to-transparent"></div>
        </div>

        <div class="container-brand relative flex min-h-[min(calc(100svh-6.25rem),46rem)] items-end pb-14 pt-24 sm:items-center sm:py-14 lg:min-h-[44rem] lg:py-20">
            <div class="max-w-2xl">
                <p class="eyebrow animate-fade-up text-paper/70">Sportswear premium · Maroc</p>

                <h1 class="display mt-5 text-[clamp(3.6rem,15vw,9.5rem)] italic [animation-delay:80ms] animate-fade-up">
                    Bouge<br>
                    <span class="text-outline">sans</span><br>
                    limites<span class="text-accent">.</span>
                </h1>

                <p class="mt-6 max-w-md text-base leading-relaxed text-paper/75 [animation-delay:160ms] animate-fade-up sm:text-lg">
                    Des pièces techniques aux coupes nettes, pensées pour l’entraînement, le running et la vie de tous les jours.
                </p>

                <div class="mt-9 flex flex-col gap-3 [animation-delay:240ms] animate-fade-up sm:flex-row">
                    <a href="{{ route('shop.index') }}" class="btn btn-light btn-lg">
                        Découvrir la boutique <x-icon name="arrow-right" class="size-4" />
                    </a>
                    <a href="{{ route('shop.index', ['tri' => 'nouveautes']) }}" class="btn btn-outline-light btn-lg">Nouveautés</a>
                </div>
            </div>
        </div>

        {{-- Bandeau défilant des catégories --}}
        @if ($categories->isNotEmpty())
            <div class="relative overflow-hidden border-t border-paper/10 py-4" aria-hidden="true">
                <div class="flex w-max animate-marquee gap-10 whitespace-nowrap">
                    @foreach (range(1, 4) as $_)
                        @foreach ($categories as $cat)
                            <span class="display text-2xl text-paper/30">{{ $cat->name }}</span>
                            <span class="display text-2xl text-accent">/</span>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    {{-- ========================================================== CATÉGORIES --}}
    @if ($categories->isNotEmpty())
        <section class="container-brand pt-16 sm:pt-24">
            <x-section-heading eyebrow="Explorer" title="Les catégories" :href="route('shop.index')" link-label="Toute la boutique" />

            <ul class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                @foreach ($categories as $i => $cat)
                    <li>
                        <a href="{{ route('shop.category', $cat) }}"
                           @class([
                               'group relative flex aspect-[4/3] flex-col justify-between overflow-hidden p-4 sm:aspect-[16/10] sm:p-6',
                               'bg-ink text-paper' => $i % 3 === 0,
                               'bg-bone text-ink' => $i % 3 !== 0,
                           ])>
                            @if ($cat->image_url)
                                <img src="{{ $cat->image_url }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover opacity-60 transition-transform duration-700 group-hover:scale-105">
                            @endif
                            <span class="relative text-xs font-semibold tabular-nums opacity-60">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <div class="relative flex items-end justify-between gap-3">
                                <div>
                                    <h3 class="display text-[1.65rem] sm:text-4xl">{{ $cat->name }}</h3>
                                    <p class="mt-1 text-xs opacity-60">{{ $cat->products_count }} {{ Str::plural('produit', $cat->products_count) }}</p>
                                </div>
                                <x-icon name="arrow-up-right" class="size-5 shrink-0 transition-transform duration-300 group-hover:-translate-y-1 group-hover:translate-x-1 sm:size-6" />
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ========================================================== NOUVEAUTÉS --}}
    @if ($newArrivals->isNotEmpty())
        <section class="container-brand pt-16 sm:pt-24">
            <x-section-heading eyebrow="Fraîchement arrivés" title="Nouveautés" :href="route('shop.index', ['tri' => 'nouveautes'])" />
            <div class="scroll-rail -mx-4 px-4 sm:mx-0 sm:grid sm:grid-cols-3 sm:overflow-visible sm:px-0 lg:grid-cols-4">
                @foreach ($newArrivals as $product)
                    <x-product-card :product="$product" class="w-[68%] shrink-0 sm:w-auto" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===================================================== MEILLEURES VENTES --}}
    @if ($bestSellers->isNotEmpty())
        <section class="container-brand pt-16 sm:pt-24">
            <x-section-heading eyebrow="Sélection" title="Meilleures ventes" :href="route('shop.index')" />
            <div class="grid grid-cols-2 gap-x-3 gap-y-8 sm:grid-cols-3 sm:gap-x-5 lg:grid-cols-4">
                @foreach ($bestSellers as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============================================================= MARQUE --}}
    <section class="container-brand pt-20 sm:pt-28">
        <div class="grid gap-8 border-t border-ink pt-10 lg:grid-cols-12 lg:gap-12">
            <p class="eyebrow lg:col-span-3">L’esprit {{ config('yassami.brand') }}</p>
            <div class="lg:col-span-9">
                <h2 class="display text-[clamp(2.5rem,6.5vw,5.25rem)]">
                    Conçu pour l’effort.<br><span class="text-muted">Porté avec style.</span>
                </h2>
                <p class="mt-6 max-w-2xl text-base leading-relaxed text-muted sm:text-lg">
                    {{ config('yassami.brand') }} habille celles et ceux qui bougent — à la salle, sur la route ou en ville.
                    Des matières qui suivent chaque mouvement, des coupes nettes et une identité forte, pour se sentir prêt dès la première minute.
                </p>
            </div>
        </div>
    </section>

    {{-- ========================================================== AVANTAGES --}}
    <section class="container-brand pt-16 sm:pt-20">
        @php
            $deliveryFee = \App\Support\Delivery::fee();
            $deliveryText = match (true) {
                $deliveryFee === null => 'Délais et frais de livraison confirmés directement avec vous avant l’expédition.',
                $deliveryFee == 0 => 'Livraison gratuite partout au Maroc. Délais confirmés avec vous avant l’expédition.',
                default => 'Partout au Maroc pour '.mad($deliveryFee).'. Délais confirmés avec vous avant l’expédition.',
            };
            $benefits = [
                ['icon' => 'bag', 'title' => 'Commande en ligne', 'text' => 'Sans création de compte : choisissez vos articles et validez votre commande en une minute.'],
                ['icon' => 'message', 'title' => 'Confirmation personnalisée', 'text' => 'Taille, couleur et disponibilité sont vérifiées avec vous avant l’expédition.'],
                ['icon' => 'truck', 'title' => 'Livraison au Maroc', 'text' => $deliveryText],
            ];
        @endphp
        <ul class="grid gap-px overflow-hidden border border-line bg-line sm:grid-cols-3">
            @foreach ($benefits as $benefit)
                <li class="bg-paper p-6 sm:p-8">
                    <x-icon :name="$benefit['icon']" class="size-6" />
                    <h3 class="mt-5 text-sm font-semibold uppercase tracking-[0.08em]">{{ $benefit['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $benefit['text'] }}</p>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.app>
