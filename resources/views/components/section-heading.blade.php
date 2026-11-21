@props(['eyebrow' => null, 'title', 'href' => null, 'linkLabel' => 'Tout voir'])

<div {{ $attributes->merge(['class' => 'mb-6 flex items-end justify-between gap-6 sm:mb-8']) }}>
    <div>
        @if ($eyebrow)
            <p class="eyebrow mb-2">{{ $eyebrow }}</p>
        @endif
        <h2 class="display text-[2.25rem] sm:text-5xl">{{ $title }}</h2>
    </div>

    @if ($href)
        <a href="{{ $href }}" class="group mb-1 inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.12em]">
            <span class="link-underline">{{ $linkLabel }}</span>
            <x-icon name="arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" />
        </a>
    @endif
</div>
