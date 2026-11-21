{{-- Interrupteur on/off. Le champ caché garantit l'envoi de « 0 » quand il est décoché. --}}
@props(['name', 'label', 'checked' => false, 'hint' => null])

<label class="flex cursor-pointer items-start justify-between gap-4">
    <span>
        <span class="block text-sm font-semibold">{{ $label }}</span>
        @if ($hint)
            <span class="mt-0.5 block text-xs leading-relaxed text-muted">{{ $hint }}</span>
        @endif
    </span>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked($checked) class="peer sr-only">
    <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-line transition-colors
                 after:absolute after:left-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform
                 peer-checked:bg-ink peer-checked:after:translate-x-5 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-ink"
          aria-hidden="true"></span>
</label>
