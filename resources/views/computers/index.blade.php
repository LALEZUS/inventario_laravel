@extends('layouts.app')

@section('title', 'Computadoras')
@section('page-title', 'Computadoras')

@section('content')
    <section class="page-intro compact">
        <div>
            <p class="eyebrow">Activos de hardware</p>
            <h2>{{ $computers->total() }} computadoras encontradas</h2>
        </div>
        <div class="page-actions">
            <a class="button button-secondary" href="{{ route('computers.setup-guide') }}">
                <i class="bi bi-journal-check" aria-hidden="true"></i> Guia de inicio
            </a>
            <form method="GET" action="{{ route('computers.index') }}" class="search-form">
                <label class="sr-only" for="search">Buscar computadoras</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Nombre, serie, usuario, zona...">
                <button class="button button-secondary" type="submit">Buscar</button>
                @if ($search !== '')
                    <a class="button button-secondary" href="{{ route('computers.index') }}">Limpiar</a>
                @endif
            </form>
            <x-recent-filter />
            @can('create', \App\Models\HardwareAsset::class)
                <a class="button button-primary" href="{{ route('computers.create') }}">Nueva computadora</a>
            @endcan
        </div>
    </section>

    <section class="panel table-panel">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Equipo</th><th>Usuario</th><th>Procesador</th><th data-filterable="true">Estado</th><th>Zona</th><th>Folio</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($computers as $computer)
                        <tr>
                            <td><strong>{{ $computer->name }}</strong><small>{{ $computer->brand ?: 'Sin marca' }} {{ $computer->model }}</small></td>
                            <td>{{ $computer->assigned_to ?: 'Sin asignar' }}</td>
                            <td class="truncate">{{ $computer->processor ?: '-' }}</td>
                            <td><span class="status">{{ $computer->status }}</span></td>
                            <td>{{ $computer->zone ?: $computer->location ?: '-' }}</td>
                            <td>{{ $computer->code ?: '-' }}</td>
                            <td><div class="row-actions"><a class="table-action" href="{{ route('computers.show', $computer) }}">Detalles</a>@can('update', $computer)<a class="table-action secondary" href="{{ route('computers.edit', $computer) }}">Editar</a>@endcan</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No hay resultados para esta busqueda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($computers->hasPages())
            <nav class="pagination" aria-label="Paginacion">
                @if ($computers->onFirstPage())<span>Anterior</span>@else<a href="{{ $computers->previousPageUrl() }}">Anterior</a>@endif
                <strong>Pagina {{ $computers->currentPage() }} de {{ $computers->lastPage() }}</strong>
                @if ($computers->hasMorePages())<a href="{{ $computers->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif
            </nav>
        @endif
    </section>
@endsection
