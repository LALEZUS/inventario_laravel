@extends('layouts.app')
@section('title','Busqueda global') @section('page-title','Busqueda global')
@section('content')
@php
    $typeIcons = [
        'Computadora' => 'bi-pc-display',
        'Celular' => 'bi-phone',
        'Periferico' => 'bi-mouse2',
        'Impresora' => 'bi-printer',
        'Tinta' => 'bi-droplet',
        'Toner' => 'bi-printer-fill',
        'Dispositivo de red' => 'bi-router',
        'WatchGuard' => 'bi-shield-check',
        'Red empresarial' => 'bi-wifi',
        'Empleado' => 'bi-person-vcard',
        'Licencia' => 'bi-key',
        'Correo Outlook' => 'bi-envelope-at',
        'Microsoft 365' => 'bi-microsoft',
        'Correo Windows' => 'bi-envelope',
        'Reenvio' => 'bi-forward',
        'Tutorial' => 'bi-journal-text',
        'Galeria' => 'bi-images',
        'Archivo' => 'bi-folder2-open',
        'Nota' => 'bi-journal-bookmark',
        'Recordatorio' => 'bi-calendar-event',
    ];
@endphp

<section class="search-hero">
    <div class="search-hero-heading">
        <span class="search-hero-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
        <div>
            <p class="eyebrow">Buscador global</p>
            <h2>Encuentra cualquier registro</h2>
            <p>Equipos, personas, folios, correos, telefonos y servicios en un solo lugar.</p>
        </div>
    </div>
    <form method="GET" action="{{ route('search.index') }}" class="search-page-form" role="search">
        <div class="search-page-input">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input name="q" value="{{ $query }}" minlength="2" maxlength="100" autofocus placeholder="Escribe lo que necesitas encontrar..." aria-label="Buscar en todo el inventario">
            @if($query !== '')
                <a class="search-clear" href="{{ route('search.index') }}" title="Limpiar busqueda" aria-label="Limpiar busqueda"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
            @endif
        </div>
        <button class="button button-primary" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span>Buscar</span></button>
    </form>
</section>

@if(mb_strlen($query) >= 2)
    <section class="search-results-panel">
        <header class="search-results-header">
            <div class="search-summary">
                <span class="search-summary-count" id="search-visible-count">{{ $results->count() }} resultados</span>
                <div><strong>encontrados</strong><span> para "{{ $query }}"</span></div>
            </div>
            @if($results->isNotEmpty())
                <div class="search-filter-row" aria-label="Filtrar resultados por categoria">
                    <button class="search-filter is-active" type="button" data-search-filter="all" aria-pressed="true">Todos <span>{{ $results->count() }}</span></button>
                    @foreach($typeCounts as $type => $count)
                        <button class="search-filter" type="button" data-search-filter="{{ $type }}" aria-pressed="false">{{ $type }} <span>{{ $count }}</span></button>
                    @endforeach
                </div>
            @endif
        </header>

        <div class="search-results-grid" id="search-results-grid">
            @forelse($results as $result)
                <a class="search-result-card" href="{{ $result['url'] }}" data-search-result data-result-type="{{ $result['type'] }}">
                    <span class="search-result-icon"><i class="bi {{ $typeIcons[$result['type']] ?? 'bi-search' }}" aria-hidden="true"></i></span>
                    <div class="search-result-copy">
                        <small><span>{{ $result['type'] }}</span></small>
                        <h3>{{ $result['title'] }}</h3>
                        <p>{{ $result['subtitle'] ?: 'Sin informacion adicional' }}</p>
                        @if($result['meta'])<em>{{ $result['meta'] }}</em>@endif
                    </div>
                    <b aria-hidden="true"><i class="bi bi-chevron-right"></i></b>
                </a>
            @empty
                <div class="search-empty"><i class="bi bi-search" aria-hidden="true"></i><strong>No encontramos coincidencias</strong><p>Prueba con un nombre, folio, serie, correo o telefono diferente.</p></div>
            @endforelse
        </div>

        <div class="search-empty search-filter-empty" id="search-filter-empty" hidden><i class="bi bi-funnel" aria-hidden="true"></i><strong>No hay resultados en esta categoria</strong><p>Selecciona otra categoria para continuar.</p></div>
    </section>
@else
    <section class="search-start-state">
        <div class="search-start-icon"><i class="bi bi-command" aria-hidden="true"></i></div>
        <div><strong>Empieza con cualquier dato</strong><p>Nombre del equipo, empleado, serie, folio, correo, telefono o direccion IP.</p></div>
    </section>
@endif
@endsection

@push('scripts')
<script>
(() => {
    const filters = [...document.querySelectorAll('[data-search-filter]')];
    const results = [...document.querySelectorAll('[data-search-result]')];
    const visibleCount = document.getElementById('search-visible-count');
    const emptyState = document.getElementById('search-filter-empty');

    if (!filters.length || !results.length) return;

    filters.forEach((filter) => {
        filter.addEventListener('click', () => {
            const selectedType = filter.dataset.searchFilter;
            let visible = 0;

            filters.forEach((item) => {
                const active = item === filter;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            results.forEach((result) => {
                const show = selectedType === 'all' || result.dataset.resultType === selectedType;
                result.hidden = !show;
                if (show) visible += 1;
            });

            if (visibleCount) visibleCount.textContent = `${visible} resultados`;
            if (emptyState) emptyState.hidden = visible !== 0;
        });
    });
})();
</script>
@endpush
