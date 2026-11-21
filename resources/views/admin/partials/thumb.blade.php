@php $url = $product->primary_image_url; @endphp
<span class="{{ $size ?? 'size-12' }} relative flex shrink-0 items-center justify-center overflow-hidden bg-bone">
    @if ($url)
        <img src="{{ $url }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
    @else
        <x-icon name="image" class="size-4 text-muted/60" />
    @endif
</span>
