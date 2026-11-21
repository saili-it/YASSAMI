{{-- Messages de retour après une action (succès / erreur) --}}
@foreach (['status' => ['check', 'border-success/30 bg-success/10 text-success'], 'error' => ['alert', 'border-accent/30 bg-accent/10 text-accent-strong']] as $key => [$icon, $classes])
    @if (session($key))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition.opacity
             role="{{ $key === 'error' ? 'alert' : 'status' }}"
             class="mb-6 flex items-start gap-3 border px-4 py-3 text-sm font-medium {{ $classes }}">
            <x-icon :name="$icon" class="mt-0.5 size-4" />
            <p class="flex-1">{{ session($key) }}</p>
            <button type="button" @click="show = false" class="-m-1 p-1 opacity-60 hover:opacity-100" aria-label="Fermer">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    @endif
@endforeach
