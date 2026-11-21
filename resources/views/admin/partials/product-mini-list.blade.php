@if ($items->isEmpty())
    <p class="px-5 py-10 text-center text-sm text-muted">{{ $empty }}</p>
@else
    <ul class="divide-y divide-line">
        @foreach ($items as $item)
            <li>
                <a href="{{ route('admin.products.edit', $item) }}" class="flex items-center gap-4 px-5 py-3 transition-colors hover:bg-paper">
                    @include('admin.partials.thumb', ['product' => $item, 'size' => 'size-11'])
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $item->name }}</p>
                        <p class="truncate text-xs text-muted">{{ $item->sku }} · {{ $item->category->name }}</p>
                    </div>
                    @include('admin.partials.stock-badge', ['product' => $item])
                </a>
            </li>
        @endforeach
    </ul>
@endif
