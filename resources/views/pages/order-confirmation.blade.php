@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

<x-layouts.app :title="'Commande '.$order->reference" description="Confirmation de votre commande.">
    {{-- Sécurité : le panier est déjà vidé par le formulaire, on s'en assure ici aussi. --}}
    <div class="container-brand max-w-3xl pt-10 sm:pt-16" x-data x-init="$store.cart.clear()">
        <div class="flex flex-col items-center text-center animate-fade-up">
            <span class="flex size-16 items-center justify-center bg-ink text-paper">
                <x-icon name="check" class="size-8" />
            </span>
            <p class="eyebrow mt-6">Commande enregistrée</p>
            <h1 class="display mt-2 text-[clamp(2.5rem,8vw,4.5rem)]">Merci {{ \Illuminate\Support\Str::before($order->customer_name, ' ') }}&nbsp;!</h1>
            <p class="mt-3 text-sm text-muted">Votre numéro de commande</p>
            <p class="mt-1 font-display text-3xl font-extrabold italic tracking-wide tabular-nums">{{ $order->reference }}</p>
        </div>

        <div class="mt-10 flex gap-4 bg-white p-5 ring-1 ring-line sm:p-6">
            <x-icon name="phone" class="mt-0.5 size-5 shrink-0" />
            <div class="text-sm leading-relaxed">
                <p class="font-semibold">Prochaine étape</p>
                <p class="mt-1 text-muted">
                    Notre équipe vous contacte au <strong class="font-semibold text-ink tabular-nums">{{ $order->phone_display }}</strong>
                    pour confirmer votre commande{{ $order->delivery_fee === null ? ' et les frais de livraison' : '' }}. Aucun paiement n’a été effectué en ligne.
                </p>
            </div>
        </div>

        <section class="mt-6 bg-white ring-1 ring-line" aria-labelledby="recap-title">
            <h2 id="recap-title" class="border-b border-line px-5 py-4 text-sm font-semibold uppercase tracking-[0.08em] sm:px-6">Récapitulatif</h2>
            <ul class="divide-y divide-line">
                @foreach ($order->items as $item)
                    <li class="flex items-start justify-between gap-4 px-5 py-4 sm:px-6">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $item->product_name }}</p>
                            <p class="mt-0.5 text-sm text-muted">
                                {{ collect([$item->size ? 'Taille '.$item->size : null, $item->color ? 'Couleur '.$item->color : null])->filter()->join(' · ') }}
                                @if ($item->size || $item->color) · @endif{{ $item->quantity }} × {{ mad($item->unit_price) }}
                            </p>
                        </div>
                        <p class="shrink-0 font-semibold tabular-nums">{{ mad($item->line_total) }}</p>
                    </li>
                @endforeach
            </ul>
            <dl class="space-y-2 border-t border-line px-5 py-4 text-sm sm:px-6">
                <div class="flex justify-between">
                    <dt class="text-muted">Articles</dt>
                    <dd class="tabular-nums">{{ mad($order->subtotal) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-muted">Livraison</dt>
                    <dd @class(['tabular-nums', 'text-muted' => $order->delivery_fee === null])>
                        {{ $order->delivery_fee === null ? 'À confirmer avec vous' : \App\Support\Delivery::label($order->delivery_fee) }}
                    </dd>
                </div>
                <div class="flex items-baseline justify-between border-t border-line pt-3">
                    <dt class="font-semibold">{{ $order->delivery_fee === null ? 'Total articles' : 'Total' }}</dt>
                    <dd class="text-xl font-semibold tabular-nums">{{ mad($order->total) }}</dd>
                </div>
            </dl>
            <div class="border-t border-line px-5 py-4 text-sm sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-muted">Livraison à</p>
                <p class="mt-1">{{ $order->customer_name }}<br>{{ $order->customer_address }}<br>{{ $order->customer_city }}</p>
            </div>
        </section>

        <div class="mt-8 flex justify-center">
            <a href="{{ route('shop.index') }}" class="btn btn-primary btn-lg">Continuer mes achats <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </div>
</x-layouts.app>
