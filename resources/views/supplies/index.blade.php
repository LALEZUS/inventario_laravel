@extends('layouts.app')
@section('title', 'Consumibles')
@section('page-title', 'Consumibles de impresion')
@section('content')
<section class="page-intro compact">
    <div><p class="eyebrow">Control de existencias</p><h2>Tintas y toner</h2><p>Administra modelos compatibles, cantidades disponibles y alertas de inventario bajo.</p></div>
    <div class="page-actions">
        <x-recent-filter />
        @can('create', \App\Models\Ink::class)<a class="button button-secondary" href="{{ route('inks.create') }}">Nueva tinta</a>@endcan
        @can('create', \App\Models\Toner::class)<a class="button button-primary" href="{{ route('toner.create') }}">Nuevo toner</a>@endcan
    </div>
</section>

<section class="stats-grid compact-grid" aria-label="Indicadores de consumibles">
    <article><span>Tipos de tinta</span><strong>{{ $stats['ink_types'] }}</strong><small>{{ $stats['ink_units'] }} unidades</small></article>
    <article><span>Tipos de toner</span><strong>{{ $stats['toner_types'] }}</strong><small>{{ $stats['toner_units'] }} unidades</small></article>
    <article><span>Existencia baja</span><strong>{{ $stats['low_stock'] }}</strong><small>Según el umbral de cada registro</small></article>
</section>

