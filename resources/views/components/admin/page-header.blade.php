@props(['title', 'subtitle' => null, 'back' => null])

<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if ($back)
            <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-[0.12em] text-muted hover:text-ink">
                <x-icon name="chevron-left" class="size-4" /> Retour
            </a>
        @endif
        <h1 class="display text-[2.5rem] sm:text-5xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
