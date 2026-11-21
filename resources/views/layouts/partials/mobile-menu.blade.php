<div x-cloak x-show="menuOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu"
     @keydown.escape.window="menuOpen = false">
    <div x-show="menuOpen" x-transition.opacity class="absolute inset-0 bg-ink/50" @click="menuOpen = false"></div>

    <nav x-show="menuOpen"
         x-transition:enter="transition duration-300 ease-(--ease-brand)" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         class="absolute inset-y-0 left-0 flex w-[88%] max-w-sm flex-col bg-paper">
        <div class="flex h-16 items-center justify-between border-b border-line px-4">
            <x-logo class="h-11" />
            <button type="button" class="-mr-2 inline-flex size-10 items-center justify-center" @click="menuOpen = false" aria-label="Fermer le menu">
                <x-icon name="x" class="size-6" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-6">
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('shop.index') }}" class="display flex items-center justify-between py-2 text-[2rem]">
                        Boutique <x-icon name="arrow-right" class="size-5" />
                    </a>
                </li>
                <li>
                    <a href="{{ route('shop.index', ['tri' => 'nouveautes']) }}" class="display flex items-center justify-between py-2 text-[2rem]">
                        Nouveautés <x-icon name="arrow-right" class="size-5" />
                    </a>
                </li>
                <li>
                    <a href="{{ route('shop.index', ['promo' => 1]) }}" class="display flex items-center justify-between py-2 text-[2rem] text-accent">
                        Promotions <x-icon name="arrow-right" class="size-5" />
                    </a>
                </li>
            </ul>

            @if ($navCategories->isNotEmpty())
                <p class="eyebrow mb-3 mt-8">Catégories</p>
                <ul class="divide-y divide-line border-y border-line">
                    @foreach ($navCategories as $cat)
                        <li>
                            <a href="{{ route('shop.category', $cat) }}" class="flex items-center justify-between py-3.5 text-[0.9375rem] font-medium">
                                {{ $cat->name }} <x-icon name="chevron-right" class="size-4 text-muted" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="border-t border-line p-4">
            <a href="{{ route('cart') }}" class="btn btn-primary btn-md w-full">
                <x-icon name="bag" class="size-4" /> Mon panier
            </a>
        </div>
    </nav>
</div>
