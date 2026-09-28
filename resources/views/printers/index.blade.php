@extends('layouts.app')
@section('title', 'Impresoras')
@section('page-title', 'Impresoras')
@section('content')
<section class="page-intro compact">
    <div>
        <p class="eyebrow">Impresion y consumibles</p>
        <h2>{{ $printers->total() }} impresoras encontradas</h2>
        <p>Consulta conectividad, responsables, ubicaciones y suministros asociados.</p>
    </div>
    <div class="page-actions">
        <x-recent-filter />
        @can('create', \App\Models\Printer::class)
            <a class="button button-primary" href="{{ route('printers.create') }}">Nueva impresora</a>
        @endcan
    </div>
</section>

<section class="stats-grid compact-grid" aria-label="Indicadores de impresoras">
    <article><span>Total</span><strong>{{ $stats['total'] }}</strong><small>Impresoras registradas</small></article>
    <article><span>En red</span><strong>{{ $stats['network'] }}</strong><small>Conectadas por direccion IP</small></article>
    <article><span>Mantenimiento</span><strong>{{ $stats['maintenance'] }}</strong><small>Requieren seguimiento</small></article>
</section>

<section class="panel table-panel">
    <form method="GET" class="table-filters">
        <label><span>Buscar</span><input name="search" value="{{ $search }}" placeholder="Nombre, modelo, IP, usuario..."></label>
        <label><span>Estado</span><select name="status"><option value="">Todos</option>@foreach(['Activo','Disponible','En servicio','Mantenimiento','Baja'] as $option)<option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>@endforeach</select></label>
        <label><span>Conexion</span><select name="network"><option value="">Todas</option><option value="1" @selected($network === '1')>En red</option><option value="0" @selected($network === '0')>Local</option></select></label>
        <button class="button button-secondary">Aplicar</button>
        @if($search !== '' || $status !== '' || in_array($network, ['0','1'], true))<a class="button button-secondary" href="{{ route('printers.index') }}">Limpiar</a>@endif
    </form>
    <div class="table-scroll"><table><thead><tr><th>Impresora</th><th>Modelo</th><th>Conexion</th><th>Zona</th><th>Asignada a</th><th>Suministro</th><th data-filterable="true">Estado</th><th></th></tr></thead><tbody>
    @forelse($printers as $printer)
        <tr>
            <td><strong>{{ $printer->name }}</strong><small>{{ $printer->code ?: 'Sin folio' }} &middot; {{ $printer->serial ?: 'Sin serie' }}</small></td>
            <td>{{ $printer->brand ?: '-' }}<small>{{ $printer->model ?: 'Sin modelo' }}</small></td>
            <td>@if($printer->is_network)<span class="status">Red</span><small>{{ $printer->ip_address ?: 'IP pendiente' }}</small>@else Local @endif</td>
            <td>{{ $printer->zone ?: '-' }}</td>
            <td>{{ $printer->assigned_name ?: 'Sin asignar' }}</td>
            <td>{{ $printer->supply_type ?: '-' }}<small>{{ $printer->ink_type }}</small></td>
            <td><span class="status">{{ $printer->status }}</span></td>
            <td><div class="row-actions"><a class="table-action" href="{{ route('printers.show', $printer) }}">Detalles</a>@can('update', $printer)<a class="table-action secondary" href="{{ route('printers.edit', $printer) }}">Editar</a>@endcan</div></td>
        </tr>
    @empty
        <tr><td colspan="8" class="empty">No hay impresoras para estos filtros.</td></tr>
    @endforelse
    </tbody></table></div>
    @if($printers->hasPages())<nav class="pagination">@if($printers->onFirstPage())<span>Anterior</span>@else<a href="{{ $printers->previousPageUrl() }}">Anterior</a>@endif<strong>Pagina {{ $printers->currentPage() }} de {{ $printers->lastPage() }}</strong>@if($printers->hasMorePages())<a href="{{ $printers->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif</nav>@endif
</section>
@endsection
