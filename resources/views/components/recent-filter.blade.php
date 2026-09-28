@php
    $mode = request()->query('recent');
    $labels = [
        'created' => 'Último agregado',
        'updated' => 'Último actualizado',
    ];
    $urlFor = static function (?string $value): string {
        $query = request()->except(['recent', 'page']);
        if ($value !== null) {
            $query['recent'] = $value;
        }

        return url()->current().($query === [] ? '' : '?'.\Illuminate\Support\Arr::query($query));
    };
@endphp

<details class="recent-filter">
    <summary class="button button-secondary {{ isset($labels[$mode]) ? 'is-active' : '' }}">
        <i class="bi bi-clock-history" aria-hidden="true"></i>
        <span>{{ $labels[$mode] ?? 'Recientes' }}</span>
        <i class="bi bi-chevron-down recent-filter-chevron" aria-hidden="true"></i>
    </summary>
    <nav class="recent-filter-menu" aria-label="Ordenar registros recientes">
        <a href="{{ $urlFor('created') }}" class="{{ $mode === 'created' ? 'is-active' : '' }}">
            <i class="bi bi-plus-circle" aria-hidden="true"></i>
            <span><strong>Último agregado</strong><small>Ordenar por fecha de creación</small></span>
            @if ($mode === 'created')<i class="bi bi-check-lg" aria-hidden="true"></i>@endif
        </a>
        <a href="{{ $urlFor('updated') }}" class="{{ $mode === 'updated' ? 'is-active' : '' }}">
            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
            <span><strong>Último actualizado</strong><small>Ordenar por la modificación más reciente</small></span>
            @if ($mode === 'updated')<i class="bi bi-check-lg" aria-hidden="true"></i>@endif
        </a>
        @if (isset($labels[$mode]))
            <a href="{{ $urlFor(null) }}" class="recent-filter-default">
                <i class="bi bi-sort-alpha-down" aria-hidden="true"></i>
                <span><strong>Orden predeterminado</strong><small>Volver al orden normal</small></span>
            </a>
        @endif
    </nav>
</details>