<section class="panel table-panel">
    <x-module-tabs :active="$type" label="Tipo de consumible" :tabs="[
        'inks' => ['label'=>'Tintas', 'description'=>'Cartuchos, colores y existencias', 'count'=>$stats['ink_types'], 'icon'=>'bi-droplet-half', 'url'=>route('supplies.index',['type'=>'inks'])],
        'toner' => ['label'=>'Toner', 'description'=>'Modelos y niveles disponibles', 'count'=>$stats['toner_types'], 'icon'=>'bi-printer', 'url'=>route('supplies.index',['type'=>'toner'])],
    ]" />
    <form method="GET" class="table-filters supply-filters">
        <input type="hidden" name="type" value="{{ $type }}">
        <label><span>Buscar</span><input name="search" value="{{ $search }}" placeholder="Marca, modelo, color o referencia..."></label>
        <label><span>Estado</span><select name="status"><option value="">Todos</option>@foreach($type === 'inks' ? ['Disponible','Completo','Bajo','Agotado','Vencido'] : ['NUEVO','DISPONIBLE','BAJO','AGOTADO'] as $option)<option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>@endforeach</select></label>
        <button class="button button-secondary">Aplicar</button>
        @if($search !== '' || $status !== '')<a class="button button-secondary" href="{{ route('supplies.index', ['type' => $type]) }}">Limpiar</a>@endif
    </form>
    <div class="table-scroll"><table>
        @if($type === 'inks')
            <thead><tr><th>Tinta</th><th>Modelo</th><th>Color</th><th>Capacidad</th><th>Existencia</th><th>Vencimiento</th><th data-filterable="true">Estado</th><th></th></tr></thead>
            <tbody>@forelse($items as $ink)<tr id="ink-{{ $ink->id }}" class="ink-table-row ink-row-{{ $ink->colorKey() }}"><td><strong>{{ $ink->brand }} {{ $ink->type }}</strong><small>ID {{ $ink->id }}</small></td><td>{{ $ink->model ?: '-' }}</td><td><x-ink-color :ink="$ink" /></td><td>{{ $ink->capacity ?: '-' }}</td><td>@can('update', $ink)<form class="quick-quantity-form" method="POST" action="{{ route('inks.quantity.update', $ink) }}">@csrf @method('PATCH')<label class="sr-only" for="ink-quantity-{{ $ink->id }}">Cantidad de {{ $ink->brand }} {{ $ink->type }}</label><button class="quantity-step" type="button" data-step="-1" title="Disminuir cantidad" aria-label="Disminuir cantidad">−</button><input id="ink-quantity-{{ $ink->id }}" name="quantity" type="number" min="0" max="100000" value="{{ $ink->quantity }}" readonly><button class="quantity-step" type="button" data-step="1" title="Aumentar cantidad" aria-label="Aumentar cantidad">+</button></form>@else<span class="stock-count {{ $ink->quantity <= $ink->low_stock_threshold ? 'low' : '' }}">{{ $ink->quantity }}</span>@endcan</td><td>{{ $ink->dateValue('expiry_date') ?: '-' }}</td><td><span class="status">{{ $ink->status }}</span></td><td><div class="row-actions"><a class="table-action" href="{{ route('inks.show', $ink) }}">Detalles</a>@can('update', $ink)<a class="table-action secondary" href="{{ route('inks.edit', $ink) }}">Editar</a>@endcan</div></td></tr>@empty<tr><td colspan="8" class="empty">No hay tintas para estos filtros.</td></tr>@endforelse</tbody>
        @else
            <thead><tr><th>Toner</th><th>Modelo</th><th>Existencia</th><th data-filterable="true">Estado</th><th>Comentarios</th><th></th></tr></thead>
            <tbody>@forelse($items as $toner)<tr id="toner-{{ $toner->id }}"><td><strong>{{ $toner->brand }}</strong><small>ID {{ $toner->id }}</small></td><td>{{ $toner->model }}</td><td>@can('update', $toner)<form class="quick-quantity-form" method="POST" action="{{ route('toner.quantity.update', $toner) }}">@csrf @method('PATCH')<label class="sr-only" for="toner-quantity-{{ $toner->id }}">Cantidad de {{ $toner->brand }}</label><button class="quantity-step" type="button" data-step="-1" title="Disminuir cantidad" aria-label="Disminuir cantidad">−</button><input id="toner-quantity-{{ $toner->id }}" name="quantity" type="number" min="0" max="100000" value="{{ $toner->quantity }}" readonly><button class="quantity-step" type="button" data-step="1" title="Aumentar cantidad" aria-label="Aumentar cantidad">+</button></form>@else<span class="stock-count {{ $toner->quantity <= $toner->low_stock_threshold ? 'low' : '' }}">{{ $toner->quantity }}</span>@endcan</td><td><span class="status">{{ $toner->status }}</span></td><td class="truncate">{{ $toner->notes ?: '-' }}</td><td><div class="row-actions"><a class="table-action" href="{{ route('toner.show', $toner) }}">Detalles</a>@can('update', $toner)<a class="table-action secondary" href="{{ route('toner.edit', $toner) }}">Editar</a>@endcan</div></td></tr>@empty<tr><td colspan="6" class="empty">No hay toner para estos filtros.</td></tr>@endforelse</tbody>
        @endif
    </table></div>
    @if($items->hasPages())<nav class="pagination">@if($items->onFirstPage())<span>Anterior</span>@else<a href="{{ $items->previousPageUrl() }}">Anterior</a>@endif<strong>Pagina {{ $items->currentPage() }} de {{ $items->lastPage() }}</strong>@if($items->hasMorePages())<a href="{{ $items->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif</nav>@endif
</section>
@push('scripts')
<script>
document.querySelectorAll('.quick-quantity-form').forEach((form) => {
    form.querySelectorAll('.quantity-step').forEach((button) => {
        button.addEventListener('click', async () => {
            const input = form.querySelector('input[name="quantity"]');
            const previous = Number(input.value) || 0;
            const next = Math.max(0, Math.min(100000, previous + Number(button.dataset.step)));
            if (next === previous || form.dataset.saving === '1') return;
            input.value = next;
            form.dataset.saving = '1';
            form.classList.add('is-saving');
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error('No se pudo actualizar');
            } catch (error) {
                input.value = previous;
                window.showInventoryToast?.('No se pudo actualizar la existencia. Intenta nuevamente.', 'error');
            } finally {
                form.dataset.saving = '0';
                form.classList.remove('is-saving');
            }
        });
    });
});
</script>
@endpush
@endsection
