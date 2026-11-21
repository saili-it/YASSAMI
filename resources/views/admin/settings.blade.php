@php
    $whatsapp = $values['whatsapp_number'];
    // Affichage lisible du numéro enregistré : 212612345678 → +212 6 12 34 56 78
    $whatsappDisplay = preg_match('/^212(\d)(\d{2})(\d{2})(\d{2})(\d{2})$/', $whatsapp, $m)
        ? "+212 {$m[1]} {$m[2]} {$m[3]} {$m[4]} {$m[5]}"
        : $whatsapp;

    $fieldsContact = [
        ['key' => 'contact_phone', 'label' => 'Téléphone', 'type' => 'tel', 'icon' => 'phone', 'placeholder' => 'Ex. 05 22 00 00 00', 'max' => 30],
        ['key' => 'contact_email', 'label' => 'E-mail', 'type' => 'email', 'icon' => 'mail', 'placeholder' => 'Ex. contact@votre-domaine.ma', 'max' => 150],
        ['key' => 'contact_address', 'label' => 'Adresse', 'type' => 'text', 'icon' => 'map-pin', 'placeholder' => 'Ex. Showroom, quartier, ville', 'max' => 255],
    ];
    $fieldsSocial = [
        ['key' => 'instagram_url', 'label' => 'Instagram', 'icon' => 'instagram', 'placeholder' => 'https://www.instagram.com/…'],
        ['key' => 'facebook_url', 'label' => 'Facebook', 'icon' => 'facebook', 'placeholder' => 'https://www.facebook.com/…'],
        ['key' => 'tiktok_url', 'label' => 'TikTok', 'icon' => 'tiktok', 'placeholder' => 'https://www.tiktok.com/@…'],
    ];
@endphp

