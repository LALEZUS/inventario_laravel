@extends('layouts.app')
@section('title', $ink->brand.' '.$ink->type)
@section('page-title', 'Detalle de tinta')
@section('content')
<div class="detail-toolbar"><a class="back-link" href="{{ route('supplies.index', ['type' => 'inks']) }}">&larr; Volver a tintas</a><div class="page-actions">@can('update', $ink)<a class="button button-primary" href="{{ route('inks.edit', $ink) }}">Editar tinta</a>@endcan @can('delete', $ink)<form method="POST" action="{{ route('inks.destroy', $ink) }}" onsubmit="return confirm('Eliminar esta tinta? Se desvinculara de sus impresoras.');">@csrf @method('DELETE')<button class="button button-danger">Eliminar</button></form>@endcan</div></div>
<section class="asset-header ink-asset-header ink-color-{{ $ink->colorKey() }}"><div class="asset-icon ink-asset-icon"><i class="bi bi-droplet-fill" aria-hidden="true"></i></div><div class="asset-heading"><p class="eyebrow">Tinta de impresion</p><h2>{{ $ink->brand }} {{ $ink->type }}</h2><p><x-ink-color :ink="$ink" compact /> &middot; {{ $ink->capacity ?: 'Capacidad no registrada' }}</p></div><span class="status large">{{ $ink->status }}</span></section>
<section class="detail-grid">@foreach(['Modelo'=>$ink->model,'Existencia'=>$ink->quantity.' unidades','Umbral de alerta'=>$ink->low_stock_threshold.' unidades o menos','Fecha de compra'=>$ink->dateValue('purchase_date'),'Vencimiento'=>$ink->dateValue('expiry_date'),'Actualizado'=>$ink->updated_at?->format('d/m/Y H:i')] as $label=>$value)<article><span>{{ $label }}</span><strong>{{ $value ?: '-' }}</strong></article>@endforeach</section>
@include('supplies.printers', ['printers' => $printers])
@if($ink->comments)<section class="panel"><div class="panel-header"><div><p class="eyebrow">Seguimiento</p><h2>Comentarios</h2></div></div><p>{{ $ink->comments }}</p></section>@endif
@include('shared.audit')
@endsection
