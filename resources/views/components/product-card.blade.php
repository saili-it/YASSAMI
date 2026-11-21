@props(['product', 'eager' => false])

@php $secondImage = $product->images->get(1)?->url; @endphp

<article {{ $attributes->merge(['class' => 'group relative']) }}>
    <a href="{{ route('products.show', $product) }}" class="block focus-visible:outline-offset-4">
        <div class="relative overflow-hidden">
            <x-product-media :product="$product" :eager="$eager"
                             class="transition-transform duration-700 ease-(--ease-brand) group-hover:scale-[1.03]" />

            @if ($secondImage)
                <img src="{{ $secondImage }}" alt="" loading="lazy" aria-hidden="true"
                     class="absolute inset-0 size-full object-cover opacity-0 transition-opacity duration-500 group-hover:opacity-100">
            @endif

            <div class="absolute left-2 top-2 flex flex-col items-start gap-1">
                @if (! $product->in_stock)
                    <span class="bg-ink px-2 py-1 text-[0.625rem] font-bold uppercase tracking-[0.12em] text-paper">Rupture</span>
                @elseif ($product->has_discount)
                    <span class="bg-accent px-2 py-1 text-[0.625rem] font-bold uppercase tracking-[0.12em] text-white">−{{ $product->discount_percent }}%</span>
                @endif
            </div>
        </div>

        <div class="flex items-start justify-between gap-3 pt-3">
            <div class="min-w-0">
                <h3 class="truncate text-sm font-medium sm:text-[0.9375rem]">{{ $product->name }}</h3>
                <p class="mt-0.5 truncate text-xs text-muted">{{ $product->category->name }}</p>
            </div>

            @if (count($product->color_options) > 1)
                <ul class="mt-1 flex shrink-0 -space-x-1" aria-label="{{ count($product->color_options) }} couleurs">
                    @foreach (array_slice($product->color_options, 0, 3) as $color)
                        <li class="size-3 rounded-full ring-2 ring-paper" style="background-color: {{ $color['hex'] }}" title="{{ $color['name'] }}"></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <x-price :product="$product" class="mt-1.5" />
    </a>
</article>
