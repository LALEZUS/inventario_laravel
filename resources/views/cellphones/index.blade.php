@extends('layouts.app')
@section('title', 'Celulares')
@section('page-title', 'Celulares')
@section('content')
<section class="page-intro compact">
    <div><p class="eyebrow">Activos moviles</p><h2>{{ $cellphones->total() }} celulares encontrados</h2><p>Lineas, cuentas y controles de acceso en una sola vista.</p></div>
    <div class="page-actions">
        <form method="GET" class="search-form"><label class="sr-only" for="search">Buscar celulares</label><input id="search" name="search" value="{{ $search }}" placeholder="Empleado, modelo, correo, telefono..."><button class="button button-secondary">Buscar</button>@if($search !== '')<a class="button button-secondary" href="{{ route('cellphones.index') }}">Limpiar</a>@endif</form>
        <x-recent-filter />
        @can('create', \App\Models\Cellphone::class)<a class="button button-primary" href="{{ route('cellphones.create') }}">Nuevo celular</a>@endcan
    </div>
</section>
<section class="panel table-panel">
    <div class="table-scroll"><table><thead><tr><th>Empleado</th><th>Modelo</th><th>Area</th><th>Telefono</th><th>Correo</th><th data-filterable="true">Estado</th><th></th></tr></thead>
    <tbody>@forelse($cellphones as $cellphone)<tr>
        <td><strong>{{ $cellphone->assigned_name ?: 'Sin asignar' }}</strong></td><td>{{ $cellphone->model ?: '-' }}</td><td>{{ $cellphone->area ?: '-' }}</td><td>{{ $cellphone->phone_number ?: '-' }}</td><td class="truncate">{{ $cellphone->email_account ?: '-' }}</td><td><span class="status">{{ $cellphone->status }}</span></td>
        <td><div class="row-actions"><a class="table-action" href="{{ route('cellphones.show', $cellphone) }}">Detalles</a>@can('shareSensitive',$cellphone)<a class="table-action whatsapp" target="_blank" rel="noopener" href="{{ route('cellphones.share',$cellphone) }}">WhatsApp</a>@endcan @can('update',$cellphone)<a class="table-action secondary" href="{{ route('cellphones.edit',$cellphone) }}">Editar</a>@endcan</div></td>
    </tr>@empty<tr><td colspan="7" class="empty">No hay resultados para esta busqueda.</td></tr>@endforelse</tbody></table></div>
    @if($cellphones->hasPages())<nav class="pagination">@if($cellphones->onFirstPage())<span>Anterior</span>@else<a href="{{ $cellphones->previousPageUrl() }}">Anterior</a>@endif<strong>Pagina {{ $cellphones->currentPage() }} de {{ $cellphones->lastPage() }}</strong>@if($cellphones->hasMorePages())<a href="{{ $cellphones->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif</nav>@endif
</section>
@endsection
