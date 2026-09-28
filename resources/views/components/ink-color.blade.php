@props(['ink', 'compact' => false])
<span {{ $attributes->class(['ink-color-chip', 'compact' => $compact, 'ink-color-'.$ink->colorKey()]) }}>
    <i aria-hidden="true"></i>
    <span>{{ $ink->color ?: 'Sin color' }}</span>
</span>
