@props(['status'])
<span {{ $attributes->class(['inline-flex shrink-0 items-center px-2 py-0.5 text-[0.6875rem] font-bold uppercase tracking-[0.08em]', $status->badgeClasses()]) }}>
    {{ $status->label() }}
</span>
