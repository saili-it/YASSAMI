<x-layouts.app title="Panier" description="Votre panier — finalisez votre commande en quelques clics.">
    <div class="container-brand pt-8 sm:pt-12">
        <h1 class="display text-[clamp(2.75rem,9vw,5.5rem)]">Panier</h1>

        {{-- Vide --}}
        <div x-cloak x-show="$store.cart.items.length === 0" class="mt-8 flex flex-col items-center border border-dashed border-line px-6 py-20 text-center">
            <x-icon name="bag" class="size-10 text-muted" />
            <p class="display mt-4 text-3xl">Votre panier est vide</p>
            <p class="mt-2 max-w-sm text-sm text-muted">Parcourez la boutique, choisissez votre taille et votre couleur, puis revenez ici pour commander.</p>
            <a href="{{ route('shop.index') }}" class="btn btn-primary btn-md mt-6">Voir la boutique</a>
        </div>

        <div x-cloak x-show="$store.cart.items.length > 0" class="mt-8 grid gap-10 lg:grid-cols-12 lg:gap-14">
            {{-- --------------------------------------------- Articles --}}
            <section class="lg:col-span-7" aria-labelledby="cart-items-title">
                <h2 id="cart-items-title" class="sr-only">Articles</h2>
                <ul class="divide-y divide-line border-y border-line">
                    <template x-for="item in $store.cart.items" :key="item.key">
                        <li class="flex gap-4 py-5 sm:gap-6">
                            <a :href="`/produit/${item.slug}`" class="block w-24 shrink-0 sm:w-28">
                                <div class="aspect-[4/5] overflow-hidden bg-bone">
                                    <img x-show="item.image" :src="item.image" :alt="item.name" class="size-full object-cover">
                                </div>
                            </a>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <a :href="`/produit/${item.slug}`" class="font-medium leading-snug hover:underline" x-text="item.name"></a>
                                        <p class="mt-1 text-sm text-muted" x-text="[item.size && `Taille ${item.size}`, item.color && `Couleur ${item.color}`].filter(Boolean).join(' · ')"></p>
                                        <p class="mt-1 text-sm tabular-nums" x-text="$store.cart.format(item.price)"></p>
                                    </div>
                                    <span class="whitespace-nowrap font-semibold tabular-nums" x-text="$store.cart.format(item.price * item.quantity)"></span>
                                </div>
                                <div class="mt-auto flex items-center justify-between pt-3">
                                    <div class="flex h-10 items-center border border-line bg-white">
                                        <button type="button" class="flex size-10 items-center justify-center disabled:opacity-30" :disabled="item.quantity <= 1"
                                                @click="$store.cart.setQuantity(item.key, item.quantity - 1)" aria-label="Diminuer la quantité">
                                            <x-icon name="minus" class="size-4" />
                                        </button>
                                        <span class="w-8 text-center text-sm font-semibold tabular-nums" x-text="item.quantity"></span>
                                        <button type="button" class="flex size-10 items-center justify-center disabled:opacity-30" :disabled="!$store.cart.canIncrement(item)"
                                                @click="$store.cart.setQuantity(item.key, item.quantity + 1)" aria-label="Augmenter la quantité">
                                            <x-icon name="plus" class="size-4" />
                                        </button>
                                    </div>
                                    <button type="button" class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-muted hover:text-accent"
                                            @click="$store.cart.remove(item.key)">
                                        <x-icon name="trash" class="size-4" /> Retirer
                                    </button>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
                <a href="{{ route('shop.index') }}" class="mt-6 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em]">
                    <x-icon name="chevron-left" class="size-4" /> <span class="link-underline">Continuer mes achats</span>
                </a>
            </section>

            {{-- ---------------------------------------------- Commande --}}
            <section id="commande" class="scroll-mt-28 lg:col-span-5" aria-labelledby="checkout-title"
                     x-data="checkoutForm(@js(route('orders.store')))">
                <div class="bg-white p-5 ring-1 ring-line sm:p-8 lg:sticky lg:top-24">
                    <h2 id="checkout-title" class="display text-3xl">Vos coordonnées</h2>
                    <p class="mt-1 text-sm text-muted">Nous vous contactons pour confirmer la commande avant l’expédition.</p>

                    <form class="mt-6 space-y-4" @submit.prevent="submit()" novalidate>
                        @php
                            $fields = [
                                ['name' => 'name', 'label' => 'Nom complet', 'type' => 'text', 'autocomplete' => 'name', 'placeholder' => 'Prénom et nom'],
                                ['name' => 'phone', 'label' => 'Téléphone', 'type' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '06 12 34 56 78'],
                                ['name' => 'city', 'label' => 'Ville', 'type' => 'text', 'autocomplete' => 'address-level2', 'placeholder' => 'Ex. Casablanca'],
                            ];
                        @endphp
                        @foreach ($fields as $f)
                            <div>
                                <label for="checkout-{{ $f['name'] }}" class="field-label">{{ $f['label'] }}</label>
                                <input id="checkout-{{ $f['name'] }}" type="{{ $f['type'] }}" autocomplete="{{ $f['autocomplete'] }}" placeholder="{{ $f['placeholder'] }}"
                                       x-model.trim="customer.{{ $f['name'] }}" @input="delete errors['customer.{{ $f['name'] }}']"
                                       @if ($f['type'] === 'tel') inputmode="tel" @endif
                                       class="field" :class="fieldError('{{ $f['name'] }}') && 'field-invalid'" :data-invalid="!!fieldError('{{ $f['name'] }}')"
                                       :aria-invalid="!!fieldError('{{ $f['name'] }}')" aria-describedby="checkout-{{ $f['name'] }}-error" required>
                                <p id="checkout-{{ $f['name'] }}-error" class="field-error" x-show="fieldError('{{ $f['name'] }}')" x-text="fieldError('{{ $f['name'] }}')"></p>
                            </div>
                        @endforeach
                        <div>
                            <label for="checkout-address" class="field-label">Adresse de livraison</label>
                            <textarea id="checkout-address" rows="3" autocomplete="street-address" placeholder="Rue, numéro, quartier, repère…"
                                      x-model.trim="customer.address" @input="delete errors['customer.address']"
                                      class="field resize-none" :class="fieldError('address') && 'field-invalid'" :data-invalid="!!fieldError('address')"
                                      :aria-invalid="!!fieldError('address')" aria-describedby="checkout-address-error" required></textarea>
                            <p id="checkout-address-error" class="field-error" x-show="fieldError('address')" x-text="fieldError('address')"></p>
                        </div>

                        <dl class="space-y-2 border-t border-line pt-5 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-muted">Articles (<span x-text="$store.cart.count"></span>)</dt>
                                <dd class="tabular-nums" x-text="$store.cart.format($store.cart.subtotal)"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted">Livraison <span class="text-xs">(tout le Maroc)</span></dt>
                                <dd class="tabular-nums" :class="!$store.cart.hasDeliveryFee && 'text-muted'"
                                    x-text="$store.cart.hasDeliveryFee ? $store.cart.deliveryLabel : 'À confirmer avec vous'"></dd>
                            </div>
                            <div class="flex items-baseline justify-between border-t border-line pt-3">
                                <dt class="font-semibold" x-text="$store.cart.hasDeliveryFee ? 'Total' : 'Sous-total'"></dt>
                                <dd class="text-xl font-semibold tabular-nums" x-text="$store.cart.format($store.cart.total)"></dd>
                            </div>
                        </dl>

                        <div x-show="globalErrors.length" role="alert" class="flex gap-3 bg-accent/10 p-4 text-sm text-accent-strong">
                            <x-icon name="alert" class="mt-0.5 size-4" />
                            <ul class="space-y-1"><template x-for="msg in globalErrors"><li x-text="msg"></li></template></ul>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="submitting">
                            <x-icon name="check" class="size-5" x-show="!submitting" />
                            <svg x-show="submitting" x-cloak class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" stroke-dasharray="42 100" /></svg>
                            <span x-text="submitting ? 'Envoi en cours…' : 'Valider ma commande'"></span>
                        </button>
                        <p class="text-center text-xs leading-relaxed text-muted">
                            Aucun paiement en ligne. Notre équipe vous contacte pour confirmer la commande<span x-show="!$store.cart.hasDeliveryFee"> et les frais de livraison</span>.
                        </p>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
