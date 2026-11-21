@props(['product', 'size' => 'md'])

@php
    $main = match ($size) {
        'lg' => 'text-2xl sm:text-[1.75rem] font-semibold',
        'sm' => 'text-sm font-semibold',
        default => 'text-[0.9375rem] font-semibold',
    };
    $old = $size === 'lg' ? 'text-base' : 'text-[0.8125rem]';
@endphp

<p {{ $attributes->merge(['class' => 'flex flex-wrap items-baseline gap-x-2']) }}>
    @if ($product->has_discount)
        <span class="{{ $main }} text-accent">{{ mad($product->sale_price) }}</span>
        <s class="{{ $old }} text-muted" aria-label="Prix initial">{{ mad($product->price) }}</s>
    @else
        <span class="{{ $main }}">{{ mad($product->price) }}</span>
    @endif
</p>
