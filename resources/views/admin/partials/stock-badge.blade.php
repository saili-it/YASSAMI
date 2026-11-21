@if ($product->stock_quantity === 0)
    <span class="inline-flex shrink-0 items-center bg-accent/10 px-2 py-1 text-xs font-semibold text-accent">Rupture</span>
@elseif ($product->is_low_stock)
    <span class="inline-flex shrink-0 items-center bg-warning/10 px-2 py-1 text-xs font-semibold tabular-nums text-warning">{{ $product->stock_quantity }} en stock</span>
@else
    <span class="inline-flex shrink-0 items-center bg-success/10 px-2 py-1 text-xs font-semibold tabular-nums text-success">{{ $product->stock_quantity }} en stock</span>
@endif
