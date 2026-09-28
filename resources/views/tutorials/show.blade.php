@extends('layouts.app')
@section('title', $tutorial->title)
@section('page-title', 'Detalle de tutorial')
@section('content')
<div class="detail-toolbar">
    <a class="back-link" href="{{ route('tutorials.index') }}">&larr; Volver</a>
    <div class="page-actions">
        <a class="button button-secondary" href="{{ route('tutorials.preview', $tutorial) }}" target="_blank" rel="noopener">Abrir PDF</a>
        <a class="button button-primary" href="{{ route('tutorials.download', $tutorial) }}">Descargar PDF</a>
        @can('update', $tutorial)<a class="button button-secondary" href="{{ route('tutorials.edit', $tutorial) }}">Editar</a>@endcan
    </div>
</div>
<section class="asset-header">
    <div class="asset-icon">PDF</div>
    <div class="asset-heading"><p class="eyebrow">{{ $tutorial->category ?: 'Tutorial' }}</p><h2>{{ $tutorial->title }}</h2><p>{{ $tutorial->description ?: 'Sin descripcion' }}</p></div>
</section>
<section class="panel tutorial-viewer-panel">
    <div class="panel-header">
        <div><p class="eyebrow">Documento</p><h2>Vista previa del PDF</h2></div>
        <small>Usa los controles del visor para navegar o ampliar.</small>
    </div>
    <iframe class="tutorial-pdf-viewer" src="{{ route('tutorials.preview', $tutorial) }}#view=FitH" title="PDF: {{ $tutorial->title }}"></iframe>
</section>
@if($tutorial->comments)<section class="panel"><p>{{ $tutorial->comments }}</p></section>@endif
@include('shared.audit')
@endsection
