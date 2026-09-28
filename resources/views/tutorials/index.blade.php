@extends('layouts.app')
@section('title', 'Tutoriales')
@section('page-title', 'Biblioteca de tutoriales')
@section('content')
<section class="page-intro compact">
    <div><p class="eyebrow">Documentacion TI</p><h2>Tutoriales</h2><p>{{ $items->total() }} guias disponibles.</p></div>
    <div class="page-actions"><x-recent-filter />@can('create', \App\Models\Tutorial::class)<a class="button button-primary" href="{{ route('tutorials.create') }}">Nuevo tutorial</a>@endcan</div>
</section>
<section class="panel">
    <form class="search-form">
        <input name="search" value="{{ $s }}" placeholder="Buscar tutorial...">
        <button class="button button-secondary">Buscar</button>
    </form>
    <div class="search-results-grid">
        @forelse($items as $item)
            <a class="search-result-card" href="{{ route('tutorials.show', $item) }}">
                <span class="global-result-icon">PDF</span>
                <div><small>{{ $item->category ?: 'Tutorial' }}</small><strong>{{ $item->title }}</strong><p>{{ $item->description ?: 'Sin descripcion' }}</p></div>
                <b>Ver</b>
            </a>
        @empty
            <p class="empty">No hay tutoriales.</p>
        @endforelse
    </div>
    @if($items->hasPages())
        <nav class="pagination" aria-label="Paginas de tutoriales">
            @if($items->onFirstPage())<span aria-disabled="true">Anterior</span>@else<a href="{{ $items->previousPageUrl() }}" rel="prev">Anterior</a>@endif
            <strong>Pagina {{ $items->currentPage() }} de {{ $items->lastPage() }}</strong>
            @if($items->hasMorePages())<a href="{{ $items->nextPageUrl() }}" rel="next">Siguiente</a>@else<span aria-disabled="true">Siguiente</span>@endif
        </nav>
    @endif
</section>
@endsection