<x-layouts.admin title="Paramètres">
    <x-admin.page-header title="Paramètres" subtitle="Livraison, coordonnées et réseaux sociaux affichés sur la boutique." />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="grid gap-6 lg:grid-cols-3" novalidate>
        @csrf
        @method('PUT')

        <div class="space-y-6 lg:col-span-2">
            {{-- Livraison --}}
            @php
                $fee = $values['delivery_fee'];
                $feeDisplay = is_numeric($fee) ? rtrim(rtrim(number_format((float) $fee, 2, ',', ''), '0'), ',') : '';
            @endphp
            <section class="bg-white p-5 ring-1 ring-line sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Livraison</h2>
                        <p class="mt-1 text-xs text-muted">Prix unique pour tout le Maroc, ajouté au total de chaque commande.</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 bg-bone px-2.5 py-1 text-xs font-semibold">
                        <x-icon name="truck" class="size-3.5" /> Actuellement : {{ \App\Support\Delivery::label(is_numeric($fee) ? $fee : null) }}
                    </span>
                </div>

                <div class="mt-5 max-w-xs">
                    <label for="delivery_fee" class="field-label">Frais de livraison</label>
                    <div class="flex items-stretch">
                        <input id="delivery_fee" name="delivery_fee" type="text" inputmode="decimal" maxlength="10"
                               value="{{ old('delivery_fee', $feeDisplay) }}" placeholder="Ex. 35"
                               @class(['field rounded-r-none', 'field-invalid' => $errors->has('delivery_fee')])>
                        <span class="flex items-center border border-l-0 border-line bg-bone px-3 text-sm text-muted">{{ config('yassami.currency') }}</span>
                    </div>
                    @error('delivery_fee')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <ul class="mt-3 space-y-1 text-xs text-muted">
                    <li><strong class="font-semibold text-ink">0</strong> : livraison gratuite.</li>
                    <li><strong class="font-semibold text-ink">Vide</strong> : frais « à confirmer » avec chaque client.</li>
                    <li>Le prix est enregistré sur chaque commande au moment où elle est passée : le modifier ne change pas les commandes existantes (ajustables une par une dans Commandes).</li>
                </ul>
            </section>

            {{-- Commande WhatsApp --}}
            <section class="bg-white p-5 ring-1 ring-line sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Contact WhatsApp</h2>
                <p class="mt-1 text-xs text-muted">Optionnel : affiche un lien « Nous écrire sur WhatsApp » dans le pied de page. Les commandes, elles, arrivent dans l’onglet Commandes.</p>

                <div class="mt-5">
                    <label for="whatsapp_number" class="field-label">Numéro WhatsApp</label>
                    <div class="relative">
                        <x-icon name="whatsapp" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-whatsapp" />
                        <input id="whatsapp_number" name="whatsapp_number" type="tel" inputmode="tel" autocomplete="off" maxlength="25"
                               value="{{ old('whatsapp_number', $whatsappDisplay) }}" placeholder="06 12 34 56 78"
                               @class(['field pl-9', 'field-invalid' => $errors->has('whatsapp_number')])>
                    </div>
                    <p class="mt-1.5 text-xs text-muted">Numéro marocain, saisi comme vous voulez : 06 12 34 56 78, +212 6 12 34 56 78… Laissez vide pour masquer le lien.</p>
                    @error('whatsapp_number')<p class="field-error">{{ $message }}</p>@enderror

                    @if ($whatsapp)
                        <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Test depuis l’administration '.config('yassami.brand')) }}"
                           target="_blank" rel="noopener" class="btn btn-sm btn-outline mt-4">
                            <x-icon name="external" class="size-4" /> Tester le numéro sur WhatsApp
                        </a>
                    @endif
                </div>
            </section>

            {{-- Coordonnées --}}
            <section class="bg-white p-5 ring-1 ring-line sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Coordonnées</h2>
                <p class="mt-1 text-xs text-muted">Affichées dans le pied de page. Un champ vide n’apparaît pas sur le site.</p>

                <div class="mt-5 space-y-5">
                    @foreach ($fieldsContact as $f)
                        <div>
                            <label for="{{ $f['key'] }}" class="field-label">{{ $f['label'] }}</label>
                            <div class="relative">
                                <x-icon :name="$f['icon']" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                                <input id="{{ $f['key'] }}" name="{{ $f['key'] }}" type="{{ $f['type'] }}" maxlength="{{ $f['max'] }}"
                                       value="{{ old($f['key'], $values[$f['key']]) }}" placeholder="{{ $f['placeholder'] }}"
                                       @class(['field pl-9', 'field-invalid' => $errors->has($f['key'])])>
                            </div>
                            @error($f['key'])<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Réseaux sociaux --}}
            <section class="bg-white p-5 ring-1 ring-line sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-[0.08em]">Réseaux sociaux</h2>
                <p class="mt-1 text-xs text-muted">Collez l’adresse complète de vos pages. Seuls les réseaux renseignés s’affichent.</p>

                <div class="mt-5 space-y-5">
                    @foreach ($fieldsSocial as $f)
                        <div>
                            <label for="{{ $f['key'] }}" class="field-label">{{ $f['label'] }}</label>
                            <div class="relative">
                                <x-icon :name="$f['icon']" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                                <input id="{{ $f['key'] }}" name="{{ $f['key'] }}" type="url" maxlength="255" inputmode="url"
                                       value="{{ old($f['key'], $values[$f['key']]) }}" placeholder="{{ $f['placeholder'] }}"
                                       @class(['field pl-9', 'field-invalid' => $errors->has($f['key'])])>
                            </div>
                            @error($f['key'])<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="bg-white p-5 text-sm leading-relaxed text-muted ring-1 ring-line sm:p-6">
                <h2 class="mb-2 text-sm font-semibold uppercase tracking-[0.08em] text-ink">Bon à savoir</h2>
                <p>Les changements s’appliquent immédiatement sur la boutique, sans redémarrage.</p>
                <p class="mt-2">Les commandes des clients sont enregistrées sur le site : retrouvez-les dans <a href="{{ route('admin.orders.index') }}" class="font-semibold text-ink underline">Commandes</a>.</p>
            </section>

            <button type="submit" class="btn btn-lg btn-primary w-full">
                <x-icon name="check" class="size-4" /> Enregistrer
            </button>
        </aside>
    </form>
</x-layouts.admin>
