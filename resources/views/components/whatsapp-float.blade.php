{{--
    Bouton WhatsApp flottant (toutes les pages de la boutique).
    Numéro : Admin → Paramètres (config('yassami.whatsapp_number')). Sans numéro, rien n'est affiché.
    Le message pré-rempli peut être personnalisé par page (ex. fiche produit) via le layout.
--}}
@props(['message' => null])

@php
    $number = config('yassami.whatsapp_number');
    $text = $message ?: 'Bonjour '.config('yassami.brand').', j’ai une question.';
@endphp

@if ($number)
    <a href="https://wa.me/{{ $number }}?text={{ rawurlencode($text) }}" target="_blank" rel="noopener"
       x-show="!menuOpen && !$store.cart.drawerOpen" x-transition.opacity
       aria-label="Nous écrire sur WhatsApp" data-whatsapp-float
       class="group fixed bottom-[max(1rem,env(safe-area-inset-bottom))] right-4 z-[45] flex items-center gap-3 animate-fade-up sm:bottom-6 sm:right-6">
        <span class="pointer-events-none hidden translate-x-2 bg-ink px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-paper opacity-0 shadow-lg transition duration-200 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100 sm:block">
            Besoin d’aide&nbsp;?
        </span>
        <span class="flex size-14 items-center justify-center bg-whatsapp text-white shadow-lg shadow-ink/25 transition duration-200 group-hover:-translate-y-0.5 group-hover:bg-whatsapp-strong">
            <x-icon name="whatsapp" class="size-7" />
        </span>
    </a>
@endif
