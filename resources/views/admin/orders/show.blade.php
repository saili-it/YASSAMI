@php use App\Enums\OrderStatus; @endphp

<x-layouts.admin :title="'Commande '.$order->reference">
    <x-admin.page-header :title="$order->reference" :back="route('admin.orders.index')"
                         :subtitle="'Passée le '.$order->created_at->translatedFormat('d F Y à H:i')">
        <x-admin.order-status :status="$order->status" class="h-10 px-3 text-xs" />
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Articles --}}
            <section class="bg-white ring-1 ring-line">
                <h2 class="border-b border-line px-5 py-4 text-sm font-semibold uppercase tracking-[0.08em]">
                    Articles <span class="font-normal normal-case tracking-normal text-muted">({{ $order->items_count }})</span>
                </h2>
                <ul class="divide-y divide-line">
                    @foreach ($order->items as $item)
                        <li class="flex items-center gap-4 px-5 py-4">
                            @if ($item->product)
                                <a href="{{ route('admin.products.edit', $item->product) }}" class="shrink-0">
                                    @include('admin.partials.thumb', ['product' => $item->product, 'size' => 'size-14'])
                                </a>
                            @else
                                <span class="flex size-14 shrink-0 items-center justify-center bg-bone" title="Produit supprimé depuis">
                                    <x-icon name="image" class="size-4 text-muted/60" />
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ $item->product_name }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    Réf. {{ $item->sku }}
                                    @if ($item->size) · Taille <strong class="font-semibold text-ink">{{ $item->size }}</strong>@endif
                                    @if ($item->color) · Couleur <strong class="font-semibold text-ink">{{ $item->color }}</strong>@endif
                                </p>
                                <p class="mt-1 text-sm tabular-nums text-muted">{{ $item->quantity }} × {{ mad($item->unit_price) }}</p>
                            </div>
                            <p class="font-semibold tabular-nums">{{ mad($item->line_total) }}</p>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 border-t border-line px-5 py-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">Articles</dt>
                        <dd class="tabular-nums">{{ mad($order->subtotal) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Livraison</dt>
                        <dd class="tabular-nums {{ $order->delivery_fee === null ? 'text-muted' : '' }}">
                            {{ $order->delivery_fee === null ? 'À confirmer' : mad($order->delivery_fee) }}
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-line pt-3">
                        <dt class="font-semibold">Total</dt>
                        <dd class="text-xl font-semibold tabular-nums">{{ mad($order->total) }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Client --}}
            <section class="bg-white p-5 ring-1 ring-line sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Client</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-muted">Nom</dt><dd class="mt-0.5 font-medium">{{ $order->customer_name }}</dd></div>
                    <div><dt class="text-xs text-muted">Téléphone</dt><dd class="mt-0.5 font-medium tabular-nums">{{ $order->phone_display }}</dd></div>
                    <div><dt class="text-xs text-muted">Ville</dt><dd class="mt-0.5 font-medium">{{ $order->customer_city }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-muted">Adresse de livraison</dt><dd class="mt-0.5 whitespace-pre-line font-medium">{{ $order->customer_address }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="tel:{{ $order->customer_phone }}" class="btn btn-md btn-outline"><x-icon name="phone" class="size-4" /> Appeler</a>
                    @if ($order->whatsapp_url)
                        <a href="{{ $order->whatsapp_url }}?text={{ rawurlencode('Bonjour '.$order->customer_name.', nous vous contactons au sujet de votre commande '.$order->reference.' sur '.config('yassami.brand').'.') }}"
                           target="_blank" rel="noopener" class="btn btn-md btn-whatsapp"><x-icon name="whatsapp" class="size-4" /> Écrire sur WhatsApp</a>
                    @endif
                </div>
            </section>
        </div>

        {{-- Traitement --}}
        <aside>
            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-5 bg-white p-5 ring-1 ring-line sm:p-6 lg:sticky lg:top-6" novalidate>
                @csrf
                @method('PUT')
                <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Traitement</h2>

                <div>
                    <label for="status" class="field-label">Statut</label>
                    <select id="status" name="status" @class(['field', 'field-invalid' => $errors->has('status')])>
                        @foreach (OrderStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('status', $order->status->value) === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-muted">Annuler la commande remet les articles en stock.</p>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="delivery_fee" class="field-label">Frais de livraison</label>
                    <div class="flex items-stretch">
                        <input id="delivery_fee" name="delivery_fee" type="text" inputmode="decimal" maxlength="10"
                               value="{{ old('delivery_fee', $order->delivery_fee !== null ? rtrim(rtrim($order->delivery_fee, '0'), '.') : '') }}"
                               placeholder="À confirmer" @class(['field rounded-r-none', 'field-invalid' => $errors->has('delivery_fee')])>
                        <span class="flex items-center border border-l-0 border-line bg-bone px-3 text-sm text-muted">{{ config('yassami.currency') }}</span>
                    </div>
                    <p class="mt-1.5 text-xs text-muted">Une fois convenus avec le client. Laissez vide si ce n’est pas encore fait.</p>
                    @error('delivery_fee')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="admin_note" class="field-label">Note interne</label>
                    <textarea id="admin_note" name="admin_note" rows="4" maxlength="2000" placeholder="Ex. client rappelé, livraison prévue jeudi…"
                              @class(['field', 'field-invalid' => $errors->has('admin_note')])>{{ old('admin_note', $order->admin_note) }}</textarea>
                    <p class="mt-1.5 text-xs text-muted">Visible uniquement dans l’administration.</p>
                    @error('admin_note')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn btn-lg btn-primary w-full"><x-icon name="check" class="size-4" /> Enregistrer</button>
                <p class="text-xs text-muted">Dernière mise à jour : {{ $order->updated_at->translatedFormat('d M Y à H:i') }}</p>
            </form>
        </aside>
    </div>
</x-layouts.admin>
