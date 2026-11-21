{{-- Bouton de suppression avec fenêtre de confirmation (pas de suppression en un clic). --}}
@props(['action', 'title', 'message', 'label' => 'Supprimer', 'compact' => false])

<div x-data="{ open: false }" class="contents">
    @if ($compact)
        <button type="button" @click="open = true" class="inline-flex size-9 items-center justify-center text-muted transition-colors hover:bg-accent/10 hover:text-accent"
                aria-label="{{ $label }}" title="{{ $label }}">
            <x-icon name="trash" class="size-4" />
        </button>
    @else
        <button type="button" @click="open = true" class="btn btn-md border border-line text-accent hover:border-accent hover:bg-accent hover:text-white">
            <x-icon name="trash" class="size-4" /> {{ $label }}
        </button>
    @endif

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[80] flex items-end justify-center p-4 sm:items-center" role="dialog" aria-modal="true"
             @keydown.escape.window="open = false">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/60" @click="open = false"></div>
            <div x-show="open" x-transition class="relative w-full max-w-md bg-paper p-6 shadow-2xl sm:p-8">
                <div class="flex size-11 items-center justify-center bg-accent/10 text-accent">
                    <x-icon name="trash" class="size-5" />
                </div>
                <h2 class="display mt-5 text-3xl">{{ $title }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted">{{ $message }}</p>
                <form method="POST" action="{{ $action }}" class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-md btn-outline" @click="open = false">Annuler</button>
                    <button type="submit" class="btn btn-md btn-accent">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </template>
</div>
