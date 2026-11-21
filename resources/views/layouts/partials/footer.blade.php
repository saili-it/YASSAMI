@php
    $contact = config('yassami.contact');
    $social = config('yassami.social');
    $whatsapp = config('yassami.whatsapp_number');
    $hasContact = $whatsapp || array_filter($contact);
@endphp

<footer class="mt-24 bg-ink text-paper">
    <div class="container-brand grid gap-12 py-16 sm:grid-cols-2 lg:grid-cols-12 lg:py-20">
        <div class="lg:col-span-5">
            <x-logo tone="light" class="h-20" />
            <p class="mt-5 max-w-sm text-sm leading-relaxed text-paper/60">
                Vêtements et accessoires de sport premium. Choisissez vos articles, validez votre commande : nous vous contactons pour la confirmer.
            </p>

            @if ($social)
                <ul class="mt-6 flex gap-2">
                    @foreach ($social as $network => $url)
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}"
                               class="flex size-10 items-center justify-center border border-paper/20 transition-colors hover:border-paper hover:bg-paper hover:text-ink">
                                <x-icon :name="$network" class="size-4" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <nav class="lg:col-span-3" aria-label="Catégories">
            <p class="mb-4 text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-paper/45">Boutique</p>
            <ul class="space-y-2.5 text-sm">
                <li><a href="{{ route('shop.index') }}" class="link-underline text-paper/80 hover:text-paper">Tous les produits</a></li>
                @foreach ($navCategories as $cat)
                    <li><a href="{{ route('shop.category', $cat) }}" class="link-underline text-paper/80 hover:text-paper">{{ $cat->name }}</a></li>
                @endforeach
                <li><a href="{{ route('shop.index', ['promo' => 1]) }}" class="link-underline text-paper/80 hover:text-paper">Promotions</a></li>
            </ul>
        </nav>

        <div class="lg:col-span-4">
            <p class="mb-4 text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-paper/45">Commande & contact</p>
            <ul class="space-y-3 text-sm text-paper/80">
                <li>
                    <a href="{{ route('cart') }}" class="inline-flex items-center gap-2.5 hover:text-paper">
                        <x-icon name="bag" class="size-4 text-paper/50" /> Mon panier
                    </a>
                </li>
                @if ($whatsapp)
                    <li>
                        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2.5 hover:text-paper">
                            <x-icon name="whatsapp" class="size-4 text-whatsapp" /> Nous écrire sur WhatsApp
                        </a>
                    </li>
                @endif
                @if ($contact['phone'])
                    <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}" class="inline-flex items-center gap-2.5 hover:text-paper"><x-icon name="phone" class="size-4 text-paper/50" /> {{ $contact['phone'] }}</a></li>
                @endif
                @if ($contact['email'])
                    <li><a href="mailto:{{ $contact['email'] }}" class="inline-flex items-center gap-2.5 hover:text-paper"><x-icon name="mail" class="size-4 text-paper/50" /> {{ $contact['email'] }}</a></li>
                @endif
                @if ($contact['address'])
                    <li class="inline-flex items-start gap-2.5"><x-icon name="map-pin" class="mt-0.5 size-4 text-paper/50" /> {{ $contact['address'] }}</li>
                @endif
            </ul>
        </div>
    </div>

    <div class="border-t border-paper/10">
        <div class="container-brand flex flex-col gap-2 py-6 text-xs text-paper/45 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} {{ config('yassami.brand') }}. Tous droits réservés.</p>
            <p>Prix en dirhams marocains (DH), TTC.</p>
        </div>
    </div>
</footer>
