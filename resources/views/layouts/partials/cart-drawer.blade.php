<div x-cloak x-show="$store.cart.drawerOpen" class="fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title"
     @keydown.escape.window="$store.cart.drawerOpen = false">
    <div x-show="$store.cart.drawerOpen" x-transition.opacity class="absolute inset-0 bg-ink/50" @click="$store.cart.drawerOpen = false"></div>

    <aside x-show="$store.cart.drawerOpen"
           x-transition:enter="transition duration-300 ease-(--ease-brand)" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-paper">
        <div class="flex h-16 items-center justify-between border-b border-line px-5">
            <h2 id="cart-drawer-title" class="display text-2xl">
                Panier <span class="text-muted" x-text="`(${$store.cart.count})`"></span>
            </h2>
            <button type="button" class="-mr-2 inline-flex size-10 items-center justify-center" @click="$store.cart.drawerOpen = false" aria-label="Fermer le panier">
                <x-icon name="x" class="size-6" />
            </button>
        </div>

        {{-- Vide --}}
        <div x-show="$store.cart.items.length === 0" class="flex flex-1 flex-col items-center justify-center px-8 text-center">
            <x-icon name="bag" class="size-10 text-muted" />
            <p class="display mt-4 text-3xl">Votre panier est vide</p>
            <p class="mt-2 text-sm text-muted">Découvrez nos nouveautés et trouvez votre prochaine tenue.</p>
            <a href="{{ route('shop.index') }}" class="btn btn-primary btn-md mt-6">Voir la boutique</a>
        </div>

        {{-- Articles --}}
        <ul x-show="$store.cart.items.length > 0" class="flex-1 divide-y divide-line overflow-y-auto px-5">
            <template x-for="item in $store.cart.items" :key="item.key">
                <li class="flex gap-4 py-4">
                    <a :href="`/produit/${item.slug}`" class="block w-20 shrink-0">
                        <div class="aspect-[4/5] overflow-hidden bg-bone">
                            <img x-show="item.image" :src="item.image" :alt="item.name" class="size-full object-cover">
                        </div>
                    </a>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <div class="flex items-start justify-between gap-2">
                            <a :href="`/produit/${item.slug}`" class="text-sm font-medium leading-snug" x-text="item.name"></a>
                            <button type="button" class="-mr-1 -mt-1 p-1 text-muted hover:text-ink" @click="$store.cart.remove(item.key)" :aria-label="`Retirer ${item.name}`">
                                <x-icon name="x" class="size-4" />
                            </button>
                        </div>
                        <p class="mt-0.5 text-xs text-muted" x-text="[item.size && `Taille ${item.size}`, item.color].filter(Boolean).join(' · ')"></p>
                        <div class="mt-auto flex items-center justify-between pt-2">
                            <div class="flex h-8 items-center border border-line">
                                <button type="button" class="flex size-8 items-center justify-center disabled:opacity-30" :disabled="item.quantity <= 1"
                                        @click="$store.cart.setQuantity(item.key, item.quantity - 1)" aria-label="Diminuer la quantité">
                                    <x-icon name="minus" class="size-3.5" />
                                </button>
                                <span class="w-7 text-center text-sm tabular-nums" x-text="item.quantity"></span>
                                <button type="button" class="flex size-8 items-center justify-center disabled:opacity-30" :disabled="!$store.cart.canIncrement(item)"
                                        @click="$store.cart.setQuantity(item.key, item.quantity + 1)" aria-label="Augmenter la quantité">
                                    <x-icon name="plus" class="size-3.5" />
                                </button>
                            </div>
                            <span class="text-sm font-semibold tabular-nums" x-text="$store.cart.format(item.price * item.quantity)"></span>
                        </div>
                    </div>
                </li>
            </template>
        </ul>

        <div x-show="$store.cart.items.length > 0" class="border-t border-line p-5">
            <dl class="space-y-1.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-muted">Sous-total</dt>
                    <dd class="tabular-nums" x-text="$store.cart.format($store.cart.subtotal)"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-muted">Livraison <span class="text-xs">(tout le Maroc)</span></dt>
                    <dd class="tabular-nums" :class="!$store.cart.hasDeliveryFee && 'text-muted'" x-text="$store.cart.deliveryLabel"></dd>
                </div>
                <div class="flex items-baseline justify-between border-t border-line pt-2">
                    <dt class="font-medium">Total</dt>
                    <dd class="text-lg font-semibold tabular-nums" x-text="$store.cart.format($store.cart.total)"></dd>
                </div>
            </dl>
            <p x-show="!$store.cart.hasDeliveryFee" class="mt-1 text-xs text-muted">Frais de livraison confirmés avec vous avant l’expédition.</p>
            <a href="{{ route('cart') }}#commande" class="btn btn-primary btn-lg mt-4 w-full">
                <x-icon name="check" class="size-4" /> Finaliser la commande
            </a>
            <button type="button" class="btn btn-ghost btn-sm mt-2 w-full" @click="$store.cart.drawerOpen = false">Continuer mes achats</button>
        </div>
    </aside>
</div>
