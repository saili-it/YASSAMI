{{--
    Visuel produit à ratio constant (4:5). Sans photo, affiche un visuel de
    remplacement sobre teinté par la première couleur du produit.
--}}
@props(['product', 'image' => null, 'eager' => false, 'label' => true])

@php
    $url = $image ?? $product->primary_image_url;
    $tint = $product->color_options[0]['hex'] ?? '#888888';
@endphp

<div {{ $attributes->merge(['class' => 'relative aspect-[4/5] overflow-hidden bg-bone']) }}>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $product->name }}" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
             class="absolute inset-0 size-full object-cover">
    @else
        <div class="absolute inset-0 flex flex-col items-center justify-center"
             style="background: linear-gradient(160deg, color-mix(in oklab, {{ $tint }} 6%, var(--color-bone)), color-mix(in oklab, {{ $tint }} 20%, var(--color-bone)))">
            <span class="font-display text-[clamp(5rem,14vw,9rem)] font-extrabold italic leading-none text-ink/[0.06]" aria-hidden="true">Y</span>
            @if ($label)
                <span class="absolute bottom-3 left-3 text-[0.625rem] font-semibold uppercase tracking-[0.18em] text-ink/40">Photo à venir</span>
            @endif
        </div>
    @endif
</div>
